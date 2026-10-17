<?php

namespace App\Http\Requests\Course;

use App\Enums\CourseLevel;
use App\Services\CourseCatalogService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Query-string filters for GET /courses. */
class CourseCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:120'],
            'level' => ['nullable', Rule::in(CourseLevel::values())],
            'price' => ['nullable', Rule::in(['free', 'paid'])],
            'min_rating' => ['nullable', 'numeric', 'between:0,5'],
            'featured' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(CourseCatalogService::SORTS)],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
