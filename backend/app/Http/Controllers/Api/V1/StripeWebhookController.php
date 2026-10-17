<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\PaymentGateway;
use App\Exceptions\InvalidWebhookException;
use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * POST /webhooks/stripe — called by Stripe, never by the browser.
 * The signature check (Stripe-Signature header) is what authenticates it.
 */
class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGateway $gateway, PaymentService $payments): JsonResponse
    {
        try {
            $event = $gateway->parseWebhookEvent($request->getContent(), $request->header('Stripe-Signature'));
        } catch (InvalidWebhookException $e) {
            Log::warning('Rejected Stripe webhook', ['reason' => $e->getMessage()]);

            return response()->json(['received' => false, 'message' => 'Invalid signature.'], 400);
        }

        $payments->handleWebhookEvent($event);

        // Stripe only needs a 2xx to stop retrying.
        return response()->json(['received' => true]);
    }
}
