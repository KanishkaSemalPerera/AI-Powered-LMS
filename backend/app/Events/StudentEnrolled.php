<?php

namespace App\Events;

use App\Models\Enrollment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired once when a student gains access to a course (free or paid).
 * Notification listeners subscribe to this event.
 */
class StudentEnrolled
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Enrollment $enrollment) {}
}
