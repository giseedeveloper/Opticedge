<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $user = auth()->user();
        $url = $user->isSuperadmin()
            ? route('superadmin.dashboard', absolute: false)
            : ($user->role === 'admin' && \App\Support\TenantSuspension::adminHasRestrictedAccess($user)
                ? route('admin.tenant.edit', absolute: false)
                : (in_array($user->role, ['admin', 'subadmin'], true)
                    ? route('admin.dashboard', absolute: false)
                    : route('dashboard', absolute: false)));

        $this->redirectIntended(default: $url, navigate: true);
    }
}; ?>

@php
    $inputBase =
        'auth-input w-full rounded-xl border bg-slate-50/80 text-slate-900 text-[15px] leading-snug placeholder:text-slate-400 transition duration-200 shadow-sm outline-none ' .
        'border-slate-200/90 hover:border-slate-300 hover:bg-white ' .
        'focus:border-[#fa8900] focus:bg-white focus:ring-2 focus:ring-[#fa8900]/25 focus:shadow-md ' .
        'disabled:opacity-60 disabled:cursor-not-allowed';
@endphp

<div>
    <div class="text-center mb-8">
        <h2 class="text-2xl font-semibold tracking-tight text-[#232f3e]">Sign in</h2>
        <p class="mt-1.5 text-sm text-slate-500">Use your account email and password.</p>
    </div>

    <x-auth-session-status class="mb-5 rounded-xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900" :status="session('status')" />

    <form wire:submit="login" class="space-y-5">
        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-semibold text-slate-800 mb-2">Email or mobile phone number</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400" aria-hidden="true">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25H4.5a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                </span>
                <input wire:model="form.email" id="email" type="email" name="email" required autofocus
                    autocomplete="username" placeholder="you@example.com"
                    class="{{ $inputBase }} pl-11 py-3 pr-4" />
            </div>
            <x-input-error :messages="array_merge($errors->get('form.email'), $errors->get('email'))" class="mt-2 text-red-600 text-xs font-medium" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between gap-2 mb-2">
                <label for="password" class="block text-sm font-semibold text-slate-800">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" wire:navigate
                        class="text-xs font-medium text-[#007185] hover:text-[#fa8900] transition-colors">
                        Forgot password?
                    </a>
                @endif
            </div>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400" aria-hidden="true">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </span>
                <input wire:model="form.password" id="password" type="password" name="password" required
                    autocomplete="current-password" placeholder="Enter your password"
                    class="{{ $inputBase }} pl-11 py-3 pr-4" />
            </div>
            <x-input-error :messages="$errors->get('form.password')" class="mt-2 text-red-600 text-xs font-medium" />
        </div>

        <label for="remember" class="flex items-start gap-3 cursor-pointer group rounded-lg px-1 py-0.5 -mx-1 hover:bg-slate-50/80 transition-colors">
            <span class="mt-0.5 flex h-5 shrink-0 items-center justify-center">
                <input wire:model="form.remember" id="remember" type="checkbox" name="remember"
                    class="h-4 w-4 rounded-md border-slate-300 text-[#fa8900] shadow-sm focus:ring-2 focus:ring-[#fa8900]/30 focus:ring-offset-0 cursor-pointer" />
            </span>
            <span class="text-sm text-slate-600 group-hover:text-slate-800 leading-snug">Keep me signed in on this device.</span>
        </label>

        <div class="pt-1">
            <button type="submit"
                class="w-full rounded-xl bg-[#fa8900] hover:bg-[#e87b00] active:bg-[#d96f00] text-white font-semibold py-3 px-4 text-[15px] shadow-md shadow-orange-900/10 hover:shadow-lg hover:shadow-orange-900/15 transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#fa8900] focus-visible:ring-offset-2">
                Sign in
            </button>
        </div>
    </form>
</div>
