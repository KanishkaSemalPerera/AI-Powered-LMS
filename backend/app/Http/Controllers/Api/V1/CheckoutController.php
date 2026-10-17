<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Course;
use App\Services\PaymentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    /** POST /courses/{course}/checkout → { checkout_url } to redirect the browser to Stripe. */
    public function store(Request $request, Course $course): JsonResponse
    {
        return ApiResponse::created($this->payments->checkout($request->user(), $course), 'Redirecting to secure checkout…');
    }

    /** POST /checkout/confirm { session_id } — used by the success page. */
    public function confirm(Request $request): JsonResponse
    {
        $data = $request->validate(['session_id' => ['required', 'string', 'max:255']]);

        $payment = $this->payments->confirm($request->user(), $data['session_id']);

        $message = match ($payment->status) {
            PaymentStatus::Paid => 'Payment received. You now have full access to the course.',
            PaymentStatus::Pending => 'We are still waiting for the payment to be confirmed.',
            default => 'This payment was not completed.',
        };

        return ApiResponse::success(new PaymentResource($payment), $message);
    }
}
