<?php

namespace App\Http\Requests\Quiz;

use App\Enums\QuizStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Full quiz payload from the quiz editor (questions are replaced as a whole). */
class SaveQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'pass_mark' => ['required', 'integer', 'between:1,100'],
            'status' => ['required', Rule::in(QuizStatus::values())],
            'questions' => ['present', 'array', 'max:50'],
            'questions.*.question' => ['required', 'string', 'min:5', 'max:1000'],
            'questions.*.options' => ['required', 'array', 'min:2', 'max:6'],
            'questions.*.options.*' => ['required', 'string', 'max:300'],
            'questions.*.correct_option' => ['required', 'integer', 'min:0'],
            'questions.*.explanation' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** correct_option must point at one of that question's options; options must be distinct. */
    public function after(): array
    {
        return [function (Validator $validator) {
            foreach ((array) $this->input('questions', []) as $i => $question) {
                $options = array_map(fn ($o) => mb_strtolower(trim((string) $o)), (array) ($question['options'] ?? []));
                $correct = $question['correct_option'] ?? null;

                if (is_numeric($correct) && ((int) $correct < 0 || (int) $correct >= count($options))) {
                    $validator->errors()->add("questions.{$i}.correct_option", 'Choose which option is correct.');
                }
                if (count($options) !== count(array_unique($options))) {
                    $validator->errors()->add("questions.{$i}.options", 'Each option must be different.');
                }
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'questions.*.question' => 'question',
            'questions.*.options' => 'options',
            'questions.*.options.*' => 'option',
            'questions.*.correct_option' => 'correct answer',
        ];
    }
}
