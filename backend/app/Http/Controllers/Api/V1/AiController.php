<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\AskAssistantRequest;
use App\Http\Requests\Ai\CourseDescriptionRequest;
use App\Http\Requests\Ai\GenerateQuizRequest;
use App\Http\Resources\QuizResource;
use App\Models\Course;
use App\Models\Lesson;
use App\Services\AiService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * AI features. The Claude API key never leaves the server: the browser only
 * talks to these endpoints, which are rate limited per user.
 */
class AiController extends Controller
{
    public function __construct(private readonly AiService $ai) {}

    /** GET /ai/usage — whether AI is available and today's remaining requests. */
    public function usage(Request $request): JsonResponse
    {
        return ApiResponse::success($this->ai->usage($request->user()));
    }

    /** POST /instructor/lessons/{lesson}/ai/quiz — creates a DRAFT quiz to review. */
    public function generateQuiz(GenerateQuizRequest $request, Lesson $lesson): JsonResponse
    {
        Gate::authorize('update', $lesson->course);

        $quiz = $this->ai->generateQuiz($request->user(), $lesson, $request->validated());

        return ApiResponse::created(
            new QuizResource($quiz),
            'Draft quiz generated. Review the questions, then publish it.'
        );
    }

    /** POST /instructor/ai/course-description — returns suggested copy (not saved). */
    public function courseDescription(CourseDescriptionRequest $request): JsonResponse
    {
        $course = null;
        if ($courseId = $request->validated('course_id')) {
            $course = Course::findOrFail($courseId);
            abort_unless($course->isOwnedBy($request->user()), 404);
        }

        return ApiResponse::success(
            $this->ai->suggestCourseDescription($request->user(), $request->validated(), $course),
            'Here is a suggestion. Edit it as you like before saving.'
        );
    }

    /** POST /learn/{slug}/lessons/{lesson}/assistant — enrolled students (and owner/admin preview). */
    public function ask(AskAssistantRequest $request, Course $course, Lesson $lesson): JsonResponse
    {
        Gate::authorize('learn', $course);
        abort_unless($lesson->course_id === $course->id, 404, 'The requested resource was not found.');

        $answer = $this->ai->askAssistant(
            $request->user(),
            $lesson,
            $request->validated('question'),
            $request->validated('history') ?? [],
        );

        return ApiResponse::success([
            'answer' => $answer,
            'usage' => $this->ai->usage($request->user()),
        ]);
    }
}
