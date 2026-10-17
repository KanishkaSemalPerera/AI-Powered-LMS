<?php

namespace Database\Factories;

use App\Enums\QuizStatus;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quiz>
 */
class QuizFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'title' => 'Quiz: '.rtrim(fake()->sentence(3), '.'),
            'description' => fake()->sentence(),
            'pass_mark' => 70,
            'status' => QuizStatus::Draft,
            'is_ai_generated' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => QuizStatus::Published]);
    }

    public function aiGenerated(): static
    {
        return $this->state(fn () => ['is_ai_generated' => true]);
    }

    /** Adds multiple-choice questions, e.g. Quiz::factory()->withQuestions(5)->create(). */
    public function withQuestions(int $count = 4): static
    {
        return $this->afterCreating(function (Quiz $quiz) use ($count) {
            Question::factory()
                ->count($count)
                ->sequence(fn ($sequence) => ['position' => $sequence->index + 1])
                ->for($quiz)
                ->create();
        });
    }
}
