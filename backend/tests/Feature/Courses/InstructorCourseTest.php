<?php

namespace Tests\Feature\Courses;

use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InstructorCourseTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = User::factory()->instructor()->create();
        Sanctum::actingAs($this->instructor);
    }

    public function test_instructor_can_create_a_draft_course(): void
    {
        $category = Category::factory()->create();

        $this->postJson('/api/v1/instructor/courses', [
            'title' => 'Mastering Tailwind CSS',
            'category_id' => $category->id,
            'price' => 19.99,
            'level' => 'intermediate',
            'what_you_will_learn' => ['Utility-first CSS', '', '  Dark mode  '],
            // Workflow fields must be ignored.
            'status' => 'published',
            'instructor_id' => 999,
        ])->assertCreated()
            ->assertJsonPath('data.slug', 'mastering-tailwind-css')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.what_you_will_learn', ['Utility-first CSS', 'Dark mode'])
            ->assertJsonPath('data.can_manage', true);

        $this->assertDatabaseHas('courses', ['title' => 'Mastering Tailwind CSS', 'instructor_id' => $this->instructor->id]);
    }

    public function test_course_validation(): void
    {
        $this->postJson('/api/v1/instructor/courses', ['title' => 'abc', 'price' => 0.2, 'level' => 'expert', 'category_id' => 999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'price', 'level', 'category_id']);
    }

    public function test_students_cannot_access_instructor_routes(): void
    {
        Sanctum::actingAs(User::factory()->student()->create());

        $this->getJson('/api/v1/instructor/courses')->assertForbidden();
        $this->postJson('/api/v1/instructor/courses', ['title' => 'Sneaky course'])->assertForbidden();
    }

    public function test_instructor_lists_only_their_own_courses(): void
    {
        Course::factory()->for($this->instructor, 'instructor')->count(2)->create();
        Course::factory()->create();

        $this->getJson('/api/v1/instructor/courses')->assertOk()->assertJsonPath('meta.total', 2);
        $this->getJson('/api/v1/instructor/courses?status=published')->assertOk()->assertJsonPath('meta.total', 0);
    }

    public function test_instructor_cannot_view_or_edit_another_instructors_course(): void
    {
        $other = Course::factory()->create();

        $this->getJson("/api/v1/instructor/courses/{$other->id}")->assertNotFound();
        $this->putJson("/api/v1/instructor/courses/{$other->id}", ['title' => 'Hijacked title'])
            ->assertForbidden()
            ->assertJsonPath('message', 'You can only edit your own courses.');
        $this->deleteJson("/api/v1/instructor/courses/{$other->id}")->assertForbidden();
    }

    public function test_instructor_can_update_their_course(): void
    {
        $course = Course::factory()->for($this->instructor, 'instructor')->create(['title' => 'Old title']);

        $this->putJson("/api/v1/instructor/courses/{$course->id}", ['title' => 'New better title', 'price' => 0])
            ->assertOk()
            ->assertJsonPath('data.title', 'New better title')
            ->assertJsonPath('data.is_free', true)
            // Slug stays stable so shared links keep working.
            ->assertJsonPath('data.slug', $course->slug);
    }

    public function test_courses_under_review_are_locked(): void
    {
        $course = Course::factory()->for($this->instructor, 'instructor')->pending()->create();

        $this->putJson("/api/v1/instructor/courses/{$course->id}", ['title' => 'Changed while pending'])
            ->assertForbidden()
            ->assertJsonPath('message', 'This course is under review and cannot be edited until an admin has reviewed it.');
    }

    public function test_editing_a_rejected_course_moves_it_back_to_draft(): void
    {
        $course = Course::factory()->for($this->instructor, 'instructor')->rejected()->create();

        $this->putJson("/api/v1/instructor/courses/{$course->id}", ['title' => 'Fixed course title'])
            ->assertOk()
            ->assertJsonPath('data.status', 'draft');
    }

    public function test_thumbnail_upload_validates_and_replaces_the_old_file(): void
    {
        $disk = Storage::fake(config('filesystems.default'));
        $course = Course::factory()->for($this->instructor, 'instructor')->create();

        $this->post("/api/v1/instructor/courses/{$course->id}/thumbnail", ['thumbnail' => UploadedFile::fake()->image('small.jpg', 100, 100)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('thumbnail');

        $this->post("/api/v1/instructor/courses/{$course->id}/thumbnail", ['thumbnail' => UploadedFile::fake()->image('a.jpg', 1280, 720)])
            ->assertOk();
        $first = $course->fresh()->thumbnail_path;
        $disk->assertExists($first);

        $this->post("/api/v1/instructor/courses/{$course->id}/thumbnail", ['thumbnail' => UploadedFile::fake()->image('b.png', 1280, 720)])
            ->assertOk()
            ->assertJsonPath('data.thumbnail_url', fn ($url) => str_contains($url, "thumbnails/{$course->id}/"));
        $disk->assertMissing($first);
    }

    public function test_submit_for_review_requires_a_complete_course(): void
    {
        $course = Course::factory()->for($this->instructor, 'instructor')->create(['category_id' => null, 'description' => 'Too short']);

        $this->postJson("/api/v1/instructor/courses/{$course->id}/submit")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'description', 'lessons']);

        $this->assertSame(CourseStatus::Draft, $course->fresh()->status);
    }

    public function test_complete_course_can_be_submitted_once(): void
    {
        $course = Course::factory()->for($this->instructor, 'instructor')->withContent(1, 1)->create([
            'description' => str_repeat('A thorough course description. ', 6),
        ]);

        $this->postJson("/api/v1/instructor/courses/{$course->id}/submit")
            ->assertOk()
            ->assertJsonPath('data.status', 'pending');

        $this->assertNotNull($course->fresh()->submitted_at);

        $this->postJson("/api/v1/instructor/courses/{$course->id}/submit")->assertForbidden();
    }

    public function test_only_draft_courses_without_students_can_be_deleted(): void
    {
        $draft = Course::factory()->for($this->instructor, 'instructor')->create();
        $published = Course::factory()->for($this->instructor, 'instructor')->published()->create();
        $withStudents = Course::factory()->for($this->instructor, 'instructor')->rejected()->create();
        Enrollment::factory()->for($withStudents)->create();

        $this->deleteJson("/api/v1/instructor/courses/{$published->id}")->assertForbidden();
        $this->deleteJson("/api/v1/instructor/courses/{$withStudents->id}")
            ->assertForbidden()
            ->assertJsonPath('message', 'This course has enrolled students and cannot be deleted.');

        $this->deleteJson("/api/v1/instructor/courses/{$draft->id}")->assertOk();
        $this->assertModelMissing($draft);
    }
}
