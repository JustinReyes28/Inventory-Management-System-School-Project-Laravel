<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::redirect('/', '/dashboard')->name('home');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('categories', CategoryController::class);
    Route::resource('items', ItemController::class);
    Route::patch('/items/{item}/archive', [ItemController::class, 'archive'])->name('items.archive');
    Route::resource('batches', BatchController::class);

    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->name('activity-logs.index');

    Route::middleware('can:manage-users')->group(function (): void {
        Route::resource('users', UserController::class);
    });

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');
    Route::get('/notifications/recent', [NotificationController::class, 'recent'])
        ->name('notifications.recent');
    Route::match(['post', 'patch'], '/notifications/read-all', [NotificationController::class, 'readAll'])
        ->name('notifications.read-all');
    Route::match(['post', 'patch'], '/notifications/{notification}/read', [NotificationController::class, 'read'])
        ->name('notifications.read');
});
