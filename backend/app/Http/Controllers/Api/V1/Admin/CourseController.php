<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\CourseListRequest;
use App\Http\Requests\Course\RejectCourseRequest;
use App\Http\Resources\CourseDetailResource;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Services\CourseCatalogService;
use App\Services\CourseService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

/** Course moderation for admins. */
class CourseController extends Controller
{
    public function __construct(
        private readonly CourseService $courses,
        private readonly CourseCatalogService $catalogue,
    ) {}

    /** GET /admin/courses?status=pending */
    public function index(CourseListRequest $request): JsonResponse
    {
        return ApiResponse::success(CourseResource::collection($this->catalogue->adminCourses($request->validated())));
    }

    /** POST /admin/courses/{course}/approve */
    public function approve(Course $course): JsonResponse
    {
        Gate::authorize('review', $course);

        $course = $this->courses->approve($course);

        return ApiResponse::success(new CourseDetailResource($this->catalogue->loadDetail($course)), 'Course approved and published.');
    }

    /** POST /admin/courses/{course}/reject  { reason } */
    public function reject(RejectCourseRequest $request, Course $course): JsonResponse
    {
        $course = $this->courses->reject($course, $request->validated('reason'));

        return ApiResponse::success(new CourseDetailResource($this->catalogue->loadDetail($course)), 'Course rejected. The instructor can see your feedback.');
    }
}
