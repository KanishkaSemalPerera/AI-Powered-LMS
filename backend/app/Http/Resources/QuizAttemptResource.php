<?php

namespace App\Http\Resources;

use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A submitted attempt. With the quiz's questions loaded it includes a review:
 * the student's choice, the correct option and the explanation per question.
 *
 * @mixin QuizAttempt
 */
class QuizAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $withReview = $this->relationLoaded('quiz') && $this->quiz->relationLoaded('questions');

        return [
            'id' => $this->id,
            'score_percent' => $this->score_percent,
            'correct_count' => $this->correct_count,
            'total_questions' => $this->total_questions,
            'passed' => $this->passed,
            'pass_mark' => $this->whenLoaded('quiz', fn () => $this->quiz->pass_mark),
            'created_at' => $this->created_at?->toIso8601String(),
            'review' => $this->when($withReview, fn () => $this->quiz->questions->map(function ($question) {
                $chosen = $this->answers[$question->id] ?? $this->answers[(string) $question->id] ?? null;

                return [
                    'question_id' => $question->id,
                    'question' => $question->question,
                    'options' => $question->options,
                    'chosen_option' => $chosen,
                    'correct_option' => $question->correct_option,
                    'is_correct' => $chosen === $question->correct_option,
                    'explanation' => $question->explanation,
                ];
            })->values()),
        ];
    }
}
