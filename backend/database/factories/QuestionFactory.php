<?php

namespace Database\Factories;

use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Question>
 */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'question' => rtrim(fake()->sentence(8), '.').'?',
            'options' => [
                fake()->words(3, true),
                fake()->words(3, true),
                fake()->words(3, true),
                fake()->words(3, true),
            ],
            'correct_option' => fake()->numberBetween(0, 3),
            'explanation' => fake()->sentence(12),
            'position' => 1,
        ];
    }
}
