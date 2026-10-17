<?php

namespace App\Http\Requests\Course;

use App\Enums\CourseLevel;
use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creating a course only requires a title; everything else can be filled in
 * before submitting for review (see CourseService::assertReadyForReview).
 */
class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Course::class);
    }

    protected function prepareForValidation(): void
    {
        // Drop empty bullet points from list fields.
        foreach (['what_you_will_learn', 'requirements'] as $field) {
            if (is_array($this->input($field))) {
                $this->merge([$field => array_values(array_filter(
                    array_map(fn ($item) => is_string($item) ? trim($item) : $item, $this->input($field)),
                    fn ($item) => $item !== '' && $item !== null
                ))]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'level' => ['sometimes', Rule::in(CourseLevel::values())],
            'language' => ['sometimes', 'string', 'max:50'],
            // 0 = free. Stripe requires paid prices of at least $0.50.
            'price' => ['sometimes', 'numeric', 'min:0', 'max:9999.99', function ($attribute, $value, $fail) {
                if ($value > 0 && $value < 0.5) {
                    $fail('Paid courses must cost at least 0.50. Use 0 for a free course.');
                }
            }],
            'what_you_will_learn' => ['nullable', 'array', 'max:12'],
            'what_you_will_learn.*' => ['string', 'max:200'],
            'requirements' => ['nullable', 'array', 'max:12'],
            'requirements.*' => ['string', 'max:200'],
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'category',
            'what_you_will_learn.*' => 'learning outcome',
            'requirements.*' => 'requirement',
        ];
    }
}
