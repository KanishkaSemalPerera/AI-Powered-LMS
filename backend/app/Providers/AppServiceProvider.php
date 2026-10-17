<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Services\Payments\StripeGateway;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Swap the payment provider (or a fake in tests) by rebinding this interface.
        $this->app->bind(PaymentGateway::class, StripeGateway::class);
    }

    public function boot(): void
    {
        // API Resources are wrapped by ApiResponse, not by a "data" key of their own.
        JsonResource::withoutWrapping();

        // Catch N+1 queries during development (lazy loading inside loops throws).
        Model::preventLazyLoading(! $this->app->isProduction());

        $this->configurePasswordRules();
        $this->configureRateLimiting();
        $this->configurePasswordResetLinks();
    }

    /**
     * Password policy used by registration, reset and change-password.
     * Production additionally rejects passwords found in public data breaches.
     */
    private function configurePasswordRules(): void
    {
        Password::defaults(function () {
            $rule = Password::min(8)->letters()->numbers();

            return $this->app->isProduction() ? $rule->mixedCase()->uncompromised() : $rule;
        });
    }

    private function configureRateLimiting(): void
    {
        // General API traffic, per user (or per IP for guests).
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Brute-force protection: 5 attempts per minute per email+IP, 20 per minute per IP.
        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by('login:'.$email.'|'.$request->ip()),
                Limit::perMinute(20)->by('login-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(10)->by($request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Discourages brute-forcing answers by rapid resubmission.
        RateLimiter::for('quiz-attempts', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Each checkout creates a Stripe session; stop accidental or abusive loops.
        RateLimiter::for('checkout', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }

    /** Password-reset emails link to the React app, not to a Laravel page. */
    private function configurePasswordResetLinks(): void
    {
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return config('services.frontend_url').'/reset-password?'.http_build_query([
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]);
        });
    }
}
