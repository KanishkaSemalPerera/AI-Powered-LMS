<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Paid enrollment via Stripe Checkout.
 *
 * Flow: checkout() creates a pending Payment and a Checkout Session → the
 * student pays on Stripe → Stripe calls the webhook (handleWebhookEvent) →
 * fulfil() marks the payment paid and grants access. confirm() runs the same
 * idempotent fulfil() from the success page, so access is granted even when
 * webhooks are delayed (or not forwarded during local development).
 */
class PaymentService
{
    /** Stripe requires at least 30 minutes. */
    private const SESSION_TTL_MINUTES = 30;

    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly EnrollmentService $enrollments,
    ) {}

    /**
     * @return array{checkout_url: string, session_id: string, payment_id: int}
     */
    public function checkout(User $user, Course $course): array
    {
        $this->enrollments->assertCanEnroll($user, $course);
        abort_if($course->isFree(), 422, 'This course is free. Enroll directly instead.');
        abort_unless($this->gateway->isConfigured(), 503, 'Online payments are not configured yet. Please try again later.');

        $currency = strtolower(config('services.stripe.currency', 'usd'));
        $frontend = config('services.frontend_url');

        $payment = Payment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'amount' => $course->price,
            'currency' => $currency,
            'status' => PaymentStatus::Pending,
        ]);

        try {
            $session = $this->gateway->createCheckoutSession([
                'mode' => 'payment',
                'customer_email' => $user->email,
                'client_reference_id' => (string) $payment->id,
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => $currency,
                        'unit_amount' => $this->toMinorUnits($course->price),
                        'product_data' => array_filter([
                            'name' => $course->title,
                            'description' => $course->subtitle ?: null,
                        ]),
                    ],
                ]],
                'metadata' => [
                    'payment_id' => (string) $payment->id,
                    'user_id' => (string) $user->id,
                    'course_id' => (string) $course->id,
                ],
                'success_url' => $frontend.'/checkout/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $frontend.'/checkout/cancel?course='.urlencode($course->slug),
                'expires_at' => now()->addMinutes(self::SESSION_TTL_MINUTES)->timestamp,
            ]);
        } catch (Throwable $e) {
            $payment->forceFill(['status' => PaymentStatus::Failed])->save();
            Log::error('Stripe checkout session failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
            abort(502, 'We could not start the checkout. Please try again in a moment.');
        }

        $payment->forceFill(['stripe_session_id' => $session['id']])->save();

        return ['checkout_url' => $session['url'], 'session_id' => $session['id'], 'payment_id' => $payment->id];
    }

    /**
     * Called by the success page with the session id from Stripe's redirect.
     * Only the payment's owner may confirm it.
     */
    public function confirm(User $user, string $sessionId): Payment
    {
        $payment = Payment::where('stripe_session_id', $sessionId)->where('user_id', $user->id)->firstOrFail();

        if ($payment->status === PaymentStatus::Pending) {
            $session = $this->gateway->retrieveCheckoutSession($sessionId);

            if ($session['payment_status'] === 'paid') {
                $this->fulfil($session);
            } elseif ($session['status'] === 'expired') {
                $this->markStatus($sessionId, PaymentStatus::Expired);
            }
        }

        return $payment->fresh(['course']);
    }

    /**
     * Applies a verified webhook event. Unknown event types are ignored.
     */
    public function handleWebhookEvent(array $event): void
    {
        $object = $event['object'];

        match ($event['type']) {
            'checkout.session.completed' => ($object['payment_status'] ?? null) === 'paid' ? $this->fulfil($object) : null,
            'checkout.session.async_payment_succeeded' => $this->fulfil($object),
            'checkout.session.async_payment_failed' => $this->markStatus($object['id'], PaymentStatus::Failed),
            'checkout.session.expired' => $this->markStatus($object['id'], PaymentStatus::Expired),
            'charge.refunded' => $this->refund($object['payment_intent'] ?? null),
            default => null,
        };
    }

    /**
     * Marks the payment paid and grants access — idempotently. The row lock
     * means the webhook and the success page can race safely.
     */
    public function fulfil(array $session): void
    {
        DB::transaction(function () use ($session) {
            $payment = Payment::where('stripe_session_id', $session['id'])->lockForUpdate()->first();

            if (! $payment) {
                Log::warning('Stripe session for unknown payment', ['session' => $session['id']]);

                return;
            }
            if ($payment->status === PaymentStatus::Paid) {
                return; // already fulfilled
            }

            // Never trust a mismatched amount: the session must charge what we recorded.
            $expected = $this->toMinorUnits($payment->amount);
            if ((int) ($session['amount_total'] ?? -1) !== $expected
                || strtolower((string) ($session['currency'] ?? '')) !== strtolower($payment->currency)) {
                Log::critical('Stripe amount mismatch', ['payment_id' => $payment->id, 'session' => $session['id']]);
                $payment->forceFill(['status' => PaymentStatus::Failed])->save();

                return;
            }

            $payment->forceFill([
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
                'stripe_payment_intent_id' => $session['payment_intent'] ?? null,
            ])->save();

            $this->enrollments->grantAccess($payment->user, $payment->course, $payment);
        });
    }

    public function history(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->payments()
            ->with('course')
            ->where('status', '!=', PaymentStatus::Pending->value)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    private function markStatus(string $sessionId, PaymentStatus $status): void
    {
        Payment::where('stripe_session_id', $sessionId)
            ->where('status', PaymentStatus::Pending->value)
            ->update(['status' => $status->value]);
    }

    /** A refunded purchase no longer grants access to the course. */
    private function refund(?string $paymentIntentId): void
    {
        if (! $paymentIntentId) {
            return;
        }

        DB::transaction(function () use ($paymentIntentId) {
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->lockForUpdate()->first();
            if (! $payment || $payment->status === PaymentStatus::Refunded) {
                return;
            }

            $payment->forceFill(['status' => PaymentStatus::Refunded])->save();

            if ($payment->enrollment) {
                $this->enrollments->revokeAccess($payment->enrollment);
            }
        });
    }

    /** 29.99 → 2999 (Stripe amounts are in the currency's smallest unit). */
    private function toMinorUnits(string|float $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
