<?php

namespace App\Http\Controllers\Api\V1\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Quiz\SaveQuizRequest;
use App\Http\Resources\QuizResource;
use App\Models\Lesson;
use App\Services\QuizService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** A lesson's quiz in the instructor's editor (one quiz per lesson). */
class QuizController extends Controller
{
    public function __construct(private readonly QuizService $quizzes) {}

    /** GET /instructor/lessons/{lesson}/quiz → quiz with answer key, or null */
    public function show(Request $request, Lesson $lesson): JsonResponse
    {
        abort_unless($lesson->course->isOwnedBy($request->user()), 404);

        $quiz = $lesson->quiz()->with('questions')->first();

        return ApiResponse::success([
            'lesson' => ['id' => $lesson->id, 'title' => $lesson->title, 'course_id' => $lesson->course_id],
            'quiz' => $quiz ? (new QuizResource($quiz))->resolve($request) : null,
        ]);
    }

    /** PUT /instructor/lessons/{lesson}/quiz — create or replace. */
    public function upsert(SaveQuizRequest $request, Lesson $lesson): JsonResponse
    {
        Gate::authorize('update', $lesson->course);

        $existed = $lesson->quiz()->exists();
        $quiz = $this->quizzes->save($lesson, $request->validated());

        return ApiResponse::success(new QuizResource($quiz), $existed ? 'Quiz saved.' : 'Quiz created.', $existed ? 200 : 201);
    }

    /** DELETE /instructor/lessons/{lesson}/quiz */
    public function destroy(Lesson $lesson): JsonResponse
    {
        Gate::authorize('update', $lesson->course);

        $quiz = $lesson->quiz()->firstOrFail();
        $this->quizzes->delete($quiz);

        return ApiResponse::success(null, 'Quiz deleted.');
    }
}
