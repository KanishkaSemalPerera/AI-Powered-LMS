<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory()->published(),
            // Skewed towards positive ratings, like real course marketplaces.
            'rating' => fake()->randomElement([3, 4, 4, 5, 5, 5]),
            'comment' => fake()->paragraph(2),
        ];
    }
}
