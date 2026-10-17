<?php

namespace Database\Factories;

use App\Enums\LessonType;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * course_id is filled automatically from the section (see Lesson::booted()).
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lesson>
 */
class LessonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'title' => rtrim(fake()->sentence(4), '.'),
            'type' => LessonType::Text,
            'content' => collect(fake()->paragraphs(4))->implode("\n\n"),
            'duration_seconds' => fake()->numberBetween(180, 900),
            'is_preview' => false,
            'position' => 1,
        ];
    }

    public function video(string $url = 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4'): static
    {
        return $this->state(fn () => ['type' => LessonType::Video, 'video_url' => $url]);
    }

    public function preview(): static
    {
        return $this->state(fn () => ['is_preview' => true]);
    }
}
