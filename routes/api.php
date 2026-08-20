<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveTypeController;
use Illuminate\Support\Facades\Route;

// Public / Integration APIs
Route::post('/login', [AuthController::class, 'login']);
Route::post('/integration/employee', [IntegrationController::class, 'syncEmployee']);

// Authenticated API Routes
Route::middleware('auth')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Leave Request APIs
    Route::get('/leave-requests', [LeaveRequestController::class, 'index']);
    Route::post('/leave-requests', [LeaveRequestController::class, 'store']);
    Route::post('/leave-requests/calculate-days', [LeaveRequestController::class, 'calculateDays']);
    Route::get('/leave-requests/{id}', [LeaveRequestController::class, 'show']);
    Route::put('/leave-requests/{id}/manager-approve', [LeaveRequestController::class, 'managerApprove']);
    Route::put('/leave-requests/{id}/hr-approve', [LeaveRequestController::class, 'hrApprove']);
    Route::put('/leave-requests/{id}/reject', [LeaveRequestController::class, 'reject']);
    Route::post('/leave-requests/{id}/cancel', [LeaveRequestController::class, 'cancel']);

    // Management APIs (HR / Admin)
    Route::middleware('role:HR,ADMIN')->group(function () {
        Route::apiResource('employees', EmployeeController::class);
        Route::apiResource('leave-types', LeaveTypeController::class);
        Route::apiResource('holidays', HolidayController::class);
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
    });
});
