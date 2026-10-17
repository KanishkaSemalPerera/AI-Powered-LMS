<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Fills the "slug" column from another attribute when a model is created,
 * appending -2, -3, ... if the slug is already taken.
 *
 * Models set the source attribute with: protected string $slugSource = 'title';
 */
trait HasUniqueSlug
{
    public static function bootHasUniqueSlug(): void
    {
        static::creating(function ($model) {
            if (blank($model->slug)) {
                $model->slug = $model->generateUniqueSlug($model->{$model->slugSourceAttribute()});
            }
        });
    }

    public function generateUniqueSlug(string $value): string
    {
        $base = Str::slug($value) ?: Str::lower(Str::random(8));
        $slug = $base;
        $suffix = 2;

        while (
            static::query()
                ->where('slug', $slug)
                ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    protected function slugSourceAttribute(): string
    {
        return property_exists($this, 'slugSource') ? $this->slugSource : 'title';
    }
}
