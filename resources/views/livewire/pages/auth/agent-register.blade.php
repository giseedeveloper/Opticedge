<?php

use App\Models\User;
use App\Support\PlatformAuthSettings;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'phone' => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::withoutGlobalScopes()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'guest',
            'status' => 'active',
            'tenant_id' => null,
            'email_verified_at' => PlatformAuthSettings::requiresEmailVerificationOnLogin() ? null : now(),
        ]);

        event(new Registered($user));

        if (PlatformAuthSettings::requiresEmailVerificationOnLogin()) {
            $user->sendEmailVerificationNotification();

            session()->flash('status', 'Account created. Verify your email, then sign in with your email and password.');

            $this->redirect(route('login', absolute: false), navigate: true);

            return;
        }

        Auth::login($user);

        $this->redirect(route('guest.dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h2 class="text-2xl font-normal text-slate-900 mb-2">Create account</h2>
    <p class="text-sm text-slate-600 mb-6">Register with your email and password. A vendor admin will assign you as agent, team leader, or regional manager.</p>

    <form wire:submit="register" class="space-y-4">
        <div>
            <label for="name" class="block text-sm font-bold text-slate-900 mb-1">Name</label>
            <input wire:model="name" id="name" type="text" required autofocus
                class="w-full rounded-md border-slate-300 shadow-sm focus:border-[#fa8900] focus:ring focus:ring-[#fa8900] focus:ring-opacity-50 text-sm py-2">
            <x-input-error :messages="$errors->get('name')" class="mt-1 text-red-600 text-xs" />
        </div>

        <div>
            <label for="email" class="block text-sm font-bold text-slate-900 mb-1">Email</label>
            <input wire:model="email" id="email" type="email" required
                class="w-full rounded-md border-slate-300 shadow-sm focus:border-[#fa8900] focus:ring focus:ring-[#fa8900] focus:ring-opacity-50 text-sm py-2">
            <x-input-error :messages="$errors->get('email')" class="mt-1 text-red-600 text-xs" />
        </div>

        <div>
            <label for="phone" class="block text-sm font-bold text-slate-900 mb-1">Phone (optional)</label>
            <input wire:model="phone" id="phone" type="text"
                class="w-full rounded-md border-slate-300 shadow-sm focus:border-[#fa8900] focus:ring focus:ring-[#fa8900] focus:ring-opacity-50 text-sm py-2">
            <x-input-error :messages="$errors->get('phone')" class="mt-1 text-red-600 text-xs" />
        </div>

        <div>
            <label for="password" class="block text-sm font-bold text-slate-900 mb-1">Password</label>
            <input wire:model="password" id="password" type="password" required
                class="w-full rounded-md border-slate-300 shadow-sm focus:border-[#fa8900] focus:ring focus:ring-[#fa8900] focus:ring-opacity-50 text-sm py-2">
            <x-input-error :messages="$errors->get('password')" class="mt-1 text-red-600 text-xs" />
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-bold text-slate-900 mb-1">Confirm password</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password" required
                class="w-full rounded-md border-slate-300 shadow-sm focus:border-[#fa8900] focus:ring focus:ring-[#fa8900] focus:ring-opacity-50 text-sm py-2">
        </div>

        <div class="pt-2">
            <button type="submit"
                class="w-full bg-[#fa8900] hover:bg-[#e87b00] text-white font-medium py-2 px-4 rounded-md shadow-sm transition-colors text-sm">
                Create account
            </button>
        </div>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
        Already have an account?
        <a href="{{ route('login') }}" wire:navigate class="text-[#fa8900] hover:underline font-medium">Sign in</a>
    </p>

    @if (config('services.google.client_id'))
        <p class="mt-4 text-center text-sm text-slate-600">
            Or
            <a href="{{ route('auth.google') }}" class="text-[#fa8900] hover:underline font-medium">continue with Google</a>
        </p>
    @endif
</div>
