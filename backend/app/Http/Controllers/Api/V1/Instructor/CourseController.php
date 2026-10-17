<?php

namespace App\Http\Controllers\Api\V1\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\CourseListRequest;
use App\Http\Requests\Course\StoreCourseRequest;
use App\Http\Requests\Course\UpdateCourseRequest;
use App\Http\Requests\Course\UpdateCourseThumbnailRequest;
use App\Http\Resources\CourseDetailResource;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Services\CourseCatalogService;
use App\Services\CourseService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** An instructor's own courses. Ownership is enforced by CoursePolicy. */
class CourseController extends Controller
{
    public function __construct(
        private readonly CourseService $courses,
        private readonly CourseCatalogService $catalogue,
    ) {}

    /** GET /instructor/courses */
    public function index(CourseListRequest $request): JsonResponse
    {
        return ApiResponse::success(CourseResource::collection(
            $this->catalogue->instructorCourses($request->user(), $request->validated())
        ));
    }

    /** POST /instructor/courses */
    public function store(StoreCourseRequest $request): JsonResponse
    {
        $course = $this->courses->create($request->user(), $request->validated());

        return ApiResponse::created(new CourseDetailResource($this->catalogue->loadDetail($course)), 'Course created as a draft.');
    }

    /** GET /instructor/courses/{course} */
    public function show(Request $request, Course $course): JsonResponse
    {
        abort_unless($course->isOwnedBy($request->user()), 404);

        return ApiResponse::success(new CourseDetailResource($this->catalogue->loadDetail($course)));
    }

    /** PUT /instructor/courses/{course} */
    public function update(UpdateCourseRequest $request, Course $course): JsonResponse
    {
        $course = $this->courses->update($course, $request->validated());

        return ApiResponse::success(new CourseDetailResource($this->catalogue->loadDetail($course)), 'Course saved.');
    }

    /** POST /instructor/courses/{course}/thumbnail (multipart, field "thumbnail") */
    public function updateThumbnail(UpdateCourseThumbnailRequest $request, Course $course): JsonResponse
    {
        $course = $this->courses->updateThumbnail($course, $request->file('thumbnail'));

        return ApiResponse::success(new CourseDetailResource($this->catalogue->loadDetail($course)), 'Thumbnail updated.');
    }

    /** POST /instructor/courses/{course}/submit */
    public function submit(Course $course): JsonResponse
    {
        Gate::authorize('submit', $course);

        $course = $this->courses->submitForReview($course);

        return ApiResponse::success(
            new CourseDetailResource($this->catalogue->loadDetail($course)),
            'Course submitted for review. An admin will check it shortly.'
        );
    }

    /** DELETE /instructor/courses/{course} */
    public function destroy(Course $course): JsonResponse
    {
        Gate::authorize('delete', $course);

        $this->courses->delete($course);

        return ApiResponse::success(null, 'Course deleted.');
    }
}
