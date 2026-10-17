<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — all prefixed with /api/v1 (see bootstrap/app.php)
|--------------------------------------------------------------------------
|
| Middleware aliases:
|   auth:sanctum   Bearer token required
|   active         blocks deactivated accounts
|   role:a,b       restricts to the listed roles
|   throttle:x     rate limiters defined in AppServiceProvider
|
*/

Route::get('/health', HealthController::class)->name('health');

// ------------------------------------------------------------------ Auth
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register')->name('register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login');
    Route::post('/forgot-password', [PasswordResetController::class, 'forgot'])->middleware('throttle:password-reset')->name('password.forgot');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:password-reset')->name('password.reset');

    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
    });
});

// ------------------------------------------------------------------ Authenticated (any role)
Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::post('/avatar', [ProfileController::class, 'updateAvatar'])->name('avatar.update');
        Route::delete('/avatar', [ProfileController::class, 'destroyAvatar'])->name('avatar.destroy');
        Route::put('/password', [ProfileController::class, 'changePassword'])->name('password');
    });
});
