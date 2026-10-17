<?php

use Illuminate\Support\Facades\Route;

// This backend is a pure JSON API; the user interface lives in the React app.
Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'api' => url('/api/v1'),
        'health' => url('/api/v1/health'),
        'frontend' => config('services.frontend_url'),
    ]);
});
