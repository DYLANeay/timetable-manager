<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PublicHolidayController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\ShiftTemplateController;
use App\Http\Controllers\SwapRequestController;
use App\Http\Middleware\EnsureIsManager;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [LoginController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [LoginController::class, 'me']);
    Route::post('/auth/logout', [LoginController::class, 'logout']);
    Route::put('/auth/password', [LoginController::class, 'changePassword']);
    Route::put('/auth/avatar', [LoginController::class, 'updateAvatar']);

    Route::get('/shift-templates', [ShiftTemplateController::class, 'index']);

    Route::get('/public-holidays', [PublicHolidayController::class, 'index']);

    Route::get('/shifts', [ShiftController::class, 'index']);
    Route::get('/shifts/my', [ShiftController::class, 'myShifts']);

    Route::get('/leave-requests', [LeaveRequestController::class, 'index']);
    Route::post('/leave-requests', [LeaveRequestController::class, 'store']);
    Route::delete('/leave-requests/{leaveRequest}', [LeaveRequestController::class, 'cancel']);
    Route::get('/leave-requests/balance', [LeaveRequestController::class, 'balance']);

    Route::get('/swap-requests', [SwapRequestController::class, 'index']);
    Route::post('/swap-requests', [SwapRequestController::class, 'store']);
    Route::put('/swap-requests/{swapRequest}/respond', [SwapRequestController::class, 'respond']);
    Route::put('/swap-requests/{swapRequest}/cancel', [SwapRequestController::class, 'cancel']);

    Route::middleware(EnsureIsManager::class)->group(function () {
        Route::put('/swap-requests/{swapRequest}/decide', [SwapRequestController::class, 'decide']);
        Route::post('/shifts', [ShiftController::class, 'store']);
        Route::put('/shifts/{shift}', [ShiftController::class, 'update']);
        Route::delete('/shifts/{shift}', [ShiftController::class, 'destroy']);
        Route::post('/shifts/bulk', [ShiftController::class, 'bulk']);

        Route::put('/leave-requests/{leaveRequest}/decide', [LeaveRequestController::class, 'decide']);

        Route::post('/public-holidays', [PublicHolidayController::class, 'store']);
        Route::delete('/public-holidays/{publicHoliday}', [PublicHolidayController::class, 'destroy']);

        Route::get('/employees', [EmployeeController::class, 'index']);
        Route::post('/employees', [EmployeeController::class, 'store']);
        Route::put('/employees/{employee}', [EmployeeController::class, 'update']);
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy']);
    });
});
