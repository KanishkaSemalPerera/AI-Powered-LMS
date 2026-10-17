<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\ForceJsonResponse;
use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);

        // General per-user/IP limit for every API route ("api" limiter in AppServiceProvider).
        $middleware->throttleApi();

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'active' => EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $isApi = fn (Request $request) => $request->is('api/*');

        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $isApi($request) || $request->expectsJson());

        // Convert every exception on API routes into the standard response envelope.
        $exceptions->render(function (ValidationException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return ApiResponse::error($e->getMessage(), $e->status, $e->errors());
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return ApiResponse::error('Unauthenticated. Please log in.', 401);
            }
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                $retryAfter = $e->getHeaders()['Retry-After'] ?? null;

                return ApiResponse::error(
                    'Too many requests. Please slow down and try again shortly.',
                    429,
                    null,
                    ['retry_after' => $retryAfter !== null ? (int) $retryAfter : null]
                )->withHeaders($e->getHeaders());
            }
        });

        // Laravel converts ModelNotFound -> 404 and AuthorizationException -> 403 before this runs.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                $status = $e->getStatusCode();
                $message = match ($status) {
                    403 => $e->getMessage() ?: 'You are not allowed to perform this action.',
                    404 => 'The requested resource was not found.',
                    405 => 'This HTTP method is not supported for this endpoint.',
                    default => $e->getMessage() ?: 'Request failed.',
                };

                return ApiResponse::error($message, $status)->withHeaders($e->getHeaders());
            }
        });

        // Anything else is an unexpected server error; hide internals unless debugging.
        $exceptions->render(function (Throwable $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return ApiResponse::error(
                    config('app.debug') ? $e->getMessage() : 'Something went wrong on our side. Please try again.',
                    500,
                    config('app.debug') ? ['exception' => [class_basename($e)]] : null
                );
            }
        });
    })->create();
