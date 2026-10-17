<?php

use App\Http\Controllers\Api\V1\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\Instructor\CourseController as InstructorCourseController;
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

// ------------------------------------------------------------------ Public catalogue
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
Route::get('/courses/{course:slug}', [CourseController::class, 'show'])->name('courses.show');

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

    // -------------------------------------------------------------- Instructor
    Route::middleware('role:instructor')->prefix('instructor')->name('instructor.')->group(function () {
        Route::get('/courses', [InstructorCourseController::class, 'index'])->name('courses.index');
        Route::post('/courses', [InstructorCourseController::class, 'store'])->name('courses.store');
        Route::get('/courses/{course}', [InstructorCourseController::class, 'show'])->name('courses.show');
        Route::put('/courses/{course}', [InstructorCourseController::class, 'update'])->name('courses.update');
        Route::delete('/courses/{course}', [InstructorCourseController::class, 'destroy'])->name('courses.destroy');
        Route::post('/courses/{course}/thumbnail', [InstructorCourseController::class, 'updateThumbnail'])->name('courses.thumbnail');
        Route::post('/courses/{course}/submit', [InstructorCourseController::class, 'submit'])->name('courses.submit');
    });

    // -------------------------------------------------------------- Admin
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/courses', [AdminCourseController::class, 'index'])->name('courses.index');
        Route::post('/courses/{course}/approve', [AdminCourseController::class, 'approve'])->name('courses.approve');
        Route::post('/courses/{course}/reject', [AdminCourseController::class, 'reject'])->name('courses.reject');

        Route::apiResource('categories', AdminCategoryController::class)->except('show');
    });
});
