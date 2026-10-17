<?php

namespace App\Http\Controllers\Api\V1\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Curriculum\ReorderCurriculumRequest;
use App\Http\Requests\Curriculum\SaveLessonRequest;
use App\Http\Requests\Curriculum\SaveSectionRequest;
use App\Http\Requests\Curriculum\UploadLessonVideoRequest;
use App\Http\Resources\LessonResource;
use App\Http\Resources\SectionResource;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Services\CurriculumService;
use App\Services\LessonVideoService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Course builder: sections, lessons, videos and ordering.
 * Every action requires permission to update the parent course
 * (CoursePolicy::update — owner, and not locked for review).
 */
class CurriculumController extends Controller
{
    public function __construct(
        private readonly CurriculumService $curriculum,
        private readonly LessonVideoService $videos,
    ) {}

    /** GET /instructor/courses/{course}/curriculum */
    public function show(Request $request, Course $course): JsonResponse
    {
        abort_unless($course->isOwnedBy($request->user()), 404);

        return ApiResponse::success(SectionResource::collection($this->curriculum->load($course)->sections));
    }

    /** PUT /instructor/courses/{course}/curriculum/order */
    public function reorder(ReorderCurriculumRequest $request, Course $course): JsonResponse
    {
        Gate::authorize('update', $course);

        $this->curriculum->reorder($course, $request->validated('sections'));

        return ApiResponse::success(SectionResource::collection($this->curriculum->load($course)->sections), 'Order saved.');
    }

    // ------------------------------------------------------------------ Sections

    /** POST /instructor/courses/{course}/sections */
    public function storeSection(SaveSectionRequest $request, Course $course): JsonResponse
    {
        Gate::authorize('update', $course);

        $section = $this->curriculum->createSection($course, $request->validated());

        return ApiResponse::created(new SectionResource($section->load('lessons')), 'Section added.');
    }

    /** PUT /instructor/sections/{section} */
    public function updateSection(SaveSectionRequest $request, Section $section): JsonResponse
    {
        Gate::authorize('update', $section->course);

        $section = $this->curriculum->updateSection($section, $request->validated());

        return ApiResponse::success(new SectionResource($section), 'Section renamed.');
    }

    /** DELETE /instructor/sections/{section} */
    public function destroySection(Section $section): JsonResponse
    {
        Gate::authorize('update', $section->course);

        $this->curriculum->deleteSection($section);

        return ApiResponse::success(null, 'Section and its lessons deleted.');
    }

    // ------------------------------------------------------------------ Lessons

    /** POST /instructor/sections/{section}/lessons */
    public function storeLesson(SaveLessonRequest $request, Section $section): JsonResponse
    {
        Gate::authorize('update', $section->course);

        $lesson = $this->curriculum->createLesson($section, $request->validated());

        return ApiResponse::created(new LessonResource($lesson), 'Lesson added.');
    }

    /** GET /instructor/lessons/{lesson} */
    public function showLesson(Request $request, Lesson $lesson): JsonResponse
    {
        abort_unless($lesson->course->isOwnedBy($request->user()), 404);

        return ApiResponse::success(new LessonResource($lesson));
    }

    /** PUT /instructor/lessons/{lesson} */
    public function updateLesson(SaveLessonRequest $request, Lesson $lesson): JsonResponse
    {
        Gate::authorize('update', $lesson->course);

        $lesson = $this->curriculum->updateLesson($lesson, $request->validated());

        return ApiResponse::success(new LessonResource($lesson), 'Lesson saved.');
    }

    /** DELETE /instructor/lessons/{lesson} */
    public function destroyLesson(Lesson $lesson): JsonResponse
    {
        Gate::authorize('update', $lesson->course);

        $this->curriculum->deleteLesson($lesson);

        return ApiResponse::success(null, 'Lesson deleted.');
    }

    /** POST /instructor/lessons/{lesson}/video (multipart: video, duration_seconds) */
    public function uploadVideo(UploadLessonVideoRequest $request, Lesson $lesson): JsonResponse
    {
        Gate::authorize('update', $lesson->course);

        $lesson = $this->videos->store($lesson, $request->file('video'), $request->validated('duration_seconds'));

        return ApiResponse::success(new LessonResource($lesson), 'Video uploaded.');
    }

    /** DELETE /instructor/lessons/{lesson}/video */
    public function destroyVideo(Lesson $lesson): JsonResponse
    {
        Gate::authorize('update', $lesson->course);

        return ApiResponse::success(new LessonResource($this->videos->remove($lesson)), 'Video removed.');
    }
}
