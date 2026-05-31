<?php

use App\Models\Package;
use App\Models\User;
use App\Models\VendorRegistrationIntent;
use App\Services\VendorSubscriptionPaymentService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.marketing')] class extends Component {
    public Package $package;

    public int $step = 1;

    public ?int $intentId = null;

    // Step 1 — vendor
    public string $vendor_name = '';
    public string $brand_name = '';

    // Step 2 — personal / admin
    public string $admin_name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $password_confirmation = '';

    // Step 3 — payment
    public string $payment_phone = '';

    public function mount(Package $package): void
    {
        if (! $package->is_active) {
            abort(404);
        }

        $this->package = $package;
        $this->payment_phone = $this->phone;
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validate([
                'vendor_name' => ['required', 'string', 'max:255'],
                'brand_name' => ['nullable', 'string', 'max:255'],
            ]);
        }

        if ($this->step === 2) {
            $this->validate([
                'admin_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
                'phone' => ['required', 'string', 'max:32'],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);

            $this->payment_phone = $this->payment_phone ?: $this->phone;

            $payload = [
                'package_id' => $this->package->id,
                'vendor_name' => $this->vendor_name,
                'brand_name' => $this->brand_name ?: $this->vendor_name,
                'slug' => Str::slug($this->vendor_name),
                'admin_name' => $this->admin_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'password' => Hash::make($this->password),
                'status' => VendorRegistrationIntent::STATUS_DRAFT,
            ];

            if ($this->intentId) {
                $intent = VendorRegistrationIntent::findOrFail($this->intentId);
                $intent->update($payload);
            } else {
                $intent = VendorRegistrationIntent::create($payload);
                $this->intentId = $intent->id;
            }

            session(['vendor_subscribe_plain_password.'.$intent->id => Crypt::encryptString($this->password)]);
        }

        $this->step++;
    }

    public function prevStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function startPayment(VendorSubscriptionPaymentService $payments): void
    {
        $this->validate([
            'payment_phone' => ['required', 'string', 'max:32'],
        ]);

        if (! $this->intentId) {
            $this->addError('payment_phone', 'Please complete the previous steps first.');

            return;
        }

        $intent = VendorRegistrationIntent::findOrFail($this->intentId);

        try {
            $payments->normalizePhone($this->payment_phone);
        } catch (\InvalidArgumentException $e) {
            $this->addError('payment_phone', $e->getMessage());

            return;
        }

        try {
            $payments->initiatePayment($intent, $this->payment_phone);
        } catch (\Throwable $e) {
            $this->addError('payment_phone', $e->getMessage());

            return;
        }

        $this->redirect(route('vendor.subscribe.processing', $intent), navigate: true);
    }
}; ?>

