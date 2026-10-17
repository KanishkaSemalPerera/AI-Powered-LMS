<?php

namespace App\Http\Requests\Curriculum;

use Illuminate\Foundation\Http\FormRequest;

class UploadLessonVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // mimetypes inspects the real file contents, not just the extension.
            'video' => [
                'required', 'file',
                'mimetypes:'.implode(',', config('lms.video_mimetypes')),
                'max:'.config('lms.video_max_kb'),
            ],
            // Read by the browser from the file's metadata before uploading.
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
        ];
    }

    public function messages(): array
    {
        $maxMb = intdiv(config('lms.video_max_kb'), 1024);

        return [
            'video.mimetypes' => 'The video must be an MP4, WebM or MOV file.',
            'video.max' => "The video must be smaller than {$maxMb} MB.",
            'video.uploaded' => "The upload failed. Make sure the file is under {$maxMb} MB and PHP's upload limits allow it.",
        ];
    }
}
