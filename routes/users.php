<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
 * User management. Each action is protected by its own Spatie permission;
 * UserPolicy keeps the last-admin and self-delete safeguards on top.
 */
Route::name('users.')->group(function (): void {
    Route::get('/users', [UserController::class, 'index'])
        ->middleware('permission:view users')
        ->name('index');

    Route::get('/users/create', [UserController::class, 'create'])
        ->middleware('permission:create users')
        ->name('create');

    Route::post('/users', [UserController::class, 'store'])
        ->middleware('permission:create users')
        ->name('store');

    Route::get('/users/{user}', [UserController::class, 'show'])
        ->middleware('permission:view users')
        ->name('show');

    Route::get('/users/{user}/edit', [UserController::class, 'edit'])
        ->middleware('permission:update users')
        ->name('edit');

    Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update'])
        ->middleware('permission:update users')
        ->name('update');

    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:delete users')
        ->name('destroy');
});
