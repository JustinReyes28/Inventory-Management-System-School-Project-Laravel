<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

/*
 * Every user may read their own notifications (ownership is enforced by
 * UserNotificationPolicy); managing read state needs the matching permission.
 */
Route::name('notifications.')->group(function (): void {
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->middleware('permission:view notifications')
        ->name('index');

    Route::get('/notifications/recent', [NotificationController::class, 'recent'])
        ->middleware('permission:view notifications')
        ->name('recent');

    Route::match(['post', 'patch'], '/notifications/read-all', [NotificationController::class, 'readAll'])
        ->middleware('permission:manage notifications')
        ->name('read-all');

    Route::match(['post', 'patch'], '/notifications/{notification}/read', [NotificationController::class, 'read'])
        ->middleware('permission:manage notifications')
        ->name('read');
});
