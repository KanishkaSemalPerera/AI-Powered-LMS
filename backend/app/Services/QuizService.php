<?php

namespace App\Services;

use App\Enums\QuizStatus;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Quiz authoring (instructor) and attempts with server-side scoring (student).
 * The answer key never leaves the server until an attempt has been submitted.
 */
class QuizService
{
    public function __construct(private readonly ProgressService $progress) {}

    /**
     * Creates or replaces a lesson's quiz, including all its questions, in one
     * transaction. Used by the quiz editor and by the AI quiz generator.
     *
     * @param  array{title: string, description?: ?string, pass_mark: int, status: string,
     *               is_ai_generated?: bool, questions: array<int, array>}  $data
     */
    public function save(Lesson $lesson, array $data): Quiz
    {
        if ($data['status'] === QuizStatus::Published->value && empty($data['questions'])) {
            throw ValidationException::withMessages(['questions' => ['Add at least one question before publishing.']]);
        }

        return DB::transaction(function () use ($lesson, $data) {
            $quiz = $lesson->quiz()->firstOrNew();
            $quiz->fill([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'pass_mark' => $data['pass_mark'],
                'status' => $data['status'],
            ]);
            if (array_key_exists('is_ai_generated', $data)) {
                $quiz->is_ai_generated = (bool) $data['is_ai_generated'];
            }
            $quiz->save();

            // Questions are replaced as a whole; past attempts keep their own snapshot of answers.
            $quiz->questions()->delete();
            foreach (array_values($data['questions']) as $index => $question) {
                $quiz->questions()->create([
                    'question' => $question['question'],
                    'options' => array_values($question['options']),
                    'correct_option' => $question['correct_option'],
                    'explanation' => $question['explanation'] ?? null,
                    'position' => $index + 1,
                ]);
            }

            return $quiz->load('questions');
        });
    }

    public function delete(Quiz $quiz): void
    {
        $quiz->delete();
    }

    /**
     * Scores a submission. Unanswered questions count as wrong. Passing the quiz
     * also marks its lesson complete for the student.
     *
     * @param  array<int|string, int|null>  $answers  question id => chosen option index
     */
    public function submit(User $user, Quiz $quiz, Enrollment $enrollment, array $answers): QuizAttempt
    {
        $questions = $quiz->questions()->get();
        abort_if($questions->isEmpty(), 422, 'This quiz has no questions yet.');

        $unknown = array_diff(array_map('intval', array_keys($answers)), $questions->pluck('id')->all());
        if ($unknown) {
            throw ValidationException::withMessages(['answers' => ['Some answers do not belong to this quiz.']]);
        }

        $recorded = [];
        $correct = 0;
        foreach ($questions as $question) {
            $choice = $answers[$question->id] ?? null;
            $choice = is_numeric($choice) && (int) $choice >= 0 && (int) $choice < count($question->options) ? (int) $choice : null;
            $recorded[$question->id] = $choice;
            $correct += (int) ($choice === $question->correct_option);
        }

        $percent = (int) round($correct / $questions->count() * 100);

        $attempt = new QuizAttempt([
            'answers' => $recorded,
            'correct_count' => $correct,
            'total_questions' => $questions->count(),
            'score_percent' => $percent,
            'passed' => $percent >= $quiz->pass_mark,
        ]);
        $attempt->user()->associate($user);
        $attempt->quiz()->associate($quiz);
        $attempt->save();

        if ($attempt->passed) {
            $this->progress->complete($enrollment, $quiz->lesson);
        }

        return $attempt->setRelation('quiz', $quiz->setRelation('questions', $questions));
    }

    public function history(User $user, Quiz $quiz, int $perPage = 10): LengthAwarePaginator
    {
        return $quiz->attempts()->where('user_id', $user->id)->latest()->latest('id')->paginate($perPage);
    }

    /** @return array{attempts: int, best_score: ?int, passed: bool} */
    public function summaryFor(User $user, Quiz $quiz): array
    {
        $stats = $quiz->attempts()->where('user_id', $user->id)
            ->selectRaw('COUNT(*) as attempts, MAX(score_percent) as best, MAX(CASE WHEN passed THEN 1 ELSE 0 END) as passed')
            ->first();

        return [
            'attempts' => (int) $stats->attempts,
            'best_score' => $stats->best !== null ? (int) $stats->best : null,
            'passed' => (bool) $stats->passed,
        ];
    }
}
