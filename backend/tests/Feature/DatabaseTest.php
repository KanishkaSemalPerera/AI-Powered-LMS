<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Review;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_the_expected_platform(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::role(UserRole::Admin)->count());
        $this->assertSame(3, User::role(UserRole::Instructor)->count());
        $this->assertSame(10, User::role(UserRole::Student)->count());
        $this->assertSame(8, Course::count());
        $this->assertSame(6, Course::published()->count());
        $this->assertGreaterThan(0, Quiz::count());

        // The showcase student finished one course and received a certificate.
        $emma = User::where('email', 'student1@lms.test')->first();
        $this->assertSame(3, $emma->enrollments()->count());
        $this->assertSame(1, $emma->certificates()->count());

        // No student is enrolled in a course that isn't published.
        $this->assertSame(0, Enrollment::whereHas('course', fn ($q) => $q->where('status', '!=', 'published'))->count());
    }

    public function test_course_relationships_and_ordering(): void
    {
        $course = Course::factory()->published()->withContent(sections: 2, lessonsPerSection: 3)->create();

        $this->assertCount(2, $course->sections);
        $this->assertSame([1, 2], $course->sections->pluck('position')->all());
        $this->assertSame(6, $course->lessons()->count());
        $this->assertTrue($course->instructor->isInstructor());
        $this->assertSame(CourseStatus::Published, $course->status);
    }

    public function test_lesson_course_id_is_synced_from_its_section(): void
    {
        $section = Section::factory()->create();
        $lesson = $section->lessons()->create(['title' => 'Intro', 'type' => 'text', 'position' => 1]);

        $this->assertSame($section->course_id, $lesson->course_id);

        $otherSection = Section::factory()->create();
        $lesson->section()->associate($otherSection)->save();

        $this->assertSame($otherSection->course_id, $lesson->fresh()->course_id);
    }

    public function test_role_and_status_cannot_be_mass_assigned(): void
    {
        $user = User::create([
            'name' => 'Mallory',
            'email' => 'mallory@example.com',
            'password' => 'secret-password',
            'role' => 'admin',
            'is_active' => false,
        ]);

        $this->assertSame(UserRole::Student, $user->fresh()->role);
        $this->assertTrue($user->fresh()->is_active);

        $course = User::factory()->instructor()->create()->courses()->create([
            'title' => 'Sneaky course',
            'status' => 'published',
            'rating_avg' => 5,
        ]);

        $this->assertSame(CourseStatus::Draft, $course->fresh()->status);
        $this->assertSame('0.00', $course->fresh()->rating_avg);
    }

    public function test_passwords_are_hashed(): void
    {
        $user = User::factory()->create(['password' => 'plain-text-password']);

        $this->assertNotSame('plain-text-password', $user->getRawOriginal('password'));
        $this->assertTrue(password_verify('plain-text-password', $user->getRawOriginal('password')));
    }

    public function test_question_answer_key_is_hidden_from_serialisation(): void
    {
        $question = Question::factory()->create();

        $this->assertArrayNotHasKey('correct_option', $question->toArray());
        $this->assertArrayNotHasKey('explanation', $question->toArray());
    }

    public function test_slugs_are_unique(): void
    {
        $instructor = User::factory()->instructor()->create();
        $first = $instructor->courses()->create(['title' => 'Learn Laravel']);
        $second = $instructor->courses()->create(['title' => 'Learn Laravel']);

        $this->assertSame('learn-laravel', $first->slug);
        $this->assertSame('learn-laravel-2', $second->slug);
    }

    public function test_a_student_can_enroll_in_a_course_only_once(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->published()->create();

        Enrollment::create(['user_id' => $student->id, 'course_id' => $course->id]);

        $this->expectException(QueryException::class);
        Enrollment::create(['user_id' => $student->id, 'course_id' => $course->id]);
    }

    public function test_reviews_update_the_cached_course_rating(): void
    {
        $course = Course::factory()->published()->create();

        Review::factory()->for($course)->create(['rating' => 5]);
        Review::factory()->for($course)->create(['rating' => 4]);

        $course->refresh();
        $this->assertSame(2, $course->rating_count);
        $this->assertSame('4.50', $course->rating_avg);
    }

    public function test_certificates_get_a_unique_verification_code(): void
    {
        $certificate = Certificate::factory()->create();

        $this->assertMatchesRegularExpression('/^LH-[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}$/', $certificate->code);
        $this->assertNotNull($certificate->issued_at);
        $this->assertSame($certificate->user->name, $certificate->student_name);
    }

    public function test_deleting_a_course_cascades_to_its_content(): void
    {
        $course = Course::factory()->withContent(1, 2)->create();
        $lesson = $course->lessons()->first();
        Quiz::factory()->withQuestions(3)->for($lesson)->create();

        $course->delete();

        $this->assertSame(0, Section::count());
        $this->assertSame(0, Lesson::count());
        $this->assertSame(0, Quiz::count());
        $this->assertSame(0, Question::count());
    }
}
