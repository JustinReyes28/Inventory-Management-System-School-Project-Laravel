<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::name('reports.')->group(function (): void {
    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('permission:view reports')
        ->name('index');
});
