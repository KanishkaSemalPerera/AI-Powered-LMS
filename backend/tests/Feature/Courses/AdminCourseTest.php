<?php

namespace Tests\Feature\Courses;

use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminCourseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->admin()->create());
    }

    public function test_admin_sees_the_pending_review_queue_oldest_first(): void
    {
        $newer = Course::factory()->pending()->create(['submitted_at' => now()->subHour()]);
        $older = Course::factory()->pending()->create(['submitted_at' => now()->subDays(2)]);
        Course::factory()->published()->create();

        $this->getJson('/api/v1/admin/courses?status=pending')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.id', $older->id)
            ->assertJsonPath('data.1.id', $newer->id);
    }

    public function test_admin_can_approve_a_pending_course(): void
    {
        $course = Course::factory()->pending()->create();

        $this->postJson("/api/v1/admin/courses/{$course->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        $this->assertNotNull($course->fresh()->published_at);
        $this->getJson('/api/v1/courses')->assertJsonPath('meta.total', 1);
    }

    public function test_admin_must_give_a_reason_to_reject(): void
    {
        $course = Course::factory()->pending()->create();

        $this->postJson("/api/v1/admin/courses/{$course->id}/reject", ['reason' => 'no'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');

        $this->postJson("/api/v1/admin/courses/{$course->id}/reject", ['reason' => 'Please add a clearer course description.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejection_reason', 'Please add a clearer course description.');
    }

    public function test_only_pending_courses_can_be_reviewed(): void
    {
        $draft = Course::factory()->draft()->create();

        $this->postJson("/api/v1/admin/courses/{$draft->id}/approve")->assertForbidden();
        $this->assertSame(CourseStatus::Draft, $draft->fresh()->status);
    }

    public function test_non_admins_cannot_moderate(): void
    {
        $course = Course::factory()->pending()->create();
        Sanctum::actingAs($course->instructor);

        $this->postJson("/api/v1/admin/courses/{$course->id}/approve")->assertForbidden();
        $this->getJson('/api/v1/admin/courses')->assertForbidden();
    }

    public function test_admin_manages_categories(): void
    {
        $this->postJson('/api/v1/admin/categories', ['name' => 'Music Production', 'icon' => 'music'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'music-production');

        $category = Category::where('name', 'Music Production')->first();

        $this->postJson('/api/v1/admin/categories', ['name' => 'Music Production'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->postJson('/api/v1/admin/categories', ['name' => 'Bad icon', 'icon' => '<script>'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('icon');

        $this->putJson("/api/v1/admin/categories/{$category->id}", ['name' => 'Audio Engineering'])
            ->assertOk()
            ->assertJsonPath('data.slug', 'audio-engineering');

        $course = Course::factory()->for($category)->published()->create();

        $this->deleteJson("/api/v1/admin/categories/{$category->id}")->assertOk();
        $this->assertModelMissing($category);
        $this->assertNull($course->fresh()->category_id);
    }

    public function test_non_admins_cannot_manage_categories(): void
    {
        Sanctum::actingAs(User::factory()->instructor()->create());

        $this->postJson('/api/v1/admin/categories', ['name' => 'Hacked'])->assertForbidden();
    }
}
