<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks deactivated accounts even if they still hold a valid token,
 * and revokes that token so the client is logged out.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            $token = $user->currentAccessToken();
            if ($token instanceof PersonalAccessToken) {
                $token->delete();
            }

            // errors.account lets the SPA recognise this case and sign the user out.
            return ApiResponse::error(
                'Your account has been deactivated. Please contact support.',
                403,
                ['account' => ['deactivated']]
            );
        }

        return $next($request);
    }
}
