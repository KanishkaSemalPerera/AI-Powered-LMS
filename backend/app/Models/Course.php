<?php

namespace App\Models;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;

class Course extends Model
{
    /** @use HasFactory<\Database\Factories\CourseFactory> */
    use HasFactory, HasUniqueSlug;

    protected string $slugSource = 'title';

    /**
     * status, rating and student counters are changed only through dedicated
     * workflows (submit/approve, reviews, enrollments), never mass assigned.
     */
    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'subtitle',
        'description',
        'what_you_will_learn',
        'requirements',
        'thumbnail_path',
        'price',
        'level',
        'language',
    ];

    protected $attributes = [
        'status' => 'draft',
        'price' => 0,
        'level' => 'beginner',
        'language' => 'English',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'rating_avg' => 'decimal:2',
            'rating_count' => 'integer',
            'students_count' => 'integer',
            'level' => CourseLevel::class,
            'status' => CourseStatus::class,
            'what_you_will_learn' => 'array',
            'requirements' => 'array',
            'is_featured' => 'boolean',
            'submitted_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------- Relationships

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('position');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function quizzes(): HasManyThrough
    {
        return $this->hasManyThrough(Quiz::class, Lesson::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments')
            ->withPivot(['id', 'progress_percent', 'completed_at'])
            ->withTimestamps();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    // --------------------------------------------------------------- Helpers

    public function isFree(): bool
    {
        return (float) $this->price <= 0;
    }

    public function isPublished(): bool
    {
        return $this->status === CourseStatus::Published;
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $this->instructor_id === $user->getKey();
    }

    public function thumbnailUrl(): ?string
    {
        return $this->thumbnail_path ? Storage::url($this->thumbnail_path) : null;
    }

    /** Recalculates the cached average rating after reviews change. */
    public function refreshRating(): void
    {
        $stats = $this->reviews()->selectRaw('COUNT(*) as total, AVG(rating) as average')->first();

        $this->forceFill([
            'rating_count' => (int) $stats->total,
            'rating_avg' => round((float) $stats->average, 2),
        ])->saveQuietly();
    }

    /** Recalculates the cached student count after enrollments change. */
    public function refreshStudentsCount(): void
    {
        $this->forceFill(['students_count' => $this->enrollments()->count()])->saveQuietly();
    }

    // ---------------------------------------------------------------- Scopes

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', CourseStatus::Published->value);
    }

    public function scopeStatus(Builder $query, CourseStatus|string $status): Builder
    {
        return $query->where('status', $status instanceof CourseStatus ? $status->value : $status);
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('instructor_id', $user->getKey());
    }
}
