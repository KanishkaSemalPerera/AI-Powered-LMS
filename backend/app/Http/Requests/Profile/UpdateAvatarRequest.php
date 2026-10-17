<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // Validates the real file contents (MIME sniffing), not just the extension. Max 2 MB.
            'avatar' => [
                'required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048',
                'dimensions:min_width=64,min_height=64,max_width=4000,max_height=4000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.max' => 'The avatar must be smaller than 2 MB.',
            'avatar.mimes' => 'The avatar must be a JPG, PNG or WebP image.',
            'avatar.dimensions' => 'The avatar must be between 64×64 and 4000×4000 pixels.',
        ];
    }
}
