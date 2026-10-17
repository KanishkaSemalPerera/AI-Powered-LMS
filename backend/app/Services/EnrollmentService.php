<?php

namespace App\Services;

use App\Events\StudentEnrolled;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Grants course access. Free courses enroll directly; paid courses enroll
 * only through PaymentService once a payment is confirmed.
 */
class EnrollmentService
{
    /** Checks shared by free enrollment and checkout. Aborts with a clear message. */
    public function assertCanEnroll(User $user, Course $course): void
    {
        abort_unless($user->isStudent(), 403, 'Only student accounts can enroll in courses.');
        abort_unless($course->isPublished(), 404, 'The requested resource was not found.');
        abort_if($course->isOwnedBy($user), 403, 'You cannot enroll in your own course.');
        abort_if($user->isEnrolledIn($course), 409, 'You are already enrolled in this course.');
    }

    public function enrollFree(User $user, Course $course): Enrollment
    {
        $this->assertCanEnroll($user, $course);
        abort_unless($course->isFree(), 402, 'This is a paid course. Please complete checkout to enroll.');

        return $this->grantAccess($user, $course);
    }

    /**
     * Creates the enrollment exactly once. Safe to call repeatedly (e.g. from
     * both the webhook and the success-page confirmation).
     */
    public function grantAccess(User $user, Course $course, ?Payment $payment = null): Enrollment
    {
        $existing = Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->first();
        if ($existing) {
            if ($payment && ! $existing->payment_id) {
                $existing->forceFill(['payment_id' => $payment->id])->save();
            }

            return $existing;
        }

        try {
            $enrollment = Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'payment_id' => $payment?->id,
            ]);
        } catch (UniqueConstraintViolationException) {
            // A parallel request won the race; return its enrollment.
            return Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->firstOrFail();
        }

        $course->refreshStudentsCount();
        StudentEnrolled::dispatch($enrollment);

        return $enrollment;
    }

    /** Removes access, e.g. after a refund. */
    public function revokeAccess(Enrollment $enrollment): void
    {
        $course = $enrollment->course;
        $enrollment->delete();
        $course?->refreshStudentsCount();
    }

    public function studentEnrollments(User $user, array $filters = []): LengthAwarePaginator
    {
        return $user->enrollments()
            ->with(['course' => fn ($q) => $q->with(['instructor', 'category'])->withCount('lessons')->withSum('lessons', 'duration_seconds')])
            ->when(($filters['status'] ?? null) === 'completed', fn ($q) => $q->whereNotNull('completed_at'))
            ->when(($filters['status'] ?? null) === 'in_progress', fn ($q) => $q->whereNull('completed_at'))
            ->orderByRaw('COALESCE(last_accessed_at, created_at) DESC')
            ->paginate($filters['per_page'] ?? 12)
            ->withQueryString();
    }
}
