<?php

namespace App\Http\Requests\Course;

use Illuminate\Support\Facades\Gate;

/** Same rules as creating; ownership and "not under review" are checked by CoursePolicy::update. */
class UpdateCourseRequest extends StoreCourseRequest
{
    public function authorize(): bool
    {
        // Gate::authorize surfaces the policy's explanation (e.g. "under review") in the 403.
        Gate::authorize('update', $this->route('course'));

        return true;
    }
}
