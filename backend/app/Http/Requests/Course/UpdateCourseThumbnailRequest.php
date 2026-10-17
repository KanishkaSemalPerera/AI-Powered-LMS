<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateCourseThumbnailRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Gate::authorize surfaces the policy's explanation (e.g. "under review") in the 403.
        Gate::authorize('update', $this->route('course'));

        return true;
    }

    public function rules(): array
    {
        return [
            // Real image validation (content sniffing), max 4 MB, at least 640×360 for sharp cards.
            'thumbnail' => [
                'required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096',
                'dimensions:min_width=640,min_height=360,max_width=6000,max_height=6000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'thumbnail.max' => 'The thumbnail must be smaller than 4 MB.',
            'thumbnail.mimes' => 'The thumbnail must be a JPG, PNG or WebP image.',
            'thumbnail.dimensions' => 'The thumbnail must be at least 640×360 pixels.',
        ];
    }
}
