<?php

namespace App\Policies;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Who may see and change a course. Route middleware already limits instructor
 * and admin routes by role; the policy adds ownership and workflow rules.
 */
class CoursePolicy
{
    /** Published courses are public; drafts/pending/rejected only for the owner and admins. */
    public function view(?User $user, Course $course): bool
    {
        return $course->isPublished()
            || $course->isOwnedBy($user)
            || (bool) $user?->isAdmin();
    }

    /**
     * Full lesson content and videos: enrolled students, plus the course
     * owner and admins (who use the player as a read-only preview).
     */
    public function learn(User $user, Course $course): Response
    {
        // Enrolled students keep access even if the course is later unpublished.
        return $course->isOwnedBy($user) || $user->isAdmin() || $user->isEnrolledIn($course)
            ? Response::allow()
            : Response::deny('Enroll in this course to access its lessons.');
    }

    public function create(User $user): bool
    {
        return $user->isInstructor();
    }

    public function update(User $user, Course $course): Response
    {
        if (! $course->isOwnedBy($user)) {
            return Response::deny('You can only edit your own courses.');
        }

        return $course->status->isEditable()
            ? Response::allow()
            : Response::deny('This course is under review and cannot be edited until an admin has reviewed it.');
    }

    public function delete(User $user, Course $course): Response
    {
        if (! $course->isOwnedBy($user)) {
            return Response::deny('You can only delete your own courses.');
        }

        if (! in_array($course->status, [CourseStatus::Draft, CourseStatus::Rejected], true)) {
            return Response::deny('Only draft or rejected courses can be deleted.');
        }

        // Protect learners who already have access (and their payment history).
        return $course->enrollments()->exists()
            ? Response::deny('This course has enrolled students and cannot be deleted.')
            : Response::allow();
    }

    public function submit(User $user, Course $course): Response
    {
        if (! $course->isOwnedBy($user)) {
            return Response::deny('You can only submit your own courses.');
        }

        return in_array($course->status, [CourseStatus::Draft, CourseStatus::Rejected], true)
            ? Response::allow()
            : Response::deny('Only draft or rejected courses can be submitted for review.');
    }

    /** Admin decisions are only possible while a course awaits review. */
    public function review(User $user, Course $course): Response
    {
        if (! $user->isAdmin()) {
            return Response::deny('Only admins can review courses.');
        }

        return $course->status === CourseStatus::Pending
            ? Response::allow()
            : Response::deny('Only courses pending review can be approved or rejected.');
    }
}
