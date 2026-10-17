<?php

namespace App\Http\Requests\Curriculum;

use App\Enums\LessonType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create/update a lesson. On update every field is optional ("sometimes").
 * Uploaded videos go through a separate endpoint (UploadLessonVideoRequest).
 */
class SaveLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('title')) {
            $this->merge(['title' => trim((string) $this->input('title'))]);
        }
        if ($this->input('video_url') === '') {
            $this->merge(['video_url' => null]);
        }
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'title' => [$required, 'string', 'min:2', 'max:150'],
            'type' => [$required, Rule::in(LessonType::values())],
            // External video: YouTube, Vimeo or a direct https link to a video file.
            'video_url' => ['nullable', 'url:https,http', 'max:500'],
            'content' => ['nullable', 'string', 'max:100000'],
            'duration_minutes' => ['sometimes', 'numeric', 'min:0', 'max:1440'],
            'is_preview' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'video_url.url' => 'Enter a full video link starting with https://',
        ];
    }
}
