<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class RejectCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('review', $this->route('course'));

        return true;
    }

    public function rules(): array
    {
        return [
            // The instructor needs to know what to fix.
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
