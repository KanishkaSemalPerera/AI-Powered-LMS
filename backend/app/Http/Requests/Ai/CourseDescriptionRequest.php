<?php

namespace App\Http\Requests\Ai;

use App\Enums\CourseLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseDescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'level' => ['nullable', Rule::in(CourseLevel::values())],
            'notes' => ['nullable', 'string', 'max:3000'],
            'tone' => ['nullable', Rule::in(['friendly', 'professional', 'enthusiastic', 'concise'])],
            'course_id' => ['nullable', 'integer'],
        ];
    }
}
