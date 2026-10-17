<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LessonResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use App\Services\CurriculumService;
use App\Services\ProgressService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Course player. Enrolled students learn and track progress; the course owner
 * and admins can open the player in read-only preview mode (no progress).
 */
class LearnController extends Controller
{
    public function __construct(
        private readonly ProgressService $progress,
        private readonly CurriculumService $curriculum,
    ) {}

    /** GET /learn/{slug} — curriculum with completion state and the lesson to resume. */
    public function show(Request $request, Course $course): JsonResponse
    {
        Gate::authorize('learn', $course);

        $enrollment = $this->enrollmentFor($request->user(), $course);
        $ordered = $this->progress->orderedLessons($course);
        $completed = $enrollment ? $this->progress->completedLessonIds($enrollment) : [];

        $course = $this->curriculum->load($course)->load('instructor');

        return ApiResponse::success([
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'instructor' => ['id' => $course->instructor->id, 'name' => $course->instructor->name],
            ],
            'mode' => $enrollment ? 'student' : 'preview',
            'sections' => $course->sections->map(fn ($section) => [
                'id' => $section->id,
                'title' => $section->title,
                'lessons' => $section->lessons->map(fn ($lesson) => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'type' => $lesson->type->value,
                    'duration_seconds' => $lesson->duration_seconds,
                    'is_completed' => in_array($lesson->id, $completed, true),
                ])->values(),
            ])->values(),
            'progress' => $this->progressPayload($enrollment, $completed, $ordered),
        ]);
    }

    /** GET /learn/{slug}/lessons/{lesson} — full lesson; also updates the resume point. */
    public function lesson(Request $request, Course $course, Lesson $lesson): JsonResponse
    {
        Gate::authorize('learn', $course);
        abort_unless($lesson->course_id === $course->id, 404, 'The requested resource was not found.');

        $enrollment = $this->enrollmentFor($request->user(), $course);
        if ($enrollment) {
            $this->progress->recordVisit($enrollment, $lesson);
        }

        $ordered = $this->progress->orderedLessons($course)->pluck('id')->values();
        $index = $ordered->search($lesson->id);

        return ApiResponse::success([
            'lesson' => new LessonResource($lesson),
            'is_completed' => $enrollment
                ? $enrollment->lessonProgress()->where('lesson_id', $lesson->id)->exists()
                : false,
            'navigation' => [
                'index' => $index + 1,
                'total' => $ordered->count(),
                'previous_lesson_id' => $index > 0 ? $ordered[$index - 1] : null,
                'next_lesson_id' => $ordered[$index + 1] ?? null,
            ],
        ]);
    }

    /** POST /learn/{slug}/lessons/{lesson}/complete */
    public function complete(Request $request, Course $course, Lesson $lesson): JsonResponse
    {
        [$enrollment, $lesson] = $this->studentContext($request, $course, $lesson);

        $wasComplete = $enrollment->completed_at !== null;
        $this->progress->complete($enrollment, $lesson);

        $payload = $this->progressPayload($enrollment->fresh(), null, $this->progress->orderedLessons($course));
        $payload['course_completed_now'] = ! $wasComplete && $enrollment->fresh()->completed_at !== null;

        return ApiResponse::success($payload, $payload['course_completed_now']
            ? 'Congratulations — you completed the course!'
            : 'Lesson marked as complete.');
    }

    /** DELETE /learn/{slug}/lessons/{lesson}/complete */
    public function uncomplete(Request $request, Course $course, Lesson $lesson): JsonResponse
    {
        [$enrollment, $lesson] = $this->studentContext($request, $course, $lesson);

        $this->progress->uncomplete($enrollment, $lesson);

        return ApiResponse::success(
            $this->progressPayload($enrollment->fresh(), null, $this->progress->orderedLessons($course)),
            'Lesson marked as not complete.'
        );
    }

    /** GET /courses/{slug}/lessons/{lesson}/preview — public free-preview lessons. */
    public function preview(Course $course, Lesson $lesson): JsonResponse
    {
        abort_unless(
            $course->isPublished() && $lesson->course_id === $course->id && $lesson->is_preview,
            404,
            'The requested resource was not found.'
        );

        return ApiResponse::success(new LessonResource($lesson));
    }

    // ------------------------------------------------------------------ helpers

    private function enrollmentFor(User $user, Course $course): ?Enrollment
    {
        return Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->first();
    }

    /** Progress changes need a real enrollment (not owner/admin preview). */
    private function studentContext(Request $request, Course $course, Lesson $lesson): array
    {
        abort_unless($lesson->course_id === $course->id, 404, 'The requested resource was not found.');

        $enrollment = $this->enrollmentFor($request->user(), $course);
        abort_unless($enrollment, 403, 'Enroll in this course to track your progress.');

        return [$enrollment, $lesson];
    }

    private function progressPayload(?Enrollment $enrollment, ?array $completed, Collection $ordered): ?array
    {
        if (! $enrollment) {
            return null;
        }

        $completed ??= $this->progress->completedLessonIds($enrollment);

        return [
            'progress_percent' => $enrollment->progress_percent,
            'completed_lesson_ids' => array_values($completed),
            'completed_lessons' => count(array_intersect($completed, $ordered->pluck('id')->all())),
            'total_lessons' => $ordered->count(),
            'completed_at' => $enrollment->completed_at?->toIso8601String(),
            'resume_lesson_id' => $this->progress->resumeLessonId($enrollment, $ordered),
        ];
    }
}
