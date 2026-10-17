<?php

namespace App\Services;

use App\Events\CourseCompleted;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lesson completion, course progress percentage and the "resume" point.
 *
 * progress_percent is cached on the enrollment and recalculated from
 * lesson_progress whenever a lesson is (un)completed.
 */
class ProgressService
{
    /** Lessons in learning order: section position, then lesson position. */
    public function orderedLessons(Course $course): Collection
    {
        return Lesson::query()
            ->join('sections', 'sections.id', '=', 'lessons.section_id')
            ->where('lessons.course_id', $course->id)
            ->orderBy('sections.position')
            ->orderBy('lessons.position')
            ->select('lessons.*')
            ->get();
    }

    /** @return array<int, int> ids of lessons this enrollment has completed */
    public function completedLessonIds(Enrollment $enrollment): array
    {
        return $enrollment->lessonProgress()->pluck('lesson_id')->all();
    }

    /** Remembers where the student is, for "continue where you left off". */
    public function recordVisit(Enrollment $enrollment, Lesson $lesson): void
    {
        $enrollment->forceFill([
            'last_lesson_id' => $lesson->id,
            'last_accessed_at' => now(),
        ])->save();
    }

    /**
     * Marks a lesson complete (idempotent) and recalculates progress.
     * Fires CourseCompleted the first time the course reaches 100%.
     */
    public function complete(Enrollment $enrollment, Lesson $lesson): Enrollment
    {
        $justCompleted = DB::transaction(function () use ($enrollment, $lesson) {
            $enrollment->lessonProgress()->firstOrCreate(
                ['lesson_id' => $lesson->id],
                ['completed_at' => now()],
            );

            return $this->recalculate($enrollment);
        });

        if ($justCompleted) {
            CourseCompleted::dispatch($enrollment);
        }

        return $enrollment;
    }

    /** Un-marks a lesson. A course that was finished stays finished (its certificate stays valid). */
    public function uncomplete(Enrollment $enrollment, Lesson $lesson): Enrollment
    {
        DB::transaction(function () use ($enrollment, $lesson) {
            $enrollment->lessonProgress()->where('lesson_id', $lesson->id)->delete();
            $this->recalculate($enrollment);
        });

        return $enrollment;
    }

    /**
     * Recomputes progress_percent. Returns true when this call is the one that
     * completed the course for the first time.
     */
    public function recalculate(Enrollment $enrollment): bool
    {
        $total = Lesson::where('course_id', $enrollment->course_id)->count();
        $done = $enrollment->lessonProgress()
            ->whereIn('lesson_id', Lesson::where('course_id', $enrollment->course_id)->select('id'))
            ->count();

        $percent = $total === 0 ? 0 : (int) floor($done / $total * 100);
        $finishedNow = $total > 0 && $done === $total && $enrollment->completed_at === null;

        $enrollment->forceFill([
            'progress_percent' => $percent,
            'completed_at' => $finishedNow ? now() : $enrollment->completed_at,
            'last_accessed_at' => now(),
        ])->save();

        return $finishedNow;
    }

    /**
     * The lesson to open when the student returns: their last visited lesson,
     * otherwise the first lesson they haven't completed, otherwise the first.
     */
    public function resumeLessonId(Enrollment $enrollment, Collection $orderedLessons): ?int
    {
        if ($enrollment->last_lesson_id && $orderedLessons->contains('id', $enrollment->last_lesson_id)) {
            return $enrollment->last_lesson_id;
        }

        $completed = $this->completedLessonIds($enrollment);

        return $orderedLessons->first(fn ($lesson) => ! in_array($lesson->id, $completed, true))?->id
            ?? $orderedLessons->first()?->id;
    }
}
