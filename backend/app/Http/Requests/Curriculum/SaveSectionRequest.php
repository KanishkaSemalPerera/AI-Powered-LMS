<?php

namespace App\Http\Requests\Curriculum;

use Illuminate\Foundation\Http\FormRequest;

/** Create/rename a section. Course ownership is checked in the controller. */
class SaveSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['title' => trim((string) $this->input('title'))]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:2', 'max:150'],
        ];
    }
}
