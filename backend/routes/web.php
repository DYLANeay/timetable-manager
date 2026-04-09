<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// One-time setup route — protected by SETUP_SECRET env var
Route::get('/setup/{secret}', function (string $secret) {
    if ($secret !== env('SETUP_SECRET') || !env('SETUP_SECRET')) {
        abort(403);
    }
    Artisan::call('db:seed', ['--force' => true]);
    return response()->json(['message' => 'Seeded successfully.']);
});

// Serve the Vue SPA for all non-API routes
Route::get('/{any?}', function () {
    $spaIndex = public_path('index.html');

    if (file_exists($spaIndex)) {
        return response()->file($spaIndex);
    }

    return response('App not built yet.', 404);
})->where('any', '.*');
