<?php

namespace App\Services;

use App\Enums\CourseStatus;
use App\Enums\LessonType;
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

    public function __construct(private readonly LessonVideoService $videos) {}

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
        $thumbnail = $course->thumbnail_path;

        DB::transaction(fn () => $course->delete());

        // Files are removed only after the database delete succeeded.
        if ($thumbnail) {
            Storage::disk(config('filesystems.default'))->delete($thumbnail);
        }
        $this->videos->removeCourseVideos($course->id);
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
        $lessons = $course->lessons()->get(['id', 'title', 'type', 'video_path', 'video_url', 'content']);

        if ($lessons->isEmpty()) {
            $missing['lessons'] = ['Add at least one lesson to the curriculum.'];
        } else {
            // Video lessons need a video; text lessons need content.
            $incomplete = $lessons->filter(fn ($lesson) => $lesson->type === LessonType::Video
                ? blank($lesson->video_path) && blank($lesson->video_url)
                : blank(trim(strip_tags((string) $lesson->content))));

            if ($incomplete->isNotEmpty()) {
                $missing['lessons'] = ['These lessons are empty: '.$incomplete->pluck('title')->map(fn ($t) => "“{$t}”")->join(', ').'.'];
            }
        }

        if ($missing) {
            throw ValidationException::withMessages($missing);
        }
    }
}
