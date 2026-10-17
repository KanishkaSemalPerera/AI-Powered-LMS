<?php

namespace App\Models;

use App\Enums\LessonType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lesson extends Model
{
    /** @use HasFactory<\Database\Factories\LessonFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'video_path',
        'video_url',
        'content',
        'duration_seconds',
        'is_preview',
        'position',
    ];

    protected $attributes = [
        'type' => 'video',
        'is_preview' => false,
        'duration_seconds' => 0,
    ];

    protected function casts(): array
    {
        return [
            'type' => LessonType::class,
            'is_preview' => 'boolean',
            'duration_seconds' => 'integer',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Keep the denormalised course_id in sync with the parent section.
        static::saving(function (Lesson $lesson) {
            if ($lesson->section_id && ($lesson->isDirty('section_id') || ! $lesson->course_id)) {
                $lesson->course_id = Section::whereKey($lesson->section_id)->value('course_id');
            }
        });
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function hasVideo(): bool
    {
        return filled($this->video_path) || filled($this->video_url);
    }
}
