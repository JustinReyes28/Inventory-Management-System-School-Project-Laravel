<?php

use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

/*
 * Category routes. Named routes and public URLs match the original
 * Route::resource('categories', ...) contract.
 */
Route::name('categories.')->group(function (): void {
    Route::get('/categories', [CategoryController::class, 'index'])
        ->middleware('permission:view categories')
        ->name('index');

    Route::get('/categories/create', [CategoryController::class, 'create'])
        ->middleware('permission:create categories')
        ->name('create');

    Route::post('/categories', [CategoryController::class, 'store'])
        ->middleware('permission:create categories')
        ->name('store');

    Route::get('/categories/{category}', [CategoryController::class, 'show'])
        ->middleware('permission:view categories')
        ->name('show');

    Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])
        ->middleware('permission:update categories')
        ->name('edit');

    Route::match(['put', 'patch'], '/categories/{category}', [CategoryController::class, 'update'])
        ->middleware('permission:update categories')
        ->name('update');

    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
        ->middleware('permission:delete categories')
        ->name('destroy');
});
