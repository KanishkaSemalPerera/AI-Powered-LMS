<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    public function definition(): array
    {
        $total = 4;
        $correct = fake()->numberBetween(0, $total);
        $percent = (int) round($correct / $total * 100);

        return [
            'user_id' => User::factory(),
            'quiz_id' => Quiz::factory(),
            'answers' => [],
            'correct_count' => $correct,
            'total_questions' => $total,
            'score_percent' => $percent,
            'passed' => $percent >= 70,
        ];
    }
}
