<?php

namespace Database\Seeders;

use App\Enums\CourseStatus;
use App\Enums\QuizStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates the 8 demo courses (with sections, lessons and quizzes) from
 * database/seeders/data/courses.php.
 */
class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $catalogue = require database_path('seeders/data/courses.php');
        $categories = Category::pluck('id', 'slug');
        $instructors = User::whereIn('email', array_column($catalogue, 'instructor'))->get()->keyBy('email');

        foreach ($catalogue as $index => $data) {
            $course = $instructors[$data['instructor']]->courses()->create([
                'category_id' => $categories[$data['category']],
                'title' => $data['title'],
                'subtitle' => $data['subtitle'],
                'description' => $data['description'],
                'what_you_will_learn' => $data['what_you_will_learn'],
                'requirements' => $data['requirements'],
                'price' => $data['price'],
                'level' => $data['level'],
                'language' => 'English',
            ]);

            $this->applyStatus($course, CourseStatus::from($data['status']), $data['featured'], $index);
            $this->createContent($course, $data['sections']);
        }
    }

    /** Workflow fields are not mass assignable, so set them explicitly with realistic dates. */
    private function applyStatus(Course $course, CourseStatus $status, bool $featured, int $index): void
    {
        $publishedAt = now()->subMonths(8)->addWeeks($index * 3);

        $course->forceFill([
            'status' => $status,
            'is_featured' => $featured,
            'submitted_at' => $status === CourseStatus::Draft ? null : ($status === CourseStatus::Pending ? now()->subDays(2) : $publishedAt->copy()->subDays(3)),
            'published_at' => $status === CourseStatus::Published ? $publishedAt : null,
            'created_at' => $publishedAt->copy()->subDays(14),
        ])->save();
    }

    private function createContent(Course $course, array $sections): void
    {
        foreach ($sections as $sectionIndex => $sectionData) {
            $section = $course->sections()->create([
                'title' => $sectionData['title'],
                'position' => $sectionIndex + 1,
            ]);

            foreach ($sectionData['lessons'] as $lessonIndex => $lessonData) {
                $lesson = $section->lessons()->create([
                    'title' => $lessonData['title'],
                    'type' => $lessonData['type'],
                    'video_url' => $lessonData['video'] ?? null,
                    'content' => $lessonData['content'],
                    'duration_seconds' => $lessonData['duration'] * 60,
                    'is_preview' => $lessonData['preview'] ?? false,
                    'position' => $lessonIndex + 1,
                ]);

                if (isset($lessonData['quiz'])) {
                    $quiz = $lesson->quiz()->create([
                        'title' => $lessonData['quiz']['title'],
                        'description' => 'Check your understanding of this section.',
                        'pass_mark' => $lessonData['quiz']['pass_mark'],
                        'status' => QuizStatus::Published,
                    ]);

                    foreach ($lessonData['quiz']['questions'] as $questionIndex => $question) {
                        $quiz->questions()->create([
                            'question' => $question['question'],
                            'options' => $question['options'],
                            'correct_option' => $question['correct'],
                            'explanation' => $question['explanation'],
                            'position' => $questionIndex + 1,
                        ]);
                    }
                }
            }
        }
    }
}
