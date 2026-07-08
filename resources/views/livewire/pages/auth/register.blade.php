<?php

use App\Models\User;
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

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'phone' => ['required', 'string', 'max:20', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = 'customer';

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h2 class="text-2xl font-normal text-slate-900 mb-6">Create account</h2>

    <form wire:submit="register">
        <!-- Name -->
        <div class="mb-4">
            <label for="name" class="block text-sm font-bold text-slate-900 mb-1">Your name</label>
            <input wire:model="name" id="name" type="text" name="name" required autofocus autocomplete="name"
                placeholder="First and last name"
                class="w-full rounded-md border-slate-300 shadow-sm focus:border-[#fa8900] focus:ring focus:ring-[#fa8900] focus:ring-opacity-50 text-sm py-2">
            <x-input-error :messages="$errors->get('name')" class="mt-2 text-red-600 text-xs" />
        </div>

        <!-- Email Address -->
        <div class="mb-4">
            <label for="email" class="block text-sm font-bold text-slate-900 mb-1">Email</label>
            <input wire:model="email" id="email" type="email" name="email" required autocomplete="username"
                class="w-full rounded-md border-slate-300 shadow-sm focus:border-[#fa8900] focus:ring focus:ring-[#fa8900] focus:ring-opacity-50 text-sm py-2">
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-600 text-xs" />
        </div>

        <!-- Phone -->
        <div class="mb-4">
            <label for="phone" class="block text-sm font-bold text-slate-900 mb-1">Phone Number</label>
            <input wire:model="phone" id="phone" type="text" name="phone" required autocomplete="tel"
                placeholder="07XXXXXXXX"
                class="w-full rounded-md border-slate-300 shadow-sm focus:border-[#fa8900] focus:ring focus:ring-[#fa8900] focus:ring-opacity-50 text-sm py-2">
            <x-input-error :messages="$errors->get('phone')" class="mt-2 text-red-600 text-xs" />
        </div>

        <!-- Password -->
        <div class="mb-4">
            <label for="password" class="block text-sm font-bold text-slate-900 mb-1">Password</label>
            <input wire:model="password" id="password" type="password" name="password" required
                autocomplete="new-password" placeholder="At least 6 characters"
                class="w-full rounded-md border-slate-300 shadow-sm focus:border-[#fa8900] focus:ring focus:ring-[#fa8900] focus:ring-opacity-50 text-sm py-2">
            <p class="text-xs text-slate-500 mt-1 italic">Passwords must be at least 6 characters.</p>
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-600 text-xs" />
        </div>

        <!-- Confirm Password -->
        <div class="mb-6">
            <label for="password_confirmation" class="block text-sm font-bold text-slate-900 mb-1">Re-enter
                password</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password"
                name="password_confirmation" required autocomplete="new-password"
                class="w-full rounded-md border-slate-300 shadow-sm focus:border-[#fa8900] focus:ring focus:ring-[#fa8900] focus:ring-opacity-50 text-sm py-2">
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-red-600 text-xs" />
        </div>

        <div class="mt-6">
            <button type="submit"
                class="w-full bg-[#fa8900] hover:bg-[#e87b00] text-white font-medium py-2 px-4 rounded-md shadow-sm transition-colors text-sm">
                Create your OpticEdgeAfrica account
            </button>
        </div>

        <div class="mt-6 text-xs text-slate-600">
            By creating an account, you agree to OpticEdgeAfrica's <a href="{{ route('terms') }}"
                class="text-blue-600 hover:text-[#fa8900] hover:underline">Terms of Service</a> and <a
                href="{{ route('privacy') }}"
                class="text-blue-600 hover:text-[#fa8900] hover:underline">Privacy Policy</a>.
        </div>

        <div class="mt-8 border-t border-slate-200 pt-6">
            <p class="text-sm text-slate-900">
                Already have an account?
                <a href="{{ route('login') }}" wire:navigate
                    class="text-blue-600 hover:text-[#fa8900] hover:underline font-medium">
                    Sign in <span aria-hidden="true">&rarr;</span>
                </a>
            </p>
        </div>
    </form>

    <div class="mt-4 text-center">
        <a href="{{ route('dealer.register') }}" class="text-xs text-slate-500 hover:text-[#fa8900] hover:underline">
            Want to become a seller? Register as a Dealer
        </a>
    </div>
</div>