<?php

namespace Database\Seeders;

use App\Enums\PaymentStatus;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Payment;
use App\Models\QuizAttempt;
use App\Models\Review;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Realistic learning activity so dashboards and charts have data:
 * enrollments spread over six months, paid Stripe-style payments, lesson
 * progress, quiz attempts, reviews and certificates for completed courses.
 *
 * Uses a fixed random seed so every `db:seed` produces the same demo data.
 */
class DemoActivitySeeder extends Seeder
{
    private const REVIEW_COMMENTS = [
        5 => [
            'Clear explanations and practical examples. Exactly what I needed.',
            'One of the best courses I have taken — well paced and easy to follow.',
            'The quizzes really helped the concepts stick. Highly recommended!',
        ],
        4 => [
            'Very good course. A few more exercises would make it perfect.',
            'Great content and a friendly instructor. Some lessons felt a little fast.',
        ],
        3 => [
            'Solid introduction, but I expected more depth in the later sections.',
        ],
    ];

    private Randomizer $random;

    public function run(): void
    {
        $this->random = new Randomizer(new Mt19937(2026));

        $students = User::where('email', 'like', 'student%@lms.test')->orderBy('id')->get();
        $courses = Course::published()->with(['instructor', 'sections.lessons.quiz.questions'])->orderBy('id')->get()->keyBy('title');

        // Emma (student1) is the showcase account: one finished course with a
        // certificate, one paid course in progress, and one just started.
        $emma = $students->first();
        $this->enroll($emma, $courses['Modern JavaScript from Scratch'], 1.0, now()->subMonths(3), rating: 5);
        $this->enroll($emma, $courses['React 19 Fundamentals: Build Real Apps'], 0.5, now()->subWeeks(5));
        $this->enroll($emma, $courses['Prompt Engineering for Everyday Work'], 0.0, now()->subDays(3));

        foreach ($students->skip(1) as $student) {
            $picks = collect($this->random->shuffleArray($courses->values()->all()))->take($this->random->getInt(2, 4));

            foreach ($picks as $course) {
                $progress = [0.0, 0.17, 0.34, 0.5, 0.67, 0.84, 1.0][$this->random->getInt(0, 6)];
                $enrolledAt = now()->subDays($this->random->getInt(3, 180))->setTime($this->random->getInt(8, 22), $this->random->getInt(0, 59));
                $rating = $progress >= 0.34 && $this->random->getInt(1, 100) <= 60 ? [3, 4, 4, 5, 5, 5][$this->random->getInt(0, 5)] : null;

                $this->enroll($student, $course, $progress, $enrolledAt, $rating);
            }
        }

        $courses->each(function (Course $course) {
            $course->refreshStudentsCount();
            $course->refreshRating();
        });
    }

    /**
     * Enrolls a student and simulates their activity.
     *
     * @param  float  $progress  Fraction of lessons completed (0.0 - 1.0).
     */
    private function enroll(User $student, Course $course, float $progress, CarbonInterface $enrolledAt, ?int $rating = null): void
    {
        $payment = $course->isFree() ? null : $this->createPayment($student, $course, $enrolledAt);

        $lessons = $this->orderedLessons($course);
        $completedCount = (int) round($lessons->count() * $progress);
        $completed = $lessons->take($completedCount);
        $isComplete = $completedCount === $lessons->count();
        $lastActivity = $completedCount > 0 ? $enrolledAt->copy()->addDays($completedCount * 2) : $enrolledAt;
        if ($lastActivity->isFuture()) {
            $lastActivity = now()->subHour();
        }

        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'payment_id' => $payment?->id,
            'progress_percent' => $lessons->isEmpty() ? 0 : (int) round($completedCount / $lessons->count() * 100),
            // Resume point: the next unfinished lesson, or the last lesson when done.
            'last_lesson_id' => $completedCount > 0 ? ($lessons->get($completedCount) ?? $lessons->last())->id : null,
            'last_accessed_at' => $completedCount > 0 ? $lastActivity : null,
            'completed_at' => $isComplete ? $lastActivity : null,
            'created_at' => $enrolledAt,
            'updated_at' => $lastActivity,
        ]);

        foreach ($completed->values() as $i => $lesson) {
            $completedAt = $enrolledAt->copy()->addDays(($i + 1) * 2);
            $enrollment->lessonProgress()->create([
                'lesson_id' => $lesson->id,
                'completed_at' => $completedAt->isFuture() ? now()->subHour() : $completedAt,
            ]);

            if ($lesson->quiz) {
                $this->createQuizAttempt($student, $lesson, $completedAt);
            }
        }

        if ($rating !== null) {
            $review = new Review([
                'rating' => $rating,
                'comment' => self::REVIEW_COMMENTS[$rating][$this->random->getInt(0, count(self::REVIEW_COMMENTS[$rating]) - 1)],
            ]);
            $review->user()->associate($student);
            $review->course()->associate($course);
            $review->created_at = $lastActivity;
            $review->saveQuietly(); // Ratings are recalculated once per course at the end.
        }

        if ($isComplete) {
            Certificate::create([
                'user_id' => $student->id,
                'course_id' => $course->id,
                'student_name' => $student->name,
                'course_title' => $course->title,
                'instructor_name' => $course->instructor->name,
                'issued_at' => $lastActivity,
            ]);
        }
    }

    private function createPayment(User $student, Course $course, CarbonInterface $paidAt): Payment
    {
        return Payment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'stripe_session_id' => 'cs_test_seed_'.Str::lower(Str::random(20)),
            'stripe_payment_intent_id' => 'pi_test_seed_'.Str::lower(Str::random(20)),
            'amount' => $course->price,
            'currency' => config('services.stripe.currency', 'usd'),
            'status' => PaymentStatus::Paid,
            'paid_at' => $paidAt,
            'created_at' => $paidAt,
            'updated_at' => $paidAt,
        ]);
    }

    /** One or two attempts; the latest passes most of the time. */
    private function createQuizAttempt(User $student, Lesson $lesson, CarbonInterface $at): void
    {
        $quiz = $lesson->quiz;
        $questions = $quiz->questions;
        $attempts = $this->random->getInt(1, 100) <= 30 ? 2 : 1;

        for ($n = 1; $n <= $attempts; $n++) {
            $isFinal = $n === $attempts;
            $answers = [];
            $correct = 0;

            foreach ($questions as $question) {
                $answerCorrectly = $this->random->getInt(1, 100) <= ($isFinal ? 85 : 50);
                $choice = $answerCorrectly
                    ? $question->correct_option
                    : ($question->correct_option + $this->random->getInt(1, count($question->options) - 1)) % count($question->options);
                $answers[$question->id] = $choice;
                $correct += (int) ($choice === $question->correct_option);
            }

            $percent = $questions->isEmpty() ? 0 : (int) round($correct / $questions->count() * 100);

            $attempt = new QuizAttempt([
                'answers' => $answers,
                'correct_count' => $correct,
                'total_questions' => $questions->count(),
                'score_percent' => $percent,
                'passed' => $percent >= $quiz->pass_mark,
            ]);
            $attempt->user()->associate($student);
            $attempt->quiz()->associate($quiz);
            $attempt->created_at = $at->copy()->addMinutes($n * 15);
            $attempt->save();
        }
    }

    /** @return Collection<int, Lesson> Lessons in course order (section, then lesson position). */
    private function orderedLessons(Course $course): Collection
    {
        return $course->sections->flatMap(fn ($section) => $section->lessons)->values();
    }
}
