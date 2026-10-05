<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
 * Authentication (login, logout, registration, password recovery, profile and
 * password updates) is owned by Laravel Fortify — see config/fortify.php and
 * App\Providers\FortifyServiceProvider for the Inertia view wiring.
 */
Route::middleware('auth')->group(function (): void {
    Route::redirect('/', '/dashboard')->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/account', [AccountController::class, 'show'])->name('account');

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
