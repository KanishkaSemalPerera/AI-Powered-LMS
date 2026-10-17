<?php

namespace App\Models;

use App\Enums\QuizStatus;
use Database\Factories\QuizFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'pass_mark',
        'status',
        'is_ai_generated',
    ];

    protected $attributes = [
        'status' => 'draft',
        'pass_mark' => 70,
        'is_ai_generated' => false,
    ];

    protected function casts(): array
    {
        return [
            'status' => QuizStatus::class,
            'pass_mark' => 'integer',
            'is_ai_generated' => 'boolean',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function isPublished(): bool
    {
        return $this->status === QuizStatus::Published;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', QuizStatus::Published->value);
    }
}
