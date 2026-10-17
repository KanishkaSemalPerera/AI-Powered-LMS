<?php

namespace App\Services;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Write-side course lifecycle: create, edit, thumbnail, delete, and the
 * draft -> pending -> published/rejected review workflow.
 */
class CourseService
{
    /** Minimum description length before a course can be submitted for review. */
    private const MIN_DESCRIPTION_LENGTH = 100;

    public function create(User $instructor, array $data): Course
    {
        return $instructor->courses()->create($data);
    }

    public function update(Course $course, array $data): Course
    {
        $course->fill($data);

        // A rejected course goes back to draft once the instructor starts fixing it.
        if ($course->status === CourseStatus::Rejected && $course->isDirty()) {
            $course->status = CourseStatus::Draft;
        }

        $course->save();

        return $course;
    }

    public function updateThumbnail(Course $course, UploadedFile $file): Course
    {
        $disk = config('filesystems.default');
        $previous = $course->thumbnail_path;

        $course->forceFill(['thumbnail_path' => $file->store("thumbnails/{$course->id}", $disk)])->save();

        if ($previous && $previous !== $course->thumbnail_path) {
            Storage::disk($disk)->delete($previous);
        }

        return $course;
    }

    public function delete(Course $course): void
    {
        DB::transaction(function () use ($course) {
            $thumbnail = $course->thumbnail_path;
            $course->delete();

            if ($thumbnail) {
                Storage::disk(config('filesystems.default'))->delete($thumbnail);
            }
        });
    }

    /**
     * @throws ValidationException listing what is missing before review is possible
     */
    public function submitForReview(Course $course): Course
    {
        $this->assertReadyForReview($course);

        $course->forceFill([
            'status' => CourseStatus::Pending,
            'submitted_at' => now(),
            'rejection_reason' => null,
        ])->save();

        return $course;
    }

    public function approve(Course $course): Course
    {
        $course->forceFill([
            'status' => CourseStatus::Published,
            'published_at' => $course->published_at ?? now(),
            'rejection_reason' => null,
        ])->save();

        return $course;
    }

    public function reject(Course $course, string $reason): Course
    {
        $course->forceFill([
            'status' => CourseStatus::Rejected,
            'rejection_reason' => $reason,
        ])->save();

        return $course;
    }

    private function assertReadyForReview(Course $course): void
    {
        $missing = [];

        if (! $course->category_id) {
            $missing['category_id'] = ['Choose a category.'];
        }
        if (mb_strlen(strip_tags((string) $course->description)) < self::MIN_DESCRIPTION_LENGTH) {
            $missing['description'] = ['Write a description of at least '.self::MIN_DESCRIPTION_LENGTH.' characters.'];
        }
        if (! $course->lessons()->exists()) {
            $missing['lessons'] = ['Add at least one lesson to the curriculum.'];
        }

        if ($missing) {
            throw ValidationException::withMessages($missing);
        }
    }
}
