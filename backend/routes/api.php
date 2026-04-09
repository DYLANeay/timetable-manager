<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\ShiftTemplateController;
use App\Http\Middleware\EnsureIsManager;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [LoginController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [LoginController::class, 'me']);
    Route::post('/auth/logout', [LoginController::class, 'logout']);

    Route::get('/shift-templates', [ShiftTemplateController::class, 'index']);

    Route::get('/shifts', [ShiftController::class, 'index']);
    Route::get('/shifts/my', [ShiftController::class, 'myShifts']);

    Route::middleware(EnsureIsManager::class)->group(function () {
        Route::post('/shifts', [ShiftController::class, 'store']);
        Route::put('/shifts/{shift}', [ShiftController::class, 'update']);
        Route::delete('/shifts/{shift}', [ShiftController::class, 'destroy']);
        Route::post('/shifts/bulk', [ShiftController::class, 'bulk']);
    });
});
