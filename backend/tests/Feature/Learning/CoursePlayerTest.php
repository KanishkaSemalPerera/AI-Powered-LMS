<?php

namespace Tests\Feature\Learning;

use App\Events\CourseCompleted;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CoursePlayerTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Course $course;

    /** @var array<int, Lesson> in learning order */
    private array $lessons = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::factory()->published()->create();
        // Section 2 is created first to prove ordering uses positions, not ids.
        $second = Section::factory()->for($this->course)->create(['position' => 2, 'title' => 'Second']);
        $first = Section::factory()->for($this->course)->create(['position' => 1, 'title' => 'First']);
        $this->lessons = [
            Lesson::factory()->for($first)->create(['position' => 1, 'title' => 'L1', 'content' => 'Secret content 1']),
            Lesson::factory()->for($first)->create(['position' => 2, 'title' => 'L2']),
            Lesson::factory()->for($second)->create(['position' => 1, 'title' => 'L3']),
            Lesson::factory()->for($second)->create(['position' => 2, 'title' => 'L4']),
        ];

        $this->student = User::factory()->student()->create();
        Enrollment::factory()->for($this->student)->for($this->course)->create();
        Sanctum::actingAs($this->student);
    }

    private function url(string $suffix = ''): string
    {
        return "/api/v1/learn/{$this->course->slug}{$suffix}";
    }

    public function test_enrolled_student_gets_the_curriculum_in_order(): void
    {
        $this->getJson($this->url())
            ->assertOk()
            ->assertJsonPath('data.mode', 'student')
            ->assertJsonPath('data.sections.0.title', 'First')
            ->assertJsonPath('data.sections.1.lessons.1.title', 'L4')
            ->assertJsonPath('data.progress.progress_percent', 0)
            ->assertJsonPath('data.progress.total_lessons', 4)
            ->assertJsonPath('data.progress.resume_lesson_id', $this->lessons[0]->id)
            ->assertJsonMissingPath('data.sections.0.lessons.0.content');
    }

    public function test_lesson_returns_content_navigation_and_updates_resume_point(): void
    {
        $this->getJson($this->url("/lessons/{$this->lessons[2]->id}"))
            ->assertOk()
            ->assertJsonPath('data.lesson.title', 'L3')
            ->assertJsonPath('data.navigation.index', 3)
            ->assertJsonPath('data.navigation.previous_lesson_id', $this->lessons[1]->id)
            ->assertJsonPath('data.navigation.next_lesson_id', $this->lessons[3]->id)
            ->assertJsonPath('data.is_completed', false);

        $this->getJson($this->url())->assertJsonPath('data.progress.resume_lesson_id', $this->lessons[2]->id);
    }

    public function test_students_who_are_not_enrolled_cannot_see_lessons(): void
    {
        Sanctum::actingAs(User::factory()->student()->create());

        $this->getJson($this->url())
            ->assertForbidden()
            ->assertJsonPath('message', 'Enroll in this course to access its lessons.');
        $this->getJson($this->url("/lessons/{$this->lessons[0]->id}"))->assertForbidden();
        $this->postJson($this->url("/lessons/{$this->lessons[0]->id}/complete"))->assertForbidden();
    }

    public function test_lessons_from_another_course_are_not_reachable(): void
    {
        $foreign = Lesson::factory()->create();

        $this->getJson($this->url("/lessons/{$foreign->id}"))->assertNotFound();
        $this->postJson($this->url("/lessons/{$foreign->id}/complete"))->assertNotFound();
    }

    public function test_completing_lessons_updates_progress_idempotently(): void
    {
        $this->postJson($this->url("/lessons/{$this->lessons[0]->id}/complete"))
            ->assertOk()
            ->assertJsonPath('data.progress_percent', 25)
            ->assertJsonPath('data.completed_lesson_ids', [$this->lessons[0]->id]);

        // Completing again changes nothing.
        $this->postJson($this->url("/lessons/{$this->lessons[0]->id}/complete"))->assertJsonPath('data.progress_percent', 25);

        $this->postJson($this->url("/lessons/{$this->lessons[1]->id}/complete"))->assertJsonPath('data.progress_percent', 50);

        $this->deleteJson($this->url("/lessons/{$this->lessons[0]->id}/complete"))
            ->assertOk()
            ->assertJsonPath('data.progress_percent', 25);
    }

    public function test_finishing_every_lesson_completes_the_course_once(): void
    {
        Event::fake([CourseCompleted::class]);

        foreach (array_slice($this->lessons, 0, 3) as $lesson) {
            $this->postJson($this->url("/lessons/{$lesson->id}/complete"))->assertJsonPath('data.course_completed_now', false);
        }

        $this->postJson($this->url("/lessons/{$this->lessons[3]->id}/complete"))
            ->assertOk()
            ->assertJsonPath('data.progress_percent', 100)
            ->assertJsonPath('data.course_completed_now', true)
            ->assertJsonPath('message', 'Congratulations — you completed the course!');

        Event::assertDispatchedTimes(CourseCompleted::class, 1);

        // Un-completing and re-completing does not complete the course a second time.
        $this->deleteJson($this->url("/lessons/{$this->lessons[3]->id}/complete"))
            ->assertJsonPath('data.progress_percent', 75)
            ->assertJsonPath('data.completed_at', fn ($value) => $value !== null);
        $this->postJson($this->url("/lessons/{$this->lessons[3]->id}/complete"));

        Event::assertDispatchedTimes(CourseCompleted::class, 1);
    }

    public function test_owner_can_preview_the_player_without_tracking_progress(): void
    {
        Sanctum::actingAs($this->course->instructor);

        $this->getJson($this->url())
            ->assertOk()
            ->assertJsonPath('data.mode', 'preview')
            ->assertJsonPath('data.progress', null);
        $this->getJson($this->url("/lessons/{$this->lessons[0]->id}"))->assertOk();
        $this->postJson($this->url("/lessons/{$this->lessons[0]->id}/complete"))
            ->assertForbidden()
            ->assertJsonPath('message', 'Enroll in this course to track your progress.');
    }

    public function test_guests_must_log_in(): void
    {
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', '')->getJson($this->url())->assertUnauthorized();
    }

    public function test_free_preview_lessons_are_public(): void
    {
        $this->app['auth']->forgetGuards();
        $this->lessons[0]->update(['is_preview' => true]);

        $this->withHeader('Authorization', '')
            ->getJson("/api/v1/courses/{$this->course->slug}/lessons/{$this->lessons[0]->id}/preview")
            ->assertOk()
            ->assertJsonPath('data.content', 'Secret content 1');

        $this->withHeader('Authorization', '')
            ->getJson("/api/v1/courses/{$this->course->slug}/lessons/{$this->lessons[1]->id}/preview")
            ->assertNotFound();
    }

    public function test_preview_lessons_of_unpublished_courses_stay_hidden(): void
    {
        $draft = Course::factory()->draft()->create();
        $lesson = Lesson::factory()->for(Section::factory()->for($draft))->create(['is_preview' => true]);

        $this->getJson("/api/v1/courses/{$draft->slug}/lessons/{$lesson->id}/preview")->assertNotFound();
    }
}
