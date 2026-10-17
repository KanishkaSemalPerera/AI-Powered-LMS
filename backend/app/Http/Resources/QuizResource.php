<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Quiz for the instructor's editor — includes the answer key.
 *
 * @mixin \App\Models\Quiz
 */
class QuizResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lesson_id' => $this->lesson_id,
            'title' => $this->title,
            'description' => $this->description,
            'pass_mark' => $this->pass_mark,
            'status' => $this->status->value,
            'is_ai_generated' => $this->is_ai_generated,
            'questions' => $this->whenLoaded('questions', fn () => $this->questions->map(fn ($q) => [
                'id' => $q->id,
                'question' => $q->question,
                'options' => $q->options,
                'correct_option' => $q->correct_option,
                'explanation' => $q->explanation,
            ])->values()),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
