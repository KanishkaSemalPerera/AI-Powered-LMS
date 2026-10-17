<?php

use App\Http\Controllers\Api\V1\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\V1\AiController;
use App\Http\Controllers\Api\V1\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\EnrollmentController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\StripeWebhookController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\Instructor\CourseController as InstructorCourseController;
use App\Http\Controllers\Api\V1\Instructor\CurriculumController;
use App\Http\Controllers\Api\V1\Instructor\QuizController as InstructorQuizController;
use App\Http\Controllers\Api\V1\QuizAttemptController;
use App\Http\Controllers\Api\V1\LearnController;
use App\Http\Controllers\Api\V1\LessonVideoController;
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
Route::get('/courses/{course:slug}/lessons/{lesson}/preview', [LearnController::class, 'preview'])->name('courses.lessons.preview');

// Uploaded lesson video playback. Access is granted by the temporary URL signature.
Route::get('/lessons/{lesson}/video', LessonVideoController::class)->middleware('signed')->name('lessons.video');

// Stripe webhook: authenticated by its signature, and exempt from the per-IP API rate limit.
Route::post('/webhooks/stripe', StripeWebhookController::class)->withoutMiddleware('throttle:api')->name('webhooks.stripe');

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

    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');

    // -------------------------------------------------------------- Course player
    // Enrolled students, plus the owner/admin in preview mode (CoursePolicy::learn).
    Route::prefix('learn/{course:slug}')->name('learn.')->group(function () {
        Route::get('/', [LearnController::class, 'show'])->name('show');
        Route::get('/lessons/{lesson}', [LearnController::class, 'lesson'])->name('lesson');
        Route::post('/lessons/{lesson}/complete', [LearnController::class, 'complete'])->name('complete');
        Route::delete('/lessons/{lesson}/complete', [LearnController::class, 'uncomplete'])->name('uncomplete');

        Route::get('/lessons/{lesson}/quiz', [QuizAttemptController::class, 'show'])->name('quiz.show');
        Route::get('/lessons/{lesson}/quiz/attempts', [QuizAttemptController::class, 'index'])->name('quiz.attempts.index');
        Route::post('/lessons/{lesson}/quiz/attempts', [QuizAttemptController::class, 'store'])->middleware('throttle:quiz-attempts')->name('quiz.attempts.store');

        // AI study assistant for this lesson
        Route::post('/lessons/{lesson}/assistant', [AiController::class, 'ask'])->middleware('throttle:ai')->name('assistant');
    });

    Route::get('/ai/usage', [AiController::class, 'usage'])->name('ai.usage');
    Route::get('/quiz-attempts/{attempt}', [QuizAttemptController::class, 'showAttempt'])->name('quiz-attempts.show');

    // -------------------------------------------------------------- Student
    Route::middleware('role:student')->group(function () {
        Route::post('/courses/{course}/enroll', [EnrollmentController::class, 'store'])->name('courses.enroll');
        Route::post('/courses/{course}/checkout', [CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('courses.checkout');
        Route::post('/checkout/confirm', [CheckoutController::class, 'confirm'])->name('checkout.confirm');
        Route::get('/my/courses', [EnrollmentController::class, 'index'])->name('my.courses');
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

        // Course builder
        Route::get('/courses/{course}/curriculum', [CurriculumController::class, 'show'])->name('curriculum.show');
        Route::put('/courses/{course}/curriculum/order', [CurriculumController::class, 'reorder'])->name('curriculum.reorder');
        Route::post('/courses/{course}/sections', [CurriculumController::class, 'storeSection'])->name('sections.store');
        Route::put('/sections/{section}', [CurriculumController::class, 'updateSection'])->name('sections.update');
        Route::delete('/sections/{section}', [CurriculumController::class, 'destroySection'])->name('sections.destroy');
        Route::post('/sections/{section}/lessons', [CurriculumController::class, 'storeLesson'])->name('lessons.store');
        Route::get('/lessons/{lesson}', [CurriculumController::class, 'showLesson'])->name('lessons.show');
        Route::put('/lessons/{lesson}', [CurriculumController::class, 'updateLesson'])->name('lessons.update');
        Route::delete('/lessons/{lesson}', [CurriculumController::class, 'destroyLesson'])->name('lessons.destroy');
        Route::post('/lessons/{lesson}/video', [CurriculumController::class, 'uploadVideo'])->name('lessons.video.store');
        Route::delete('/lessons/{lesson}/video', [CurriculumController::class, 'destroyVideo'])->name('lessons.video.destroy');

        // Lesson quiz editor
        Route::get('/lessons/{lesson}/quiz', [InstructorQuizController::class, 'show'])->name('lessons.quiz.show');
        Route::put('/lessons/{lesson}/quiz', [InstructorQuizController::class, 'upsert'])->name('lessons.quiz.upsert');
        Route::delete('/lessons/{lesson}/quiz', [InstructorQuizController::class, 'destroy'])->name('lessons.quiz.destroy');

        // AI tools
        Route::middleware('throttle:ai')->group(function () {
            Route::post('/lessons/{lesson}/ai/quiz', [AiController::class, 'generateQuiz'])->name('ai.quiz');
            Route::post('/ai/course-description', [AiController::class, 'courseDescription'])->name('ai.description');
        });
    });

    // -------------------------------------------------------------- Admin
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/courses', [AdminCourseController::class, 'index'])->name('courses.index');
        Route::post('/courses/{course}/approve', [AdminCourseController::class, 'approve'])->name('courses.approve');
        Route::post('/courses/{course}/reject', [AdminCourseController::class, 'reject'])->name('courses.reject');

        Route::apiResource('categories', AdminCategoryController::class)->except('show');
    });
});
