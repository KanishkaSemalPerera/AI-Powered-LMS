<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * Full course page: summary fields plus description, outcomes, requirements,
 * curriculum outline, and (for the owner/admin) review workflow details.
 *
 * @mixin \App\Models\Course
 */
class CourseDetailResource extends CourseResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $canManage = $viewer && ($this->isOwnedBy($viewer) || $viewer->isAdmin());

        return array_merge(parent::toArray($request), [
            'description' => $this->description,
            'what_you_will_learn' => $this->what_you_will_learn ?? [],
            'requirements' => $this->requirements ?? [],
            'category_id' => $this->category_id,
            'instructor' => $this->whenLoaded('instructor', fn () => [
                'id' => $this->instructor->id,
                'name' => $this->instructor->name,
                'headline' => $this->instructor->headline,
                'bio' => $this->instructor->bio,
                'avatar_url' => $this->instructor->avatarUrl(),
            ]),
            'sections' => $this->whenLoaded('sections', fn () => $this->sections->map(fn ($section) => [
                'id' => $section->id,
                'title' => $section->title,
                'position' => $section->position,
                'lessons' => $section->lessons->map(fn ($lesson) => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'type' => $lesson->type->value,
                    'duration_seconds' => $lesson->duration_seconds,
                    'is_preview' => $lesson->is_preview,
                    'position' => $lesson->position,
                ])->values(),
            ])->values()),
            'can_manage' => $canManage,
            'rejection_reason' => $this->when($canManage, $this->rejection_reason),
            'submitted_at' => $this->when($canManage, fn () => $this->submitted_at?->toIso8601String()),
            'created_at' => $this->created_at?->toIso8601String(),
        ]);
    }
}
