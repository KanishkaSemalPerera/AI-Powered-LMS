<?php

namespace App\Http\Requests\Curriculum;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Full curriculum order: { sections: [{ id, lessons: [lessonId, ...] }, ...] }.
 * CurriculumService::reorder checks the ids belong to the course.
 */
class ReorderCurriculumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sections' => ['present', 'array', 'max:200'],
            'sections.*.id' => ['required', 'integer', 'distinct'],
            'sections.*.lessons' => ['present', 'array', 'max:500'],
            'sections.*.lessons.*' => ['integer', 'distinct'],
        ];
    }
}
