<?php

namespace App\Events;

use App\Models\Enrollment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired once, the first time a student reaches 100% of a course.
 * The certificate listener subscribes to this event.
 */
class CourseCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Enrollment $enrollment) {}
}
