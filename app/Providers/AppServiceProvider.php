<?php

namespace App\Providers;

use App\Models\User;
use App\Models\UserNotification;
use App\Policies\UserNotificationPolicy;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        // requests ask $user->can(...)). Only model policies remain here.
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(UserNotification::class, UserNotificationPolicy::class);

        RateLimiter::for('login', function (Request $request): Limit {
            $username = Str::lower(trim((string) $request->input('username')));
            $key = Str::transliterate($username.'|'.$request->ip());

            return Limit::perMinute(5)->by($key);
        });
    }
}
