<?php

use Illuminate\Support\Facades\Route;

// Serve the Vue SPA for all non-API routes
Route::get('/{any?}', function () {
    $spaIndex = public_path('index.html');

    if (file_exists($spaIndex)) {
        return response()->file($spaIndex);
    }

    return response('App not built yet.', 404);
})->where('any', '.*');
