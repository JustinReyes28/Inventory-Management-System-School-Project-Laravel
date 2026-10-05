<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $maxAttempts = 5;
        $request->ensureIsNotRateLimited($maxAttempts);

        if (! Auth::attempt($request->credentials(), $request->remember())) {
            throw ValidationException::withMessages([
                'username' => trans('auth.failed'),
            ]);
        }

        $request->clearLoginAttempts();
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(): RedirectResponse
    {
        Auth::guard('web')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return to_route('login');
    }
}
