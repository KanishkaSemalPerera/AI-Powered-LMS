<?php

namespace Tests\Feature\Quizzes;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuizTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    private Lesson $lesson;

    private User $student;

    private Enrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::factory()->published()->create();
        $section = Section::factory()->for($this->course)->create();
        $this->lesson = Lesson::factory()->for($section)->create(['position' => 1]);
        Lesson::factory()->for($section)->create(['position' => 2]);

        $this->student = User::factory()->student()->create();
        $this->enrollment = Enrollment::factory()->for($this->student)->for($this->course)->create();
    }

    private function quizPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Check your understanding',
            'description' => 'Three quick questions.',
            'pass_mark' => 60,
            'status' => 'published',
            'questions' => [
                ['question' => 'What is 2 + 2?', 'options' => ['3', '4', '5'], 'correct_option' => 1, 'explanation' => 'Basic arithmetic.'],
                ['question' => 'Which keyword declares a constant?', 'options' => ['var', 'let', 'const'], 'correct_option' => 2, 'explanation' => null],
                ['question' => 'Is PHP a programming language?', 'options' => ['Yes', 'No'], 'correct_option' => 0],
            ],
        ], $overrides);
    }

    /** Publishes a quiz on the lesson via the API as its instructor. */
    private function createQuiz(array $overrides = []): Quiz
    {
        Sanctum::actingAs($this->course->instructor);
        $this->putJson("/api/v1/instructor/lessons/{$this->lesson->id}/quiz", $this->quizPayload($overrides))->assertSuccessful();
        Sanctum::actingAs($this->student);

        return $this->lesson->quiz()->with('questions')->first();
    }

    private function url(string $suffix = ''): string
    {
        return "/api/v1/learn/{$this->course->slug}/lessons/{$this->lesson->id}/quiz{$suffix}";
    }

    // ------------------------------------------------------------------ authoring

    public function test_instructor_creates_and_replaces_a_quiz(): void
    {
        Sanctum::actingAs($this->course->instructor);

        $this->putJson("/api/v1/instructor/lessons/{$this->lesson->id}/quiz", $this->quizPayload())
            ->assertCreated()
            ->assertJsonCount(3, 'data.questions')
            ->assertJsonPath('data.questions.0.correct_option', 1);

        $this->putJson("/api/v1/instructor/lessons/{$this->lesson->id}/quiz", $this->quizPayload([
            'title' => 'Updated quiz',
            'questions' => [['question' => 'Only one question now?', 'options' => ['Yes', 'No'], 'correct_option' => 0]],
        ]))->assertOk()->assertJsonPath('data.title', 'Updated quiz')->assertJsonCount(1, 'data.questions');

        $this->assertSame(1, Quiz::count());
        $this->assertSame(1, Question::count());

        $this->getJson("/api/v1/instructor/lessons/{$this->lesson->id}/quiz")->assertJsonPath('data.quiz.title', 'Updated quiz');
    }

    public function test_quiz_validation(): void
    {
        Sanctum::actingAs($this->course->instructor);

        $this->putJson("/api/v1/instructor/lessons/{$this->lesson->id}/quiz", $this->quizPayload([
            'pass_mark' => 150,
            'questions' => [
                ['question' => 'Bad index?', 'options' => ['A', 'B'], 'correct_option' => 5],
                ['question' => 'Duplicate options?', 'options' => ['Same', 'same '], 'correct_option' => 0],
                ['question' => 'One option?', 'options' => ['Only'], 'correct_option' => 0],
            ],
        ]))->assertUnprocessable()->assertJsonValidationErrors([
            'pass_mark', 'questions.0.correct_option', 'questions.1.options', 'questions.2.options',
        ]);

        $this->putJson("/api/v1/instructor/lessons/{$this->lesson->id}/quiz", $this->quizPayload(['questions' => []]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('questions');

        // An empty draft is fine.
        $this->putJson("/api/v1/instructor/lessons/{$this->lesson->id}/quiz", $this->quizPayload(['status' => 'draft', 'questions' => []]))
            ->assertCreated();
    }

    public function test_other_instructors_cannot_edit_the_quiz(): void
    {
        Sanctum::actingAs(User::factory()->instructor()->create());

        $this->getJson("/api/v1/instructor/lessons/{$this->lesson->id}/quiz")->assertNotFound();
        $this->putJson("/api/v1/instructor/lessons/{$this->lesson->id}/quiz", $this->quizPayload())->assertForbidden();
    }

    // ------------------------------------------------------------------ taking

    public function test_students_get_questions_without_the_answer_key(): void
    {
        $this->createQuiz();

        $response = $this->getJson($this->url())
            ->assertOk()
            ->assertJsonCount(3, 'data.quiz.questions')
            ->assertJsonPath('data.can_submit', true)
            ->assertJsonPath('data.summary.attempts', 0);

        $question = $response->json('data.quiz.questions.0');
        $this->assertArrayNotHasKey('correct_option', $question);
        $this->assertArrayNotHasKey('explanation', $question);
    }

    public function test_draft_quizzes_are_hidden_from_students(): void
    {
        $this->createQuiz(['status' => 'draft']);

        $this->getJson($this->url())->assertNotFound();
        $this->getJson("/api/v1/learn/{$this->course->slug}/lessons/{$this->lesson->id}")->assertJsonPath('data.quiz', null);
    }

    public function test_attempts_are_scored_on_the_server_with_a_review(): void
    {
        $quiz = $this->createQuiz();
        [$q1, $q2, $q3] = $quiz->questions;

        // 1 of 3 correct (33%) — below the 60% pass mark. Q3 left unanswered.
        $this->postJson($this->url('/attempts'), ['answers' => [$q1->id => 1, $q2->id => 0]])
            ->assertCreated()
            ->assertJsonPath('data.correct_count', 1)
            ->assertJsonPath('data.score_percent', 33)
            ->assertJsonPath('data.passed', false)
            ->assertJsonPath('data.review.0.is_correct', true)
            ->assertJsonPath('data.review.1.chosen_option', 0)
            ->assertJsonPath('data.review.1.correct_option', 2)
            ->assertJsonPath('data.review.2.chosen_option', null)
            ->assertJsonPath('data.review.0.explanation', 'Basic arithmetic.');

        $this->assertDatabaseCount('lesson_progress', 0);
    }

    public function test_passing_marks_the_lesson_complete(): void
    {
        $quiz = $this->createQuiz();
        [$q1, $q2, $q3] = $quiz->questions;

        $this->postJson($this->url('/attempts'), ['answers' => [$q1->id => 1, $q2->id => 2, $q3->id => 1]])
            ->assertCreated()
            ->assertJsonPath('data.score_percent', 67)
            ->assertJsonPath('data.passed', true)
            ->assertJsonPath('message', 'Well done — you passed!');

        $this->assertSame(1, $this->enrollment->lessonProgress()->where('lesson_id', $this->lesson->id)->count());
        $this->assertSame(50, $this->enrollment->fresh()->progress_percent);
    }

    public function test_invalid_answers_are_rejected(): void
    {
        $quiz = $this->createQuiz();
        $foreign = Question::factory()->create();

        $this->postJson($this->url('/attempts'), ['answers' => [$foreign->id => 0]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('answers');

        $this->postJson($this->url('/attempts'), ['answers' => [$quiz->questions[0]->id => 9]])
            ->assertUnprocessable();
    }

    public function test_history_summary_and_review_of_own_attempts(): void
    {
        $quiz = $this->createQuiz();
        [$q1, $q2, $q3] = $quiz->questions;

        $this->postJson($this->url('/attempts'), ['answers' => [$q1->id => 0]]);
        $attemptId = $this->postJson($this->url('/attempts'), ['answers' => [$q1->id => 1, $q2->id => 2, $q3->id => 0]])->json('data.id');

        $this->getJson($this->url('/attempts'))
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.id', $attemptId)
            ->assertJsonPath('data.0.score_percent', 100);

        $this->getJson($this->url())
            ->assertJsonPath('data.summary.attempts', 2)
            ->assertJsonPath('data.summary.best_score', 100)
            ->assertJsonPath('data.summary.passed', true);

        $this->getJson("/api/v1/quiz-attempts/{$attemptId}")->assertOk()->assertJsonCount(3, 'data.review');

        Sanctum::actingAs(User::factory()->student()->create());
        $this->getJson("/api/v1/quiz-attempts/{$attemptId}")->assertNotFound();
    }

    public function test_only_enrolled_students_can_take_the_quiz(): void
    {
        $this->createQuiz();

        Sanctum::actingAs(User::factory()->student()->create());
        $this->getJson($this->url())->assertForbidden();
        $this->postJson($this->url('/attempts'), ['answers' => []])->assertForbidden();

        // The owner can preview the quiz but not submit.
        Sanctum::actingAs($this->course->instructor);
        $this->getJson($this->url())->assertOk()->assertJsonPath('data.can_submit', false);
        $this->postJson($this->url('/attempts'), ['answers' => []])->assertForbidden();
    }

    public function test_lesson_endpoint_includes_the_quiz_summary(): void
    {
        $this->createQuiz();

        $this->getJson("/api/v1/learn/{$this->course->slug}/lessons/{$this->lesson->id}")
            ->assertJsonPath('data.quiz.questions_count', 3)
            ->assertJsonPath('data.quiz.pass_mark', 60)
            ->assertJsonPath('data.quiz.attempts', 0);

        $this->getJson("/api/v1/learn/{$this->course->slug}")->assertJsonPath('data.sections.0.lessons.0.has_quiz', true);
    }
}
