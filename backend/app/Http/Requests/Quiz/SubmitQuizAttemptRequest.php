<?php

namespace App\Http\Requests\Quiz;

use Illuminate\Foundation\Http\FormRequest;

/** answers: { "<question id>": <chosen option index or null>, ... } */
class SubmitQuizAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => ['present', 'array', 'max:50'],
            'answers.*' => ['nullable', 'integer', 'between:0,5'],
        ];
    }
}
