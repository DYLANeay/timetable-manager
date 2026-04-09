<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [LoginController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [LoginController::class, 'me']);
    Route::post('/auth/logout', [LoginController::class, 'logout']);
});
