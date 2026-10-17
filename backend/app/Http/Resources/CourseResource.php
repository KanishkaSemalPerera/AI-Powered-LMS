<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Course summary for cards and tables.
 *
 * @mixin \App\Models\Course
 */
class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'subtitle' => $this->subtitle,
            'thumbnail_url' => $this->thumbnailUrl(),
            'price' => (float) $this->price,
            'is_free' => $this->isFree(),
            'currency' => config('services.stripe.currency', 'usd'),
            'level' => $this->level->value,
            'level_label' => $this->level->label(),
            'language' => $this->language,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_featured' => $this->is_featured,
            'rating_avg' => (float) $this->rating_avg,
            'rating_count' => $this->rating_count,
            'students_count' => $this->students_count,
            'lessons_count' => $this->whenCounted('lessons'),
            'duration_seconds' => $this->whenAggregated('lessons', 'duration_seconds', 'sum', fn ($value) => (int) $value, 0),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'instructor' => $this->whenLoaded('instructor', fn () => [
                'id' => $this->instructor->id,
                'name' => $this->instructor->name,
                'headline' => $this->instructor->headline,
                'avatar_url' => $this->instructor->avatarUrl(),
            ]),
            'published_at' => $this->published_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
