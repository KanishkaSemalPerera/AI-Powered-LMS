<?php

namespace Tests\Feature\Payments;

use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Uses the real StripeGateway: payloads are signed exactly like Stripe does
 * (HMAC-SHA256 of "timestamp.payload" with the webhook secret).
 */
class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsec_test_secret';

    private User $student;

    private Course $course;

    private Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.stripe.webhook_secret' => self::SECRET]);

        $this->student = User::factory()->student()->create();
        $this->course = Course::factory()->published()->paid(49.99)->create();
        $this->payment = Payment::factory()->for($this->student)->for($this->course)->create([
            'stripe_session_id' => 'cs_test_abc123',
            'amount' => 49.99,
            'currency' => 'usd',
            'status' => PaymentStatus::Pending,
        ]);
    }

    private function sendEvent(string $type, array $object, ?string $secret = self::SECRET, ?int $timestamp = null): TestResponse
    {
        $payload = json_encode([
            'id' => 'evt_'.uniqid(),
            'object' => 'event',
            'type' => $type,
            'data' => ['object' => $object],
        ]);
        $timestamp ??= time();
        $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        return $this->call('POST', '/api/v1/webhooks/stripe', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $payload);
    }

    private function paidSession(array $overrides = []): array
    {
        return array_merge([
            'id' => 'cs_test_abc123',
            'object' => 'checkout.session',
            'payment_status' => 'paid',
            'status' => 'complete',
            'amount_total' => 4999,
            'currency' => 'usd',
            'payment_intent' => 'pi_test_123',
        ], $overrides);
    }

    public function test_completed_checkout_marks_payment_paid_and_enrolls_the_student(): void
    {
        $this->sendEvent('checkout.session.completed', $this->paidSession())->assertOk();

        $this->payment->refresh();
        $this->assertSame(PaymentStatus::Paid, $this->payment->status);
        $this->assertSame('pi_test_123', $this->payment->stripe_payment_intent_id);
        $this->assertNotNull($this->payment->paid_at);
        $this->assertTrue($this->student->isEnrolledIn($this->course));
        $this->assertSame($this->payment->id, $this->student->enrollments()->first()->payment_id);
    }

    public function test_duplicate_webhooks_are_idempotent(): void
    {
        $this->sendEvent('checkout.session.completed', $this->paidSession())->assertOk();
        $this->sendEvent('checkout.session.completed', $this->paidSession())->assertOk();

        $this->assertSame(1, $this->student->enrollments()->count());
        $this->assertSame(1, $this->course->fresh()->students_count);
    }

    public function test_invalid_signatures_are_rejected(): void
    {
        $this->sendEvent('checkout.session.completed', $this->paidSession(), secret: 'whsec_wrong')->assertStatus(400);

        $this->assertSame(PaymentStatus::Pending, $this->payment->fresh()->status);
        $this->assertFalse($this->student->isEnrolledIn($this->course));
    }

    public function test_replayed_old_events_are_rejected(): void
    {
        $this->sendEvent('checkout.session.completed', $this->paidSession(), timestamp: time() - 3600)->assertStatus(400);

        $this->assertFalse($this->student->isEnrolledIn($this->course));
    }

    public function test_unpaid_completed_sessions_do_not_grant_access(): void
    {
        // e.g. bank transfers that complete later via async_payment_succeeded
        $this->sendEvent('checkout.session.completed', $this->paidSession(['payment_status' => 'unpaid']))->assertOk();
        $this->assertFalse($this->student->isEnrolledIn($this->course));

        $this->sendEvent('checkout.session.async_payment_succeeded', $this->paidSession())->assertOk();
        $this->assertTrue($this->student->isEnrolledIn($this->course));
    }

    public function test_amount_mismatch_is_not_fulfilled(): void
    {
        $this->sendEvent('checkout.session.completed', $this->paidSession(['amount_total' => 99]))->assertOk();

        $this->assertSame(PaymentStatus::Failed, $this->payment->fresh()->status);
        $this->assertFalse($this->student->isEnrolledIn($this->course));
    }

    public function test_expired_sessions_are_marked_expired(): void
    {
        $this->sendEvent('checkout.session.expired', ['id' => 'cs_test_abc123', 'object' => 'checkout.session'])->assertOk();

        $this->assertSame(PaymentStatus::Expired, $this->payment->fresh()->status);
    }

    public function test_refunds_revoke_access(): void
    {
        $this->sendEvent('checkout.session.completed', $this->paidSession())->assertOk();

        $this->sendEvent('charge.refunded', ['id' => 'ch_1', 'object' => 'charge', 'payment_intent' => 'pi_test_123'])->assertOk();

        $this->assertSame(PaymentStatus::Refunded, $this->payment->fresh()->status);
        $this->assertFalse($this->student->isEnrolledIn($this->course));
        $this->assertSame(0, $this->course->fresh()->students_count);
    }

    public function test_unknown_events_are_acknowledged(): void
    {
        $this->sendEvent('customer.created', ['id' => 'cus_1', 'object' => 'customer'])
            ->assertOk()
            ->assertJsonPath('received', true);
    }
}
