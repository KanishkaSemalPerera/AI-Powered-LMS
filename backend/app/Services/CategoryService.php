<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CategoryService
{
    /** Category list with the number of published courses in each. */
    public function list(int $perPage = 50): LengthAwarePaginator
    {
        return Category::query()
            ->withCount(['courses as courses_count' => fn ($query) => $query->published()])
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Category
    {
        return Category::create($data)->loadCount(['courses as courses_count' => fn ($q) => $q->published()]);
    }

    public function update(Category $category, array $data): Category
    {
        $category->fill($data);

        // Keep URLs readable when an admin renames a category.
        if ($category->isDirty('name') && ! array_key_exists('slug', $data)) {
            $category->slug = $category->generateUniqueSlug($category->name);
        }

        $category->save();

        return $category->loadCount(['courses as courses_count' => fn ($q) => $q->published()]);
    }

    /** Courses in a deleted category become uncategorised (FK is nullOnDelete). */
    public function delete(Category $category): void
    {
        $category->delete();
    }
}
