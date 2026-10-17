<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Exceptions\InvalidWebhookException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use UnexpectedValueException;

/** Stripe Checkout implementation of PaymentGateway (stripe/stripe-php). */
class StripeGateway implements PaymentGateway
{
    private ?StripeClient $client = null;

    public function isConfigured(): bool
    {
        return filled(config('services.stripe.secret'));
    }

    public function createCheckoutSession(array $params): array
    {
        $session = $this->client()->checkout->sessions->create($params);

        return ['id' => $session->id, 'url' => $session->url];
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        $session = $this->client()->checkout->sessions->retrieve($sessionId);

        return [
            'id' => $session->id,
            'status' => $session->status,
            'payment_status' => $session->payment_status,
            'amount_total' => $session->amount_total,
            'currency' => $session->currency,
            'payment_intent' => is_string($session->payment_intent) ? $session->payment_intent : $session->payment_intent?->id,
            'metadata' => $session->metadata?->toArray() ?? [],
        ];
    }

    public function parseWebhookEvent(string $payload, ?string $signature): array
    {
        $secret = config('services.stripe.webhook_secret');

        if (blank($secret)) {
            throw new InvalidWebhookException('STRIPE_WEBHOOK_SECRET is not configured.');
        }

        try {
            // Verifies the HMAC signature and rejects events older than 5 minutes (replay protection).
            $event = Webhook::constructEvent($payload, (string) $signature, $secret);
        } catch (SignatureVerificationException|UnexpectedValueException $e) {
            throw new InvalidWebhookException($e->getMessage(), previous: $e);
        }

        return [
            'id' => $event->id,
            'type' => $event->type,
            'object' => $event->data->object->toArray(),
        ];
    }

    private function client(): StripeClient
    {
        return $this->client ??= new StripeClient([
            'api_key' => config('services.stripe.secret'),
            'max_network_retries' => 2,
        ]);
    }
}
