<?php

use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — all prefixed with /api/v1 (see bootstrap/app.php)
|--------------------------------------------------------------------------
|
| Route groups per role (public, authenticated, student, instructor, admin)
| are added phase by phase.
|
*/

Route::get('/health', HealthController::class)->name('health');
