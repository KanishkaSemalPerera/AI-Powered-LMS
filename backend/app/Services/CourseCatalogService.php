<?php

namespace App\Services;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-side queries for course lists: the public catalogue, an instructor's
 * own courses, and the admin review queue. Each result is eager-loaded with
 * what CourseResource needs (no N+1 queries).
 */
class CourseCatalogService
{
    public const SORTS = ['newest', 'popular', 'rating', 'price_asc', 'price_desc', 'title'];

    /**
     * @param  array{q?: string, category?: string, level?: string, price?: string,
     *               min_rating?: numeric, featured?: bool, sort?: string, per_page?: int}  $filters
     */
    public function publicCatalogue(array $filters): LengthAwarePaginator
    {
        $query = $this->baseQuery()->published();

        $this->applyFilters($query, $filters);
        $this->applySort($query, $filters['sort'] ?? 'newest');

        return $query->paginate($filters['per_page'] ?? 12)->withQueryString();
    }

    public function instructorCourses(User $instructor, array $filters): LengthAwarePaginator
    {
        $query = $this->baseQuery()->ownedBy($instructor);

        if (! empty($filters['status'])) {
            $query->status($filters['status']);
        }
        $this->applySearch($query, $filters['q'] ?? null);

        return $query->latest('updated_at')->paginate($filters['per_page'] ?? 12)->withQueryString();
    }

    public function adminCourses(array $filters): LengthAwarePaginator
    {
        $query = $this->baseQuery();

        if (! empty($filters['status'])) {
            $query->status($filters['status']);
        }
        $this->applySearch($query, $filters['q'] ?? null);

        // Oldest submissions first, so the review queue is first-in, first-out.
        ($filters['status'] ?? null) === CourseStatus::Pending->value
            ? $query->orderBy('submitted_at')
            : $query->latest('updated_at');

        return $query->paginate($filters['per_page'] ?? 15)->withQueryString();
    }

    /** Course detail with curriculum (lesson titles, durations, preview flags — never content). */
    public function loadDetail(Course $course): Course
    {
        return $course->load([
            'instructor',
            'category',
            'sections' => fn ($query) => $query->orderBy('position'),
            'sections.lessons' => fn ($query) => $query->orderBy('position')->select([
                'id', 'section_id', 'course_id', 'title', 'type', 'duration_seconds', 'is_preview', 'position',
            ]),
        ])->loadCount('lessons')->loadSum('lessons', 'duration_seconds');
    }

    private function baseQuery(): Builder
    {
        return Course::query()
            ->with(['instructor', 'category'])
            ->withCount('lessons')
            ->withSum('lessons', 'duration_seconds');
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $this->applySearch($query, $filters['q'] ?? null);

        if (! empty($filters['category'])) {
            $query->whereHas('category', fn (Builder $q) => $q->where('slug', $filters['category']));
        }

        if (! empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        match ($filters['price'] ?? null) {
            'free' => $query->where('price', '<=', 0),
            'paid' => $query->where('price', '>', 0),
            default => null,
        };

        if (! empty($filters['min_rating'])) {
            $query->where('rating_avg', '>=', (float) $filters['min_rating']);
        }

        if (! empty($filters['featured'])) {
            $query->where('is_featured', true);
        }
    }

    private function applySearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);
        if ($term === '') {
            return;
        }

        // Escape LIKE wildcards so "100%" searches literally. An explicit ESCAPE
        // character behaves the same on MySQL/MariaDB and SQLite (used in tests).
        $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';

        $query->where(function (Builder $q) use ($like) {
            foreach (['title', 'subtitle', 'description'] as $column) {
                $q->orWhereRaw("{$column} LIKE ? ESCAPE '!'", [$like]);
            }
        });
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'popular' => $query->orderByDesc('students_count')->orderByDesc('rating_avg'),
            'rating' => $query->orderByDesc('rating_avg')->orderByDesc('rating_count'),
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'title' => $query->orderBy('title'),
            default => $query->orderByDesc('published_at'),
        };

        // Stable ordering for pagination when the main sort ties.
        $query->orderByDesc('id');
    }
}
