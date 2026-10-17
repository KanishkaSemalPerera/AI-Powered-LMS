<?php

namespace App\Services;

use App\Enums\LessonType;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Sections and lessons of a course: create, edit, delete and reorder.
 * Callers must already have authorised the user to edit the course.
 */
class CurriculumService
{
    public function __construct(private readonly LessonVideoService $videos) {}

    /** Sections with their lessons, ordered for display. */
    public function load(Course $course): Course
    {
        return $course->load([
            'sections' => fn ($q) => $q->orderBy('position'),
            'sections.lessons' => fn ($q) => $q->orderBy('position'),
            'sections.lessons.quiz' => fn ($q) => $q->withCount('questions'),
        ]);
    }

    // ------------------------------------------------------------------ Sections

    public function createSection(Course $course, array $data): Section
    {
        return $course->sections()->create([
            'title' => $data['title'],
            'position' => (int) $course->sections()->max('position') + 1,
        ]);
    }

    public function updateSection(Section $section, array $data): Section
    {
        $section->update(['title' => $data['title']]);

        return $section;
    }

    /** Deletes a section, its lessons (FK cascade) and their uploaded videos. */
    public function deleteSection(Section $section): void
    {
        $lessons = $section->lessons()->get(['id', 'video_path']);

        DB::transaction(fn () => $section->delete());

        $this->videos->removeFiles($lessons);
        $this->normalisePositions($section->course_id);
    }

    // ------------------------------------------------------------------ Lessons

    public function createLesson(Section $section, array $data): Lesson
    {
        $lesson = $section->lessons()->make($this->lessonAttributes($data));
        $lesson->position = (int) $section->lessons()->max('position') + 1;
        $lesson->save();

        return $lesson;
    }

    public function updateLesson(Lesson $lesson, array $data): Lesson
    {
        $lesson->fill($this->lessonAttributes($data, $lesson));
        $lesson->save();

        // Text lessons don't keep a video around, and a newly pasted video link
        // replaces a previously uploaded file.
        $switchedToText = $lesson->type === LessonType::Text;
        $linkReplacesUpload = $lesson->wasChanged('video_url') && filled($lesson->video_url);

        if ($lesson->video_path && ($switchedToText || $linkReplacesUpload)) {
            $this->videos->remove($lesson);
        }

        return $lesson;
    }

    public function deleteLesson(Lesson $lesson): void
    {
        $this->videos->remove($lesson);
        $lesson->delete();
    }

    // ------------------------------------------------------------------ Ordering

    /**
     * Applies a complete new order from the drag-and-drop builder. Lessons may
     * move between sections. Every section and lesson of the course must appear
     * exactly once, so a stale or tampered payload can't drop or steal items.
     *
     * @param  array<int, array{id: int, lessons: array<int, int>}>  $order
     */
    public function reorder(Course $course, array $order): void
    {
        $sectionIds = collect($order)->pluck('id')->map(fn ($id) => (int) $id);
        $lessonIds = collect($order)->pluck('lessons')->flatten()->map(fn ($id) => (int) $id);

        $actualSections = $course->sections()->pluck('id');
        $actualLessons = $course->lessons()->pluck('id');

        if ($sectionIds->sort()->values()->all() !== $actualSections->sort()->values()->all()
            || $lessonIds->sort()->values()->all() !== $actualLessons->sort()->values()->all()) {
            throw ValidationException::withMessages([
                'sections' => ['The curriculum changed in another window. Reload the page and try again.'],
            ]);
        }

        DB::transaction(function () use ($order) {
            foreach (array_values($order) as $sectionIndex => $section) {
                Section::whereKey($section['id'])->update(['position' => $sectionIndex + 1]);

                foreach (array_values($section['lessons'] ?? []) as $lessonIndex => $lessonId) {
                    Lesson::whereKey($lessonId)->update([
                        'section_id' => $section['id'],
                        'position' => $lessonIndex + 1,
                    ]);
                }
            }
        });
    }

    /** Re-numbers section positions 1..n after a deletion. */
    private function normalisePositions(int $courseId): void
    {
        Section::where('course_id', $courseId)->orderBy('position')->pluck('id')
            ->each(fn ($id, $index) => Section::whereKey($id)->update(['position' => $index + 1]));
    }

    /** Maps validated input to lesson columns; switching to text clears the external link. */
    private function lessonAttributes(array $data, ?Lesson $lesson = null): array
    {
        $attributes = collect($data)->only(['title', 'type', 'content', 'video_url', 'is_preview'])->all();

        if (array_key_exists('duration_minutes', $data)) {
            $attributes['duration_seconds'] = (int) round(((float) $data['duration_minutes']) * 60);
        }

        $type = $data['type'] ?? $lesson?->type?->value;
        if ($type === LessonType::Text->value) {
            $attributes['video_url'] = null;
        }

        return $attributes;
    }
}
