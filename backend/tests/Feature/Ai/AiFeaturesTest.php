<?php

namespace Tests\Feature\Ai;

use App\Contracts\AiClient;
use App\Enums\AiRequestStatus;
use App\Exceptions\AiException;
use App\Models\AiRequest;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\Fakes\FakeAiClient;
use Tests\TestCase;

class AiFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private FakeAiClient $ai;

    private Course $course;

    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ai = new FakeAiClient;
        $this->app->instance(AiClient::class, $this->ai);

        $this->course = Course::factory()->published()->create(['title' => 'Modern JavaScript']);
        $this->lesson = Lesson::factory()->for(Section::factory()->for($this->course))->create([
            'title' => 'Arrow functions',
            'content' => str_repeat('Arrow functions are a concise syntax for writing function expressions in JavaScript. ', 4),
        ]);
    }

    private function quizReply(int $questions = 3, array $overrides = []): array
    {
        return array_merge([
            'title' => 'Arrow functions quiz',
            'questions' => array_map(fn ($i) => [
                'question' => "Generated question number {$i}?",
                'options' => ["A{$i}", "B{$i}", "C{$i}", "D{$i}"],
                'correct_option' => $i % 4,
                'explanation' => "Because of reason {$i}.",
            ], range(1, $questions)),
        ], $overrides);
    }

    private function generate(array $payload = []): TestResponse
    {
        return $this->postJson("/api/v1/instructor/lessons/{$this->lesson->id}/ai/quiz", array_merge([
            'num_questions' => 3,
            'difficulty' => 'medium',
        ], $payload));
    }

    // ------------------------------------------------------------------ quiz generator

    public function test_quiz_generator_saves_a_draft_quiz_and_logs_usage(): void
    {
        Sanctum::actingAs($this->course->instructor);
        $this->ai->reply($this->quizReply(3));

        $this->generate()
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.is_ai_generated', true)
            ->assertJsonCount(3, 'data.questions')
            ->assertJsonPath('data.questions.0.correct_option', 1);

        $prompt = $this->ai->prompts[0];
        $this->assertNotNull($prompt->jsonSchema, 'quiz generation must request structured JSON');
        $this->assertStringContainsString('Arrow functions are a concise syntax', $prompt->messages[0]['content']);
        $this->assertStringContainsString('3 medium-difficulty', $prompt->messages[0]['content']);
        $this->assertSame(config('services.anthropic.effort.quiz_generator'), $prompt->effort);

        $log = AiRequest::sole();
        $this->assertSame(AiRequestStatus::Success, $log->status);
        $this->assertSame('quiz_generator', $log->feature->value);
        $this->assertSame([1200, 350], [$log->input_tokens, $log->output_tokens]);
        $this->assertTrue($log->subject->is($this->lesson));
    }

    public function test_invalid_json_from_the_model_is_rejected_and_logged(): void
    {
        Sanctum::actingAs($this->course->instructor);
        $this->ai->reply('Sure! Here is your quiz: {not json');

        $this->generate()
            ->assertStatus(502)
            ->assertJsonPath('errors.ai.0', 'invalid_output');

        $this->assertSame(0, Quiz::count());
        $this->assertSame(AiRequestStatus::Error, AiRequest::sole()->status);
    }

    public function test_bad_questions_are_filtered_and_extra_ones_trimmed(): void
    {
        Sanctum::actingAs($this->course->instructor);
        $reply = $this->quizReply(5);
        $reply['questions'][0]['correct_option'] = 9;                       // out of range
        $reply['questions'][1]['options'] = ['Same', 'same', 'Other', 'X']; // duplicate options
        $this->ai->reply($reply);

        // 3 requested, 5 returned, 2 invalid → the 3 valid ones are kept.
        $this->generate()->assertCreated()->assertJsonCount(3, 'data.questions');
    }

    public function test_output_with_no_usable_questions_fails(): void
    {
        Sanctum::actingAs($this->course->instructor);
        $this->ai->reply(['title' => 'Empty', 'questions' => []]);

        $this->generate()->assertStatus(502);
        $this->assertSame(0, Quiz::count());
    }

    public function test_existing_quiz_is_only_replaced_when_confirmed(): void
    {
        Sanctum::actingAs($this->course->instructor);
        Quiz::factory()->for($this->lesson)->withQuestions(2)->create();

        $this->generate()->assertStatus(409);
        $this->assertSame([], $this->ai->prompts, 'no AI call is made before confirmation');

        $this->ai->reply($this->quizReply(3));
        $this->generate(['replace' => true])->assertCreated();
        $this->assertSame(3, $this->lesson->fresh()->quiz->questions()->count());
    }

    public function test_short_lessons_need_pasted_material(): void
    {
        Sanctum::actingAs($this->course->instructor);
        $this->lesson->update(['content' => 'Too short.']);

        $this->generate()->assertUnprocessable()->assertJsonValidationErrors('source_text');

        $this->ai->reply($this->quizReply(3));
        $material = str_repeat('Closures capture variables from their surrounding scope. ', 4);
        $this->generate(['source_text' => $material])->assertCreated();
        $this->assertStringContainsString('Closures capture variables', $this->ai->prompts[0]->messages[0]['content']);
    }

    public function test_only_the_course_owner_can_generate(): void
    {
        Sanctum::actingAs(User::factory()->instructor()->create());
        $this->generate()->assertForbidden();

        Sanctum::actingAs(User::factory()->student()->create());
        $this->generate()->assertForbidden();
    }

    public function test_missing_api_key_gives_a_clear_message_and_logs_nothing(): void
    {
        Sanctum::actingAs($this->course->instructor);
        $this->ai->configured = false;

        $this->generate()
            ->assertStatus(503)
            ->assertJsonPath('message', 'AI features are not configured on this server yet.');
        $this->assertSame(0, AiRequest::count());
    }

    public function test_provider_errors_are_reported_safely(): void
    {
        Sanctum::actingAs($this->course->instructor);
        $this->ai->fail(AiException::timeout());

        $this->generate()
            ->assertStatus(504)
            ->assertJsonPath('message', 'The AI took too long to respond. Please try again.');
        $this->assertStringStartsWith('timeout:', AiRequest::sole()->error_message);
    }

    // ------------------------------------------------------------------ study assistant

    public function test_enrolled_student_gets_an_answer_grounded_in_the_lesson(): void
    {
        $student = User::factory()->student()->create();
        Enrollment::factory()->for($student)->for($this->course)->create();
        Sanctum::actingAs($student);
        $this->ai->reply('An arrow function is a shorter function syntax.');

        $this->postJson("/api/v1/learn/{$this->course->slug}/lessons/{$this->lesson->id}/assistant", [
            'question' => 'What is an arrow function?',
            'history' => [
                ['role' => 'assistant', 'content' => 'A stray leading assistant turn'],
                ['role' => 'user', 'content' => 'Hi'],
                ['role' => 'assistant', 'content' => 'Hello! Ask me anything about this lesson.'],
            ],
        ])->assertOk()
            ->assertJsonPath('data.answer', 'An arrow function is a shorter function syntax.')
            ->assertJsonPath('data.usage.used_today', 1);

        $prompt = $this->ai->prompts[0];
        $this->assertStringContainsString('Arrow functions are a concise syntax', $prompt->system);
        $this->assertNull($prompt->jsonSchema);
        // History normalised to alternate user/assistant and end with the new question.
        $this->assertSame(['user', 'assistant', 'user'], array_column($prompt->messages, 'role'));
        $this->assertSame('What is an arrow function?', $prompt->messages[array_key_last($prompt->messages)]['content']);
    }

    public function test_students_who_are_not_enrolled_cannot_use_the_assistant(): void
    {
        Sanctum::actingAs(User::factory()->student()->create());

        $this->postJson("/api/v1/learn/{$this->course->slug}/lessons/{$this->lesson->id}/assistant", ['question' => 'Hello?'])
            ->assertForbidden();
        $this->assertSame([], $this->ai->prompts);
    }

    public function test_refusals_are_explained(): void
    {
        $student = User::factory()->student()->create();
        Enrollment::factory()->for($student)->for($this->course)->create();
        Sanctum::actingAs($student);
        $this->ai->fail(AiException::refused());

        $this->postJson("/api/v1/learn/{$this->course->slug}/lessons/{$this->lesson->id}/assistant", ['question' => 'Something odd'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.ai.0', 'refused');
    }

    // ------------------------------------------------------------------ description helper

    public function test_description_helper_returns_a_suggestion_without_saving(): void
    {
        Sanctum::actingAs($this->course->instructor);
        $this->ai->reply([
            'subtitle' => 'Write modern JavaScript with confidence.',
            'description' => str_repeat('This course teaches modern JavaScript step by step. ', 5),
            'what_you_will_learn' => ['Use arrow functions', 'Work with arrays', 'Write async code'],
        ]);

        $this->postJson('/api/v1/instructor/ai/course-description', [
            'title' => 'Modern JavaScript',
            'notes' => 'Covers ES2023 features',
            'tone' => 'friendly',
            'course_id' => $this->course->id,
        ])->assertOk()
            ->assertJsonPath('data.subtitle', 'Write modern JavaScript with confidence.')
            ->assertJsonCount(3, 'data.what_you_will_learn');

        $this->assertStringContainsString('Covers ES2023 features', $this->ai->prompts[0]->messages[0]['content']);
        $this->assertNotSame('Write modern JavaScript with confidence.', $this->course->fresh()->subtitle);
        $this->assertTrue(AiRequest::sole()->subject->is($this->course));
    }

    public function test_students_cannot_use_instructor_ai_tools(): void
    {
        Sanctum::actingAs(User::factory()->student()->create());

        $this->postJson('/api/v1/instructor/ai/course-description', ['title' => 'Anything here'])->assertForbidden();
    }

    // ------------------------------------------------------------------ limits & usage

    public function test_daily_limit_is_enforced_per_user(): void
    {
        config(['services.anthropic.rate_limit_per_day' => 2]);
        $instructor = $this->course->instructor;
        AiRequest::factory()->count(2)->for($instructor)->create();
        AiRequest::factory()->for($instructor)->create(['created_at' => now()->subDay()]); // yesterday doesn't count
        Sanctum::actingAs($instructor);

        $this->generate()
            ->assertStatus(429)
            ->assertJsonPath('errors.ai.0', 'daily_limit');

        $this->getJson('/api/v1/ai/usage')
            ->assertJsonPath('data.used_today', 2)
            ->assertJsonPath('data.remaining_today', 0)
            ->assertJsonPath('data.configured', true);
    }

    public function test_per_minute_rate_limit(): void
    {
        config(['services.anthropic.rate_limit_per_minute' => 2]);
        Sanctum::actingAs($this->course->instructor);
        $this->ai->reply($this->quizReply())->reply($this->quizReply());

        $this->generate()->assertCreated();
        $this->generate(['replace' => true])->assertCreated();
        $this->generate(['replace' => true])->assertTooManyRequests();
    }
}
