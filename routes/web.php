<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveTypeController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Authentication
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/me', [AuthController::class, 'user'])->name('me');

    // Leave Requests
    Route::get('/leave-requests', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
    Route::post('/leave-requests', [LeaveRequestController::class, 'store'])->name('leave-requests.store');
    Route::post('/leave-requests/calculate-days', [LeaveRequestController::class, 'calculateDays'])->name('leave-requests.calculate-days');
    Route::get('/leave-requests/{id}', [LeaveRequestController::class, 'show'])->name('leave-requests.show');
    Route::put('/leave-requests/{id}/manager-approve', [LeaveRequestController::class, 'managerApprove'])->name('leave-requests.manager-approve');
    Route::put('/leave-requests/{id}/hr-approve', [LeaveRequestController::class, 'hrApprove'])->name('leave-requests.hr-approve');
    Route::put('/leave-requests/{id}/reject', [LeaveRequestController::class, 'reject'])->name('leave-requests.reject');
    Route::post('/leave-requests/{id}/cancel', [LeaveRequestController::class, 'cancel'])->name('leave-requests.cancel');

    // HR & Admin Protected Routes
    Route::middleware('role:HR,ADMIN')->group(function () {
        Route::resource('employees', EmployeeController::class);
        Route::resource('leave-types', LeaveTypeController::class);
        Route::resource('holidays', HolidayController::class);
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });
});
