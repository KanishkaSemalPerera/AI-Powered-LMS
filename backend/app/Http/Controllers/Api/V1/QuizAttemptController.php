<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Quiz\SubmitQuizAttemptRequest;
use App\Http\Resources\QuizAttemptResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuizService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Taking a lesson's quiz inside the course player.
 * Owners/admins can open it (preview); only enrolled students can submit.
 */
class QuizAttemptController extends Controller
{
    public function __construct(private readonly QuizService $quizzes) {}

    /** GET /learn/{slug}/lessons/{lesson}/quiz — questions without answers + my stats */
    public function show(Request $request, Course $course, Lesson $lesson): JsonResponse
    {
        $quiz = $this->publishedQuiz($course, $lesson);
        $quiz->load('questions');

        return ApiResponse::success([
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'pass_mark' => $quiz->pass_mark,
                // The answer key and explanations are deliberately left out.
                'questions' => $quiz->questions->map(fn ($q) => [
                    'id' => $q->id,
                    'question' => $q->question,
                    'options' => $q->options,
                ])->values(),
            ],
            'lesson' => ['id' => $lesson->id, 'title' => $lesson->title],
            'can_submit' => $this->enrollmentFor($request, $course) !== null,
            'summary' => $this->quizzes->summaryFor($request->user(), $quiz),
        ]);
    }

    /** POST /learn/{slug}/lessons/{lesson}/quiz/attempts { answers } */
    public function store(SubmitQuizAttemptRequest $request, Course $course, Lesson $lesson): JsonResponse
    {
        $quiz = $this->publishedQuiz($course, $lesson);
        $enrollment = $this->enrollmentFor($request, $course);
        abort_unless($enrollment, 403, 'Enroll in this course to take its quizzes.');

        $attempt = $this->quizzes->submit($request->user(), $quiz, $enrollment, $request->validated('answers'));

        return ApiResponse::created(
            new QuizAttemptResource($attempt),
            $attempt->passed ? 'Well done — you passed!' : "You scored {$attempt->score_percent}%. Review the answers and try again."
        );
    }

    /** GET /learn/{slug}/lessons/{lesson}/quiz/attempts — my attempt history */
    public function index(Request $request, Course $course, Lesson $lesson): JsonResponse
    {
        $quiz = $this->publishedQuiz($course, $lesson);

        return ApiResponse::success(QuizAttemptResource::collection($this->quizzes->history($request->user(), $quiz)));
    }

    /** GET /quiz-attempts/{attempt} — review one of my past attempts */
    public function showAttempt(Request $request, QuizAttempt $attempt): JsonResponse
    {
        abort_unless($attempt->user_id === $request->user()->id, 404);

        $attempt->load('quiz.questions');

        return ApiResponse::success(new QuizAttemptResource($attempt));
    }

    private function publishedQuiz(Course $course, Lesson $lesson): Quiz
    {
        Gate::authorize('learn', $course);
        abort_unless($lesson->course_id === $course->id, 404, 'The requested resource was not found.');

        $quiz = $lesson->quiz;
        abort_unless($quiz && $quiz->isPublished(), 404, 'This lesson has no quiz.');

        return $quiz;
    }

    private function enrollmentFor(Request $request, Course $course): ?Enrollment
    {
        return Enrollment::where('user_id', $request->user()->id)->where('course_id', $course->id)->first();
    }
}
