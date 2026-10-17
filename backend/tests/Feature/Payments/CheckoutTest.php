<?php

namespace Tests\Feature\Payments;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Fakes\FakePaymentGateway;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private FakePaymentGateway $gateway;

    private User $student;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $this->gateway);

        $this->student = User::factory()->student()->create(['email' => 'buyer@example.com']);
        $this->course = Course::factory()->published()->paid(29.99)->create(['title' => 'Paid course']);
        Sanctum::actingAs($this->student);
    }

    public function test_checkout_creates_a_pending_payment_and_stripe_session(): void
    {
        $response = $this->postJson("/api/v1/courses/{$this->course->id}/checkout")
            ->assertCreated()
            ->assertJsonPath('data.checkout_url', fn ($url) => str_starts_with($url, 'https://checkout.stripe.test/'));

        $payment = Payment::first();
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame($response->json('data.session_id'), $payment->stripe_session_id);
        $this->assertSame('29.99', $payment->amount);

        $params = $this->gateway->created[0];
        $this->assertSame(2999, $params['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame('buyer@example.com', $params['customer_email']);
        $this->assertSame((string) $payment->id, $params['metadata']['payment_id']);
        $this->assertStringContainsString('/checkout/success?session_id={CHECKOUT_SESSION_ID}', $params['success_url']);
        $this->assertFalse($this->student->isEnrolledIn($this->course));
    }

    public function test_checkout_is_refused_for_free_courses_and_enrolled_students(): void
    {
        $free = Course::factory()->published()->free()->create();
        $this->postJson("/api/v1/courses/{$free->id}/checkout")->assertUnprocessable();

        $this->app->make(\App\Services\EnrollmentService::class)->grantAccess($this->student, $this->course);
        $this->postJson("/api/v1/courses/{$this->course->id}/checkout")->assertStatus(409);
    }

    public function test_checkout_explains_when_payments_are_not_configured(): void
    {
        $this->gateway->configured = false;

        $this->postJson("/api/v1/courses/{$this->course->id}/checkout")
            ->assertStatus(503)
            ->assertJsonPath('message', 'Online payments are not configured yet. Please try again later.');
        $this->assertSame(0, Payment::count());
    }

    public function test_a_stripe_outage_marks_the_payment_failed(): void
    {
        $this->gateway->failCreate = true;

        $this->postJson("/api/v1/courses/{$this->course->id}/checkout")->assertStatus(502);
        $this->assertSame(PaymentStatus::Failed, Payment::first()->status);
    }

    public function test_success_page_confirmation_grants_access_once_paid(): void
    {
        $sessionId = $this->postJson("/api/v1/courses/{$this->course->id}/checkout")->json('data.session_id');

        // Before paying: still pending, no access.
        $this->postJson('/api/v1/checkout/confirm', ['session_id' => $sessionId])
            ->assertOk()
            ->assertJsonPath('data.status', 'pending');
        $this->assertFalse($this->student->isEnrolledIn($this->course));

        $this->gateway->pay($sessionId);

        $this->postJson('/api/v1/checkout/confirm', ['session_id' => $sessionId])
            ->assertOk()
            ->assertJsonPath('data.status', 'paid')
            ->assertJsonPath('data.course.slug', $this->course->slug);

        $this->assertTrue($this->student->isEnrolledIn($this->course));
        $this->assertSame(1, $this->course->fresh()->students_count);

        // Confirming again changes nothing.
        $this->postJson('/api/v1/checkout/confirm', ['session_id' => $sessionId])->assertOk();
        $this->assertSame(1, $this->student->enrollments()->count());
    }

    public function test_a_student_cannot_confirm_someone_elses_session(): void
    {
        $sessionId = $this->postJson("/api/v1/courses/{$this->course->id}/checkout")->json('data.session_id');
        $this->gateway->pay($sessionId);

        Sanctum::actingAs(User::factory()->student()->create());
        $this->postJson('/api/v1/checkout/confirm', ['session_id' => $sessionId])->assertNotFound();
    }

    public function test_a_tampered_amount_is_never_fulfilled(): void
    {
        $sessionId = $this->postJson("/api/v1/courses/{$this->course->id}/checkout")->json('data.session_id');
        $this->gateway->pay($sessionId, amount: 100); // paid $1 instead of $29.99

        $this->postJson('/api/v1/checkout/confirm', ['session_id' => $sessionId])->assertJsonPath('data.status', 'failed');
        $this->assertFalse($this->student->isEnrolledIn($this->course));
    }

    public function test_payment_history_shows_completed_payments_only(): void
    {
        $paidSession = $this->postJson("/api/v1/courses/{$this->course->id}/checkout")->json('data.session_id');
        $this->gateway->pay($paidSession);
        $this->postJson('/api/v1/checkout/confirm', ['session_id' => $paidSession]);

        $other = Course::factory()->published()->paid(10)->create();
        $this->postJson("/api/v1/courses/{$other->id}/checkout"); // abandoned, still pending
        Payment::factory()->paid()->create(); // another user's

        $this->getJson('/api/v1/payments')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.status', 'paid')
            ->assertJsonPath('data.0.amount', 29.99)
            ->assertJsonPath('data.0.course.title', 'Paid course');
    }
}