<div class="max-w-2xl mx-auto px-4 py-8 sm:py-12">
    <div class="admin-clay-panel p-6 sm:p-8">
        <div class="mb-6">
            <p class="text-xs font-bold uppercase tracking-[0.15em] text-[#fa8900]">Subscribe</p>
            <h1 class="text-2xl font-bold text-[#232f3e] mt-1">{{ $package->name }}</h1>
            <p class="text-slate-600 mt-1">
                <span class="font-semibold text-[#232f3e]">{{ $package->formattedPrice() }}</span>
                <span class="text-sm">/ {{ strtolower($package->intervalLabel()) }}</span>
            </p>
        </div>

        <div class="mb-6">
            <div class="overflow-hidden h-2 rounded-full bg-slate-200">
                <div class="h-full bg-[#fa8900] transition-all duration-300" style="width: {{ ($step / 3) * 100 }}%"></div>
            </div>
            <div class="mt-2 flex justify-between text-xs font-medium text-slate-500">
                <span class="{{ $step >= 1 ? 'text-[#fa8900]' : '' }}">Vendor info</span>
                <span class="{{ $step >= 2 ? 'text-[#fa8900]' : '' }}">Personal info</span>
                <span class="{{ $step >= 3 ? 'text-[#fa8900]' : '' }}">Payment</span>
            </div>
        </div>

        <form wire:submit="{{ $step < 3 ? 'nextStep' : 'startPayment' }}">
        @if ($step === 1)
            <div class="space-y-4">
                <div>
                    <label for="vendor_name" class="block text-sm font-semibold text-[#232f3e] mb-1">Business / vendor name</label>
                    <input wire:model="vendor_name" id="vendor_name" type="text" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-[#fa8900] focus:ring-2 focus:ring-[#fa8900]/30" placeholder="e.g. ABC Mobile Shop">
                    @error('vendor_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="brand_name" class="block text-sm font-semibold text-[#232f3e] mb-1">Brand name (optional)</label>
                    <input wire:model="brand_name" id="brand_name" type="text" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-[#fa8900] focus:ring-2 focus:ring-[#fa8900]/30" placeholder="Shown to customers">
                    @error('brand_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        @elseif ($step === 2)
            <div class="space-y-4">
                <p class="text-sm text-slate-600">This person becomes the tenant <strong>admin</strong> after payment.</p>
                <div>
                    <label for="admin_name" class="block text-sm font-semibold text-[#232f3e] mb-1">Full name</label>
                    <input wire:model="admin_name" id="admin_name" type="text" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-[#fa8900] focus:ring-2 focus:ring-[#fa8900]/30">
                    @error('admin_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="email" class="block text-sm font-semibold text-[#232f3e] mb-1">Email (login)</label>
                    <input wire:model="email" id="email" type="email" autocomplete="email" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-[#fa8900] focus:ring-2 focus:ring-[#fa8900]/30">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="phone" class="block text-sm font-semibold text-[#232f3e] mb-1">Phone</label>
                    <input wire:model="phone" id="phone" type="tel" autocomplete="tel" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-[#fa8900] focus:ring-2 focus:ring-[#fa8900]/30" placeholder="07XXXXXXXX">
                    @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password" class="block text-sm font-semibold text-[#232f3e] mb-1">Password</label>
                    <input wire:model="password" id="password" type="password" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-[#fa8900] focus:ring-2 focus:ring-[#fa8900]/30">
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-[#232f3e] mb-1">Confirm password</label>
                    <input wire:model="password_confirmation" id="password_confirmation" type="password" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-[#fa8900] focus:ring-2 focus:ring-[#fa8900]/30">
                </div>
            </div>
        @else
            <div class="space-y-4">
                <div class="rounded-xl border border-slate-200/80 bg-white/60 p-4 text-sm text-slate-600 space-y-1">
                    <p><span class="font-semibold text-[#232f3e]">Vendor:</span> {{ $vendor_name }}</p>
                    <p><span class="font-semibold text-[#232f3e]">Admin:</span> {{ $admin_name }} ({{ $email }})</p>
                    <p><span class="font-semibold text-[#232f3e]">Amount:</span> {{ $package->formattedPrice() }}</p>
                </div>
                <div>
                    <label for="payment_phone" class="block text-sm font-semibold text-[#232f3e] mb-1">Mobile money number (Selcom)</label>
                    <input wire:model="payment_phone" id="payment_phone" type="tel" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-[#fa8900] focus:ring-2 focus:ring-[#fa8900]/30" placeholder="07XXXXXXXX">
                    <p class="text-xs text-slate-500 mt-2">You will receive a USSD prompt on this number to approve payment.</p>
                    @error('payment_phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        @endif

        <div class="mt-8 flex justify-between gap-3">
            @if ($step > 1)
                <button type="button" wire:click="prevStep"
                    class="cursor-pointer px-5 py-2.5 rounded-xl admin-clay-inset text-sm font-semibold text-[#232f3e] transition-colors duration-200">
                    Back
                </button>
            @else
                <a href="{{ route('welcome') }}#packages"
                    class="cursor-pointer px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 hover:text-[#232f3e]">
                    Cancel
                </a>
            @endif

            @if ($step < 3)
                <button type="submit"
                    class="cursor-pointer ml-auto px-6 py-2.5 rounded-xl bg-[#fa8900] hover:bg-[#e07800] text-white text-sm font-bold transition-colors duration-200">
                    Continue
                </button>
            @else
                <button type="submit" wire:loading.attr="disabled"
                    class="cursor-pointer ml-auto px-6 py-2.5 rounded-xl bg-[#232f3e] hover:bg-[#1a2430] text-white text-sm font-bold transition-colors duration-200 disabled:opacity-60">
                    <span wire:loading.remove wire:target="startPayment">Pay with Selcom</span>
                    <span wire:loading wire:target="startPayment">Starting payment…</span>
                </button>
            @endif
        </div>
        </form>
    </div>
</div>
