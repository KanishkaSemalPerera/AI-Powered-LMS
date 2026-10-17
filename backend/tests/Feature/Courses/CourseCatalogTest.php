<?php

namespace Tests\Feature\Courses;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_published_courses_are_listed(): void
    {
        Course::factory()->published()->create(['title' => 'Visible course']);
        Course::factory()->draft()->create(['title' => 'Draft course']);
        Course::factory()->pending()->create(['title' => 'Pending course']);

        $this->getJson('/api/v1/courses')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'Visible course');
    }

    public function test_courses_can_be_searched_and_filtered(): void
    {
        $code = Category::factory()->create(['name' => 'Code']);
        $art = Category::factory()->create(['name' => 'Art']);

        Course::factory()->published()->for($code)->free()->create(['title' => 'Learn Python fast', 'level' => 'beginner']);
        Course::factory()->published()->for($code)->paid(49.99)->create(['title' => 'Advanced Rust', 'level' => 'advanced']);
        Course::factory()->published()->for($art)->paid(19.99)->create(['title' => 'Watercolour basics', 'level' => 'beginner']);

        $titles = fn (string $query) => collect($this->getJson('/api/v1/courses?'.$query)->assertOk()->json('data'))->pluck('title')->all();

        $this->assertSame(['Learn Python fast'], $titles('q=python'));
        $this->assertEqualsCanonicalizing(['Learn Python fast', 'Advanced Rust'], $titles('category=code'));
        $this->assertSame(['Learn Python fast'], $titles('price=free'));
        $this->assertEqualsCanonicalizing(['Advanced Rust', 'Watercolour basics'], $titles('price=paid'));
        $this->assertSame(['Advanced Rust'], $titles('level=advanced'));
        $this->assertSame(['Learn Python fast', 'Watercolour basics', 'Advanced Rust'], $titles('sort=price_asc'));
    }

    public function test_search_treats_wildcards_literally(): void
    {
        Course::factory()->published()->create(['title' => '100% practical SQL']);
        Course::factory()->published()->create(['title' => 'Other course']);

        $this->getJson('/api/v1/courses?q='.urlencode('100%'))->assertJsonPath('meta.total', 1);
        $this->getJson('/api/v1/courses?q='.urlencode('%'))->assertJsonPath('meta.total', 1);
    }

    public function test_rating_filter_and_pagination(): void
    {
        Course::factory()->published()->count(5)->create()->each(function (Course $course, int $i) {
            $course->forceFill(['rating_avg' => $i + 1])->save();
        });

        $this->getJson('/api/v1/courses?min_rating=4')->assertJsonPath('meta.total', 2);
        $this->getJson('/api/v1/courses?per_page=2&page=2')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonCount(2, 'data');
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->getJson('/api/v1/courses?sort=hack&per_page=500')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sort', 'per_page']);
    }

    public function test_course_detail_shows_curriculum_without_lesson_content(): void
    {
        $course = Course::factory()->published()->withContent(2, 2)->create();

        $response = $this->getJson("/api/v1/courses/{$course->slug}")
            ->assertOk()
            ->assertJsonPath('data.slug', $course->slug)
            ->assertJsonPath('data.lessons_count', 4)
            ->assertJsonCount(2, 'data.sections')
            ->assertJsonPath('data.can_manage', false)
            ->assertJsonMissingPath('data.rejection_reason');

        $this->assertArrayNotHasKey('content', $response->json('data.sections.0.lessons.0'));
        $this->assertArrayNotHasKey('video_url', $response->json('data.sections.0.lessons.0'));
    }

    public function test_unpublished_courses_are_hidden_from_the_public_but_visible_to_owner_and_admin(): void
    {
        $course = Course::factory()->draft()->create();

        $this->getJson("/api/v1/courses/{$course->slug}")->assertNotFound();

        $student = User::factory()->create();
        $this->withToken($student->createToken('t')->plainTextToken)
            ->getJson("/api/v1/courses/{$course->slug}")->assertNotFound();

        $this->app['auth']->forgetGuards();
        $this->withToken($course->instructor->createToken('t')->plainTextToken)
            ->getJson("/api/v1/courses/{$course->slug}")
            ->assertOk()
            ->assertJsonPath('data.can_manage', true);

        $this->app['auth']->forgetGuards();
        $admin = User::factory()->admin()->create();
        $this->withToken($admin->createToken('t')->plainTextToken)
            ->getJson("/api/v1/courses/{$course->slug}")->assertOk();
    }

    public function test_categories_list_counts_only_published_courses(): void
    {
        $category = Category::factory()->create(['name' => 'Design']);
        Course::factory()->published()->for($category)->count(2)->create();
        Course::factory()->draft()->for($category)->create();

        $this->getJson('/api/v1/categories')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Design')
            ->assertJsonPath('data.0.courses_count', 2);
    }
}
