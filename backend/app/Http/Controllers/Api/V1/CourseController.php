<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Course\CourseCatalogRequest;
use App\Http\Resources\CourseDetailResource;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Services\CourseCatalogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Public course catalogue. */
class CourseController extends Controller
{
    public function __construct(private readonly CourseCatalogService $catalogue) {}

    /** GET /courses — search, filter, sort and paginate published courses. */
    public function index(CourseCatalogRequest $request): JsonResponse
    {
        return ApiResponse::success(CourseResource::collection($this->catalogue->publicCatalogue($request->validated())));
    }

    /**
     * GET /courses/{slug} — public for published courses. The route is open to
     * guests, but a Bearer token (if sent) lets owners and admins preview drafts.
     */
    public function show(Request $request, Course $course): JsonResponse
    {
        $viewer = $request->user('sanctum');
        $request->setUserResolver(fn () => $viewer);

        // Unpublished courses look like they don't exist to everyone else.
        abort_unless(Gate::forUser($viewer)->allows('view', $course), 404);

        $course = $this->catalogue->loadDetail($course);

        // Lets the page show "Go to course" instead of "Enroll" for enrolled students.
        $course->setRelation('viewerEnrollment', $viewer
            ? Enrollment::where('user_id', $viewer->id)->where('course_id', $course->id)->first()
            : null);

        return ApiResponse::success(new CourseDetailResource($course));
    }
}
