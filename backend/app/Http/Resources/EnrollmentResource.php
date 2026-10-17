<?php

namespace App\Http\Resources;

use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Enrollment
 */
class EnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course' => new CourseResource($this->whenLoaded('course')),
            'progress_percent' => $this->progress_percent,
            'last_lesson_id' => $this->last_lesson_id,
            'last_accessed_at' => $this->last_accessed_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'enrolled_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
