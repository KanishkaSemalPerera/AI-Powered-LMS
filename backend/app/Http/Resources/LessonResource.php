<?php

namespace App\Http\Resources;

use App\Services\LessonVideoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full lesson including content and a playback URL. Only returned to people
 * allowed to watch it (the instructor now; enrolled students in the player).
 *
 * @mixin \App\Models\Lesson
 */
class LessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'section_id' => $this->section_id,
            'title' => $this->title,
            'type' => $this->type->value,
            'content' => $this->content,
            'video_url' => $this->video_url,
            'video_upload' => $this->video_path ? [
                'original_name' => $this->video_original_name,
                'size' => $this->video_size,
            ] : null,
            'playback_url' => app(LessonVideoService::class)->playbackUrl($this->resource),
            'duration_seconds' => $this->duration_seconds,
            'is_preview' => $this->is_preview,
            'position' => $this->position,
        ];
    }
}
