<?php

namespace App\Contracts;

/**
 * The small slice of a payment provider the LMS needs. The Stripe
 * implementation is App\Services\Payments\StripeGateway; tests can bind a fake.
 * All methods return plain arrays so callers never depend on SDK objects.
 */
interface PaymentGateway
{
    public function isConfigured(): bool;

    /**
     * @return array{id: string, url: string}
     */
    public function createCheckoutSession(array $params): array;

    /**
     * @return array{id: string, status: ?string, payment_status: ?string, amount_total: ?int,
     *               currency: ?string, payment_intent: ?string, metadata: array}
     */
    public function retrieveCheckoutSession(string $sessionId): array;

    /**
     * Verifies the webhook signature and returns the event.
     *
     * @return array{id: string, type: string, object: array}
     *
     * @throws \App\Exceptions\InvalidWebhookException
     */
    public function parseWebhookEvent(string $payload, ?string $signature): array;
}
