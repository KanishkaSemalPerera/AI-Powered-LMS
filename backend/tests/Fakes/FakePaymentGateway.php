<?php

namespace Tests\Fakes;

use App\Contracts\PaymentGateway;
use Illuminate\Support\Str;
use RuntimeException;

/** In-memory stand-in for Stripe Checkout. */
class FakePaymentGateway implements PaymentGateway
{
    public bool $configured = true;

    public bool $failCreate = false;

    /** @var array<string, array> sessions by id */
    public array $sessions = [];

    /** @var array<int, array> params passed to createCheckoutSession */
    public array $created = [];

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public function createCheckoutSession(array $params): array
    {
        if ($this->failCreate) {
            throw new RuntimeException('Stripe is down');
        }

        $id = 'cs_test_'.Str::random(16);
        $this->created[] = $params;
        $this->sessions[$id] = [
            'id' => $id,
            'status' => 'open',
            'payment_status' => 'unpaid',
            'amount_total' => $params['line_items'][0]['price_data']['unit_amount'],
            'currency' => $params['line_items'][0]['price_data']['currency'],
            'payment_intent' => null,
            'metadata' => $params['metadata'],
        ];

        return ['id' => $id, 'url' => "https://checkout.stripe.test/{$id}"];
    }

    /** Simulates the student paying on Stripe's page. */
    public function pay(string $sessionId, ?int $amount = null): array
    {
        $this->sessions[$sessionId] = array_merge($this->sessions[$sessionId], [
            'status' => 'complete',
            'payment_status' => 'paid',
            'payment_intent' => 'pi_test_'.Str::random(12),
            'amount_total' => $amount ?? $this->sessions[$sessionId]['amount_total'],
        ]);

        return $this->sessions[$sessionId];
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        return $this->sessions[$sessionId] ?? throw new RuntimeException("No such session {$sessionId}");
    }

    public function parseWebhookEvent(string $payload, ?string $signature): array
    {
        $event = json_decode($payload, true);

        return ['id' => $event['id'], 'type' => $event['type'], 'object' => $event['data']['object']];
    }
}
