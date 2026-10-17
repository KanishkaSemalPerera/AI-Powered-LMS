<?php

namespace Tests\Feature\Courses;

use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CurriculumTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instructor = User::factory()->instructor()->create();
        $this->course = Course::factory()->for($this->instructor, 'instructor')->create();
        Sanctum::actingAs($this->instructor);
    }

    public function test_sections_are_appended_in_order(): void
    {
        $this->postJson("/api/v1/instructor/courses/{$this->course->id}/sections", ['title' => 'Getting started'])
            ->assertCreated()
            ->assertJsonPath('data.position', 1);
        $this->postJson("/api/v1/instructor/courses/{$this->course->id}/sections", ['title' => 'Going deeper'])
            ->assertJsonPath('data.position', 2);

        $this->getJson("/api/v1/instructor/courses/{$this->course->id}/curriculum")
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Getting started')
            ->assertJsonPath('data.1.title', 'Going deeper');
    }

    public function test_lessons_can_be_created_updated_and_deleted(): void
    {
        $section = Section::factory()->for($this->course)->create();

        $lessonId = $this->postJson("/api/v1/instructor/sections/{$section->id}/lessons", [
            'title' => 'Welcome',
            'type' => 'text',
            'content' => '## Hello',
            'duration_minutes' => 4.5,
            'is_preview' => true,
        ])->assertCreated()
            ->assertJsonPath('data.duration_seconds', 270)
            ->assertJsonPath('data.is_preview', true)
            ->json('data.id');

        $this->assertSame($this->course->id, Lesson::find($lessonId)->course_id);

        $this->putJson("/api/v1/instructor/lessons/{$lessonId}", ['type' => 'video', 'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'])
            ->assertOk()
            ->assertJsonPath('data.playback_url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');

        $this->putJson("/api/v1/instructor/lessons/{$lessonId}", ['video_url' => 'javascript:alert(1)'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('video_url');

        $this->deleteJson("/api/v1/instructor/lessons/{$lessonId}")->assertOk();
        $this->assertDatabaseMissing('lessons', ['id' => $lessonId]);
    }

    public function test_other_instructors_cannot_touch_the_curriculum(): void
    {
        $section = Section::factory()->for($this->course)->create();
        $lesson = Lesson::factory()->for($section)->create();
        Sanctum::actingAs(User::factory()->instructor()->create());

        $this->getJson("/api/v1/instructor/courses/{$this->course->id}/curriculum")->assertNotFound();
        $this->postJson("/api/v1/instructor/courses/{$this->course->id}/sections", ['title' => 'Hijack'])->assertForbidden();
        $this->postJson("/api/v1/instructor/sections/{$section->id}/lessons", ['title' => 'Hijack', 'type' => 'text'])->assertForbidden();
        $this->putJson("/api/v1/instructor/lessons/{$lesson->id}", ['title' => 'Hijack'])->assertForbidden();
        $this->deleteJson("/api/v1/instructor/sections/{$section->id}")->assertForbidden();
        $this->getJson("/api/v1/instructor/lessons/{$lesson->id}")->assertNotFound();
    }

    public function test_curriculum_is_locked_while_under_review(): void
    {
        $this->course->forceFill(['status' => 'pending'])->save();

        $this->postJson("/api/v1/instructor/courses/{$this->course->id}/sections", ['title' => 'Late change'])
            ->assertForbidden();
    }

    public function test_lessons_can_be_reordered_and_moved_between_sections(): void
    {
        $first = Section::factory()->for($this->course)->create(['position' => 1]);
        $second = Section::factory()->for($this->course)->create(['position' => 2]);
        $a = Lesson::factory()->for($first)->create(['position' => 1]);
        $b = Lesson::factory()->for($first)->create(['position' => 2]);
        $c = Lesson::factory()->for($second)->create(['position' => 1]);

        // Swap the sections and move lesson B into the (new) first section.
        $this->putJson("/api/v1/instructor/courses/{$this->course->id}/curriculum/order", [
            'sections' => [
                ['id' => $second->id, 'lessons' => [$b->id, $c->id]],
                ['id' => $first->id, 'lessons' => [$a->id]],
            ],
        ])->assertOk()
            ->assertJsonPath('data.0.id', $second->id)
            ->assertJsonPath('data.0.lessons.0.id', $b->id)
            ->assertJsonPath('data.1.lessons.0.id', $a->id);

        $this->assertSame($second->id, $b->fresh()->section_id);
        $this->assertSame(1, $second->fresh()->position);
    }

    public function test_reorder_rejects_incomplete_or_foreign_ids(): void
    {
        $section = Section::factory()->for($this->course)->create();
        $lesson = Lesson::factory()->for($section)->create();
        $foreignLesson = Lesson::factory()->create();

        // Missing a lesson.
        $this->putJson("/api/v1/instructor/courses/{$this->course->id}/curriculum/order", [
            'sections' => [['id' => $section->id, 'lessons' => []]],
        ])->assertUnprocessable()->assertJsonValidationErrors('sections');

        // Stealing a lesson from another course.
        $this->putJson("/api/v1/instructor/courses/{$this->course->id}/curriculum/order", [
            'sections' => [['id' => $section->id, 'lessons' => [$lesson->id, $foreignLesson->id]]],
        ])->assertUnprocessable();

        $this->assertSame($foreignLesson->section_id, $foreignLesson->fresh()->section_id);
    }

    public function test_video_upload_is_private_and_streams_with_a_signed_url_only(): void
    {
        $disk = Storage::fake(config('lms.video_disk'));
        $lesson = Lesson::factory()->for(Section::factory()->for($this->course))->video()->create();

        $this->post("/api/v1/instructor/lessons/{$lesson->id}/video", [
            'video' => UploadedFile::fake()->create('intro.exe', 100, 'application/x-msdownload'),
        ])->assertUnprocessable()->assertJsonValidationErrors('video');

        $response = $this->post("/api/v1/instructor/lessons/{$lesson->id}/video", [
            'video' => UploadedFile::fake()->create('intro.mp4', 2048, 'video/mp4'),
            'duration_seconds' => 125,
        ])->assertOk()
            ->assertJsonPath('data.video_upload.original_name', 'intro.mp4')
            ->assertJsonPath('data.duration_seconds', 125)
            ->assertJsonPath('data.video_url', null);

        $lesson->refresh();
        $disk->assertExists($lesson->video_path);
        $this->assertStringStartsWith("lesson-videos/{$this->course->id}/{$lesson->id}/", $lesson->video_path);

        // The signed URL plays; the bare URL and a tampered signature do not.
        $signedUrl = $response->json('data.playback_url');
        $this->get($signedUrl)->assertOk();
        $this->get("/api/v1/lessons/{$lesson->id}/video")->assertForbidden();
        $this->get($signedUrl.'0')->assertForbidden();
    }

    public function test_pasting_a_video_link_replaces_an_uploaded_file(): void
    {
        $disk = Storage::fake(config('lms.video_disk'));
        $lesson = Lesson::factory()->for(Section::factory()->for($this->course))->video()->create(['video_url' => null]);
        $this->post("/api/v1/instructor/lessons/{$lesson->id}/video", ['video' => UploadedFile::fake()->create('a.mp4', 100, 'video/mp4')]);
        $path = $lesson->fresh()->video_path;

        $this->putJson("/api/v1/instructor/lessons/{$lesson->id}", ['video_url' => 'https://vimeo.com/76979871'])
            ->assertOk()
            ->assertJsonPath('data.video_upload', null)
            ->assertJsonPath('data.playback_url', 'https://vimeo.com/76979871');

        $disk->assertMissing($path);
    }

    public function test_deleting_a_section_removes_its_lessons_and_videos(): void
    {
        $disk = Storage::fake(config('lms.video_disk'));
        $section = Section::factory()->for($this->course)->create(['position' => 1]);
        $other = Section::factory()->for($this->course)->create(['position' => 2]);
        $lesson = Lesson::factory()->for($section)->video()->create();
        $this->post("/api/v1/instructor/lessons/{$lesson->id}/video", ['video' => UploadedFile::fake()->create('a.mp4', 100, 'video/mp4')]);
        $path = $lesson->fresh()->video_path;

        $this->deleteJson("/api/v1/instructor/sections/{$section->id}")->assertOk();

        $this->assertModelMissing($lesson);
        $disk->assertMissing($path);
        $this->assertSame(1, $other->fresh()->position);
    }

    public function test_submit_for_review_reports_empty_lessons(): void
    {
        $this->course->update(['category_id' => Category::factory()->create()->id, 'description' => str_repeat('Detailed description. ', 8)]);
        $section = Section::factory()->for($this->course)->create();
        Lesson::factory()->for($section)->create(['title' => 'Empty text lesson', 'type' => 'text', 'content' => '  ']);
        Lesson::factory()->for($section)->create(['title' => 'Video without source', 'type' => 'video', 'video_url' => null]);

        $this->postJson("/api/v1/instructor/courses/{$this->course->id}/submit")
            ->assertUnprocessable()
            ->assertJsonPath('errors.lessons.0', 'These lessons are empty: “Empty text lesson”, “Video without source”.');
    }
}
