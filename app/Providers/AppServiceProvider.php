<?php

namespace App\Providers;

use App\Models\User;
use App\Models\UserNotification;
use App\Policies\UserNotificationPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Role/permission authority lives in spatie/laravel-permission
        // (routes use the role/permission middleware, policies and form
        // requests ask $user->can(...)). Only model policies remain here;
        // login throttling lives in FortifyServiceProvider.
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(UserNotification::class, UserNotificationPolicy::class);
    }
}
