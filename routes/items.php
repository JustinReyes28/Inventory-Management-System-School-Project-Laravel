<?php

use App\Http\Controllers\ItemController;
use Illuminate\Support\Facades\Route;

/*
 * Item routes. Named routes and public URLs match the original
 * Route::resource('items', ...) contract plus items.archive.
 */
Route::name('items.')->group(function (): void {
    Route::get('/items', [ItemController::class, 'index'])
        ->middleware('permission:view items')
        ->name('index');

    Route::get('/items/create', [ItemController::class, 'create'])
        ->middleware('permission:create items')
        ->name('create');

    Route::post('/items', [ItemController::class, 'store'])
        ->middleware('permission:create items')
        ->name('store');

    Route::get('/items/{item}', [ItemController::class, 'show'])
        ->middleware('permission:view items')
        ->name('show');

    Route::get('/items/{item}/edit', [ItemController::class, 'edit'])
        ->middleware('permission:update items')
        ->name('edit');

    Route::match(['put', 'patch'], '/items/{item}', [ItemController::class, 'update'])
        ->middleware('permission:update items')
        ->name('update');

    Route::patch('/items/{item}/archive', [ItemController::class, 'archive'])
        ->middleware('permission:update items')
        ->name('archive');

    Route::delete('/items/{item}', [ItemController::class, 'destroy'])
        ->middleware('permission:delete items')
        ->name('destroy');
});
