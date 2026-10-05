<?php

use App\Http\Controllers\BatchController;
use Illuminate\Support\Facades\Route;

/*
 * Batch routes. Named routes and public URLs match the original
 * Route::resource('batches', ...) contract.
 */
Route::name('batches.')->group(function (): void {
    Route::get('/batches', [BatchController::class, 'index'])
        ->middleware('permission:view batches')
        ->name('index');

    Route::get('/batches/create', [BatchController::class, 'create'])
        ->middleware('permission:create batches')
        ->name('create');

    Route::post('/batches', [BatchController::class, 'store'])
        ->middleware('permission:create batches')
        ->name('store');

    Route::get('/batches/{batch}', [BatchController::class, 'show'])
        ->middleware('permission:view batches')
        ->name('show');

    Route::get('/batches/{batch}/edit', [BatchController::class, 'edit'])
        ->middleware('permission:update batches')
        ->name('edit');

    Route::match(['put', 'patch'], '/batches/{batch}', [BatchController::class, 'update'])
        ->middleware('permission:update batches')
        ->name('update');

    Route::delete('/batches/{batch}', [BatchController::class, 'destroy'])
        ->middleware('permission:delete batches')
        ->name('destroy');
});
