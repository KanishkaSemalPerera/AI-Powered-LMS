<?php

namespace Tests\Feature\Payments;

use App\Events\StudentEnrolled;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->student()->create();
        Sanctum::actingAs($this->student);
    }

    public function test_student_can_enroll_in_a_free_course(): void
    {
        Event::fake([StudentEnrolled::class]);
        $course = Course::factory()->published()->free()->create();

        $this->postJson("/api/v1/courses/{$course->id}/enroll")
            ->assertCreated()
            ->assertJsonPath('data.course.id', $course->id)
            ->assertJsonPath('data.progress_percent', 0);

        $this->assertTrue($this->student->isEnrolledIn($course));
        $this->assertSame(1, $course->fresh()->students_count);
        Event::assertDispatched(StudentEnrolled::class);
    }

    public function test_enrolling_twice_is_rejected(): void
    {
        $course = Course::factory()->published()->free()->create();

        $this->postJson("/api/v1/courses/{$course->id}/enroll")->assertCreated();
        $this->postJson("/api/v1/courses/{$course->id}/enroll")
            ->assertStatus(409)
            ->assertJsonPath('message', 'You are already enrolled in this course.');

        $this->assertSame(1, Enrollment::count());
    }

    public function test_paid_courses_cannot_be_enrolled_for_free(): void
    {
        $course = Course::factory()->published()->paid(29.99)->create();

        $this->postJson("/api/v1/courses/{$course->id}/enroll")->assertStatus(402);
        $this->assertFalse($this->student->isEnrolledIn($course));
    }

    public function test_unpublished_courses_cannot_be_enrolled(): void
    {
        $course = Course::factory()->draft()->free()->create();

        $this->postJson("/api/v1/courses/{$course->id}/enroll")->assertNotFound();
    }

    public function test_only_students_can_enroll(): void
    {
        $course = Course::factory()->published()->free()->create();
        Sanctum::actingAs(User::factory()->instructor()->create());

        $this->postJson("/api/v1/courses/{$course->id}/enroll")->assertForbidden();
    }

    public function test_guests_must_log_in(): void
    {
        $course = Course::factory()->published()->free()->create();
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', '')->postJson("/api/v1/courses/{$course->id}/enroll")->assertUnauthorized();
    }

    public function test_my_courses_lists_enrollments_with_course_details(): void
    {
        $courses = Course::factory()->published()->free()->count(2)->create();
        foreach ($courses as $course) {
            $this->postJson("/api/v1/courses/{$course->id}/enroll");
        }
        Enrollment::factory()->create(); // someone else's

        $this->getJson('/api/v1/my/courses')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonStructure(['data' => [['id', 'progress_percent', 'course' => ['title', 'slug', 'instructor']]]]);
    }

    public function test_course_page_tells_the_student_they_are_enrolled(): void
    {
        $course = Course::factory()->published()->free()->create();
        $token = $this->student->createToken('t')->plainTextToken;

        $this->withToken($token)->getJson("/api/v1/courses/{$course->slug}")->assertJsonPath('data.enrollment', null);

        $this->postJson("/api/v1/courses/{$course->id}/enroll");
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson("/api/v1/courses/{$course->slug}")
            ->assertJsonPath('data.enrollment.progress_percent', 0);
    }
}
