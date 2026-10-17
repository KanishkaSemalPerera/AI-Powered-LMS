<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Create and update categories (admin only, enforced by route middleware). */
class SaveCategoryRequest extends FormRequest
{
    /** Icon names the frontend knows how to render (lucide icons). */
    public const ICONS = [
        'code', 'database', 'brain', 'palette', 'briefcase', 'megaphone', 'sparkles', 'camera',
        'music', 'heart-pulse', 'calculator', 'languages', 'book-open', 'globe',
    ];

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) $this->input('name'))]);
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'min:2', 'max:100', Rule::unique('categories', 'name')->ignore($category?->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', Rule::in(self::ICONS)],
        ];
    }
}
