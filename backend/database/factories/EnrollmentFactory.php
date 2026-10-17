<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory()->published(),
            'payment_id' => null,
            'progress_percent' => 0,
            'last_lesson_id' => null,
            'last_accessed_at' => null,
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'progress_percent' => 100,
            'completed_at' => now(),
            'last_accessed_at' => now(),
        ]);
    }
}
