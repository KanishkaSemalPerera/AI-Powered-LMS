<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'num_questions' => ['required', 'integer', 'between:1,15'],
            'difficulty' => ['required', Rule::in(['easy', 'medium', 'hard'])],
            // Optional pasted material; when empty the lesson's own content is used.
            'source_text' => ['nullable', 'string', 'min:100', 'max:30000'],
            'replace' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'source_text.max' => 'The pasted material is too long (30,000 characters max). Use the most relevant part.',
        ];
    }
}
