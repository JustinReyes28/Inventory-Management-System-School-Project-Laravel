<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'username' => 'username',
            'password' => 'password',
        ];
    }

    /**
     * Attempt to authenticate using username and password only.
     *
     * @return array<string, string>
     */
    public function credentials(): array
    {
        return [
            'username' => $this->string('username')->trim()->toString(),
            'password' => $this->string('password')->toString(),
        ];
    }

    public function remember(): bool
    {
        return $this->boolean('remember');
    }

    /**
     * Ensure the request is not locked out.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(int $maxAttempts): void
    {
        if (! $this->hasTooManyLoginAttempts($maxAttempts)) {
            return;
        }

        event(new Lockout($this));

        $seconds = $this->secondsUntilAvailable($maxAttempts);

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ]);
    }

    public function hasTooManyLoginAttempts(int $maxAttempts): bool
    {
        return $this->limiter()->tooManyAttempts(
            $this->throttleKey(),
            $maxAttempts,
        );
    }

    public function incrementLoginAttempts(): void
    {
        $this->limiter()->hit($this->throttleKey());
    }

    public function clearLoginAttempts(): void
    {
        $this->limiter()->clear($this->throttleKey());
    }

    public function secondsUntilAvailable(int $maxAttempts): int
    {
        return $this->limiter()->availableIn($this->throttleKey());
    }

    protected function throttleKey(): string
    {
        $username = Str::lower(trim((string) $this->input('username')));

        return Str::transliterate($username.'|'.$this->ip());
    }

    protected function limiter(): RateLimiter
    {
        return app(RateLimiter::class);
    }
}
