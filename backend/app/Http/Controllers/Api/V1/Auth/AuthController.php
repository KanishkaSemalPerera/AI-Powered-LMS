<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    /** POST /auth/register */
    public function register(RegisterRequest $request): JsonResponse
    {
        ['user' => $user, 'token' => $token] = $this->auth->register($request->validated());

        return ApiResponse::created($this->tokenPayload($user, $token), 'Welcome aboard! Your account has been created.');
    }

    /** POST /auth/login */
    public function login(LoginRequest $request): JsonResponse
    {
        ['user' => $user, 'token' => $token] = $this->auth->login(
            $request->validated('email'),
            $request->validated('password'),
        );

        return ApiResponse::success($this->tokenPayload($user, $token), 'Logged in successfully.');
    }

    /** POST /auth/logout */
    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user());

        return ApiResponse::success(null, 'Logged out successfully.');
    }

    /** GET /auth/me */
    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()));
    }

    private function tokenPayload($user, string $token): array
    {
        $minutes = config('sanctum.expiration');

        // The caller has just proven they are this user, so show them their private fields.
        request()->setUserResolver(fn () => $user);

        return [
            'user' => (new UserResource($user))->resolve(request()),
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $minutes ? now()->addMinutes($minutes)->toIso8601String() : null,
        ];
    }
}
