<?php

namespace App\Services;

use App\Models\Lesson;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stores uploaded lesson videos privately and produces time-limited playback URLs.
 */
class LessonVideoService
{
    public function store(Lesson $lesson, UploadedFile $file, ?int $durationSeconds = null): Lesson
    {
        $previous = $lesson->video_path;

        $path = $file->store("lesson-videos/{$lesson->course_id}/{$lesson->id}", $this->diskName());

        $lesson->forceFill([
            'video_path' => $path,
            'video_original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'video_size' => $file->getSize(),
            // An uploaded file replaces any external video link.
            'video_url' => null,
            'duration_seconds' => $durationSeconds ?: $lesson->duration_seconds,
        ])->save();

        if ($previous && $previous !== $path) {
            $this->disk()->delete($previous);
        }

        return $lesson;
    }

    public function remove(Lesson $lesson): Lesson
    {
        if ($lesson->video_path) {
            $this->disk()->delete($lesson->video_path);
            $lesson->forceFill(['video_path' => null, 'video_original_name' => null, 'video_size' => null])->save();
        }

        return $lesson;
    }

    /** Deletes every uploaded video of a course (used when a course is deleted). */
    public function removeCourseVideos(int $courseId): void
    {
        $this->disk()->deleteDirectory("lesson-videos/{$courseId}");
    }

    /** @param  iterable<Lesson>  $lessons */
    public function removeFiles(iterable $lessons): void
    {
        $paths = collect($lessons)->pluck('video_path')->filter()->all();
        if ($paths) {
            $this->disk()->delete($paths);
        }
    }

    /**
     * URL a <video> element (or iframe for YouTube/Vimeo links) can play.
     * Uploaded files get a signed URL that expires; external links are returned as-is.
     */
    public function playbackUrl(Lesson $lesson): ?string
    {
        if ($lesson->video_path) {
            $expires = now()->addMinutes(config('lms.video_url_ttl_minutes'));

            return $this->isLocal()
                ? URL::temporarySignedRoute('lessons.video', $expires, ['lesson' => $lesson->id])
                : $this->disk()->temporaryUrl($lesson->video_path, $expires);
        }

        return $lesson->video_url;
    }

    /**
     * Streams a locally stored video. BinaryFileResponse handles HTTP Range
     * requests, so the browser can seek without downloading the whole file.
     */
    public function streamResponse(Lesson $lesson): Response
    {
        abort_unless($lesson->video_path && $this->disk()->exists($lesson->video_path), 404);

        if (! $this->isLocal()) {
            return redirect()->away($this->playbackUrl($lesson));
        }

        return response()->file($this->disk()->path($lesson->video_path), [
            'Content-Type' => $this->disk()->mimeType($lesson->video_path) ?: 'video/mp4',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function diskName(): string
    {
        return config('lms.video_disk');
    }

    private function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    private function isLocal(): bool
    {
        return config("filesystems.disks.{$this->diskName()}.driver") === 'local';
    }
}
