<?php

use App\Http\Controllers\ActivityLogController;
use Illuminate\Support\Facades\Route;

Route::name('activity-logs.')->group(function (): void {
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->middleware('permission:view activity logs')
        ->name('index');
});
