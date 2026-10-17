<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Lightweight status endpoint used by the frontend and monitoring to confirm
 * the API is reachable and the database connection works.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::connection()->getPdo();
            $database = 'connected';
        } catch (Throwable) {
            $database = 'unavailable';
        }

        $data = [
            'app' => config('app.name'),
            'version' => 'v1',
            'environment' => app()->environment(),
            'database' => $database,
            'ai_configured' => filled(config('services.anthropic.api_key')),
            'stripe_configured' => filled(config('services.stripe.secret')),
            'time' => now()->toIso8601String(),
        ];

        return $database === 'connected'
            ? ApiResponse::success($data, 'API is healthy.')
            : ApiResponse::error('API is running but the database is unreachable.', 503, null, $data);
    }
}
