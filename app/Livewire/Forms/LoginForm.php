<?php

namespace App\Livewire\Forms;

use App\Models\User;
use App\Support\TenantSuspension;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = trim($this->email);
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL) !== false;
        $credentials = $isEmail
            ? ['email' => $login, 'password' => $this->password]
            : ['phone' => $login, 'password' => $this->password];

        if (! Auth::attempt($credentials, $this->remember)) {
            // fallback: allow phone inputs with spaces or separators (e.g. +255 712 345 678)
            if (! $isEmail) {
                $normalizedPhone = preg_replace('/\D+/', '', $login) ?? '';

                if ($normalizedPhone !== '') {
                    $byPhone = User::query()->get(['id', 'phone'])->first(function (User $user) use ($normalizedPhone) {
                        $storedPhone = preg_replace('/\D+/', '', (string) ($user->phone ?? '')) ?? '';
                        return $storedPhone !== '' && $storedPhone === $normalizedPhone;
                    });

                    if ($byPhone && Auth::attempt(['email' => $byPhone->email, 'password' => $this->password], $this->remember)) {
                        RateLimiter::clear($this->throttleKey());
                        return;
                    }
                }
            }

            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        $blockedReason = TenantSuspension::blocksLoginForUser(Auth::user());
        if ($blockedReason !== null) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'form.email' => $blockedReason,
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}
