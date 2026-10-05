<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
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

    /*
     * Modular application routes. Each file registers its named resource
     * routes and attaches the granular Spatie permission middleware; the
     * `auth` guard for all of them is applied by this group.
     */
    require __DIR__.'/categories.php';
    require __DIR__.'/items.php';
    require __DIR__.'/batches.php';
    require __DIR__.'/activity-logs.php';
    require __DIR__.'/reports.php';
    require __DIR__.'/notifications.php';
    require __DIR__.'/users.php';
});
