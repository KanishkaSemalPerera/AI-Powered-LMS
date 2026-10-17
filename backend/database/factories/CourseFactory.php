<?php

namespace Database\Factories;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'instructor_id' => User::factory()->instructor(),
            'category_id' => Category::factory(),
            'title' => rtrim(fake()->unique()->sentence(4), '.'),
            'subtitle' => fake()->sentence(10),
            'description' => collect(fake()->paragraphs(3))->implode("\n\n"),
            'what_you_will_learn' => fake()->sentences(4),
            'requirements' => fake()->sentences(2),
            'price' => fake()->randomElement([0, 19.99, 29.99, 49.99]),
            'level' => fake()->randomElement(CourseLevel::cases()),
            'language' => 'English',
            'status' => CourseStatus::Draft,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => CourseStatus::Draft, 'submitted_at' => null, 'published_at' => null]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => CourseStatus::Pending, 'submitted_at' => now()]);
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => CourseStatus::Published,
            'submitted_at' => now()->subDays(10),
            'published_at' => now()->subDays(7),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => CourseStatus::Rejected,
            'submitted_at' => now()->subDays(2),
            'rejection_reason' => 'Please add more detail to the course description.',
        ]);
    }

    public function free(): static
    {
        return $this->state(fn () => ['price' => 0]);
    }

    public function paid(float $price = 29.99): static
    {
        return $this->state(fn () => ['price' => $price]);
    }

    /** Adds sections with lessons, e.g. Course::factory()->withContent(2, 3)->create(). */
    public function withContent(int $sections = 2, int $lessonsPerSection = 3): static
    {
        return $this->afterCreating(function (Course $course) use ($sections, $lessonsPerSection) {
            Section::factory()
                ->count($sections)
                ->sequence(fn ($sequence) => ['position' => $sequence->index + 1])
                ->for($course)
                ->create()
                ->each(function (Section $section) use ($lessonsPerSection) {
                    Lesson::factory()
                        ->count($lessonsPerSection)
                        ->sequence(fn ($sequence) => ['position' => $sequence->index + 1])
                        ->for($section)
                        ->create();
                });
        });
    }
}
