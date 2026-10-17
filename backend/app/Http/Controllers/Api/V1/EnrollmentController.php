<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EnrollmentResource;
use App\Models\Course;
use App\Services\EnrollmentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EnrollmentController extends Controller
{
    public function __construct(private readonly EnrollmentService $enrollments) {}

    /** POST /courses/{course}/enroll — free courses only. */
    public function store(Request $request, Course $course): JsonResponse
    {
        $enrollment = $this->enrollments->enrollFree($request->user(), $course);

        return ApiResponse::created(
            new EnrollmentResource($enrollment->load('course')),
            "You're enrolled! Start learning whenever you're ready."
        );
    }

    /** GET /my/courses?status=in_progress|completed */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['in_progress', 'completed'])],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);

        return ApiResponse::success(EnrollmentResource::collection(
            $this->enrollments->studentEnrollments($request->user(), $filters)
        ));
    }
}
