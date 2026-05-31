<?php

use App\Models\User;
use App\Models\Address;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public int $step = 1;

    // Step 1: Account
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    // Step 2: Business
    public string $business_name = '';
    public string $phone = '';

    // Step 3: Location
    public string $address = '';
    public string $city = '';
    public string $state = '';
    public string $zip = '';
    public $latitude;
    public $longitude;

    public function nextStep()
    {
        if ($this->step == 1) {
            $this->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);
        } elseif ($this->step == 2) {
            $this->validate([
                'business_name' => ['required', 'string', 'max:255'],
                'phone' => ['required', 'string', 'max:20'],
            ]);
        }

        $this->step++;

        if ($this->step == 3) {
            $this->dispatch('stepChanged');
        }
    }

    public function prevStep()
    {
        $this->step--;

        if ($this->step == 3) {
            $this->dispatch('stepChanged');
        }
    }

    public function register()
    {
        $this->validate([
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'business_name' => $this->business_name,
            'phone' => $this->phone,
            'role' => 'dealer',
            'status' => 'pending',
        ]);

        $user->addresses()->create([
            'type' => 'business',
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
            'country' => 'Tanzania', // Defaulting to Tanzania as per context if needed, or make it dynamic
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_default' => true,
        ]);

        event(new Registered($user));

        $this->redirect(route('dealer.pending'), navigate: true);
    }
}; ?>

<div class="max-w-xl mx-auto">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900">Dealer Registration</h2>
        <p class="text-sm text-slate-600">Join our network of trusted dealers.</p>

        <!-- Progress Bar -->
        <div class="mt-6 relative">
            <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-slate-200">
                <div style="width:{{ ($step / 3) * 100 }}%"
                    class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-[#fa8900] transition-all duration-500">
                </div>
            </div>
            <div class="flex justify-between text-xs font-medium text-slate-600">
                <span class="{{ $step >= 1 ? 'text-[#fa8900]' : '' }}">Account</span>
                <span class="{{ $step >= 2 ? 'text-[#fa8900]' : '' }}">Business</span>
                <span class="{{ $step >= 3 ? 'text-[#fa8900]' : '' }}">Location</span>
            </div>
        </div>
    </div>

    <form wire:submit="register">
        @if ($step == 1)
            <!-- Step 1: Account & Security -->
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-slate-900 mb-1">Contact Person Name</label>
                    <input wire:model="name" type="text"
                        class="w-full rounded-md border-slate-300 shadow-sm focus:border-[#fa8900] focus:ring focus:ring-[#fa8900] focus:ring-opacity-50 text-sm py-2"
                        placeholder="Full Name">
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-900 mb-1">Business Email</label>
                    <input wire:model="email" type="email" id="email" name="email"
                        class="w-full rounded-md border-slate-300 shadow-sm focus:border-[#fa8900] focus:ring focus:ring-[#fa8900] focus:ring-opacity-50 text-sm py-2"
                        placeholder="email@business.com" autocomplete="email">
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-900 mb-1">Password</label>
                    <input wire:model="password" type="password"
                        class="w-full rounded-md border-slate-300 shadow-sm focus:border-[#fa8900] focus:ring focus:ring-[#fa8900] focus:ring-opacity-50 text-sm py-2">
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-900 mb-1">Confirm Password</label>
                    <input wire:model="password_confirmation" type="password"
                        class="w-full rounded-md border-slate-300 shadow-sm focus:border-[#fa8900] focus:ring focus:ring-[#fa8900] focus:ring-opacity-50 text-sm py-2">
                </div>
            </div>
        @elseif ($step == 2)
            <!-- Step 2: Business Profile -->
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-slate-900 mb-1">Business Name</label>
                    <input wire:model="business_name" type="text"
                        class="w-full rounded-md border-slate-300 shadow-sm focus:border-[#fa8900] focus:ring focus:ring-[#fa8900] focus:ring-opacity-50 text-sm py-2"
                        placeholder="Legal Business Name">
                    <x-input-error :messages="$errors->get('business_name')" class="mt-1" />
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-900 mb-1">Phone Number</label>
                    <input wire:model="phone" type="text" id="phone" name="phone"
                        class="w-full rounded-md border-slate-300 shadow-sm focus:border-[#fa8900] focus:ring focus:ring-[#fa8900] focus:ring-opacity-50 text-sm py-2"
                        placeholder="+255 ..." autocomplete="tel">
                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                </div>
            </div>
        @elseif ($step == 3)
            <!-- Step 3: Location -->
            <div class="space-y-4" x-data="{
                            map: null,
                            marker: null,
                            lat: @entangle('latitude'),
                            lng: @entangle('longitude'),
                            initMap() {
                                if (this.map) return;

                                // Default default
                                let center = [this.lat || -6.7924, this.lng || 39.2083];

                                this.map = L.map('map').setView(center, 13);

                                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                    attribution: '&copy; OpenStreetMap contributors'
                                }).addTo(this.map);

                                // Fix grey tiles issue
                                setTimeout(() => { this.map.invalidateSize(); }, 200);

                                if (this.lat && this.lng) {
                                    this.marker = L.marker([this.lat, this.lng]).addTo(this.map);
                                }

                                this.map.on('click', (e) => {
                                    this.lat = e.latlng.lat;
                                    this.lng = e.latlng.lng;

                                    if (this.marker) {
                                        this.marker.setLatLng(e.latlng);
                                    } else {
                                        this.marker = L.marker(e.latlng).addTo(this.map);
                                    }
                                });
                            },
                            getLocation() {
                                if (navigator.geolocation) {
                                    navigator.geolocation.getCurrentPosition(
                                        (position) => {
                                            this.lat = position.coords.latitude;
                                            this.lng = position.coords.longitude;

                                            if (this.map) {
                                                this.map.setView([this.lat, this.lng], 15);
                                                if (this.marker) {
                                                    this.marker.setLatLng([this.lat, this.lng]);
                                                } else {
                                                    this.marker = L.marker([this.lat, this.lng]).addTo(this.map);
                                                }
                                            }
                                            alert('Location captured successfully!');
                                        },
                                        (error) => {
                                            alert('Error getting location: ' + error.message);
                                        }
                                    );
                                } else {
                                    alert('Geolocation is not supported by this browser.');
                                }
                            }
                        }" x-init="initMap()">

                <div class="flex space-x-2">
                    <button type="button" @click="getLocation()"
                        class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 py-2 px-4 rounded-md border border-slate-300 text-sm font-medium transition-colors flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Get My Current Location
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-bold text-slate-900 mb-1">Pick Location on Map</label>
                        <div id="map" style="height: 300px; width: 100%;" class="rounded-md border border-slate-300 z-0"
                            wire:ignore></div>
                        <p class="text-xs text-slate-500 mt-1 italic">Click on the map to set your business location point.
                        </p>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-bold text-slate-900 mb-1">Physical Address</label>
                        <input wire:model="address" type="text"
                            class="w-full rounded-md border-slate-300 shadow-sm focus:border-[#fa8900] focus:ring focus:ring-[#fa8900] focus:ring-opacity-50 text-sm py-2"
                            placeholder="Street, Building...">
                        <x-input-error :messages="$errors->get('address')" class="mt-1" />
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-900 mb-1">City</label>
                        <input wire:model="city" type="text"
                            class="w-full rounded-md border-slate-300 shadow-sm focus:border-[#fa8900] focus:ring focus:ring-[#fa8900] focus:ring-opacity-50 text-sm py-2"
                            placeholder="e.g. Dar es Salaam">
                        <x-input-error :messages="$errors->get('city')" class="mt-1" />
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-900 mb-1">Region / State</label>
                        <input wire:model="state" type="text"
                            class="w-full rounded-md border-slate-300 shadow-sm focus:border-[#fa8900] focus:ring focus:ring-[#fa8900] focus:ring-opacity-50 text-sm py-2"
                            placeholder="e.g. Kinondoni">
                        <x-input-error :messages="$errors->get('state')" class="mt-1" />
                    </div>
                </div>

                <div class="hidden">
                    <input type="hidden" wire:model="latitude">
                    <input type="hidden" wire:model="longitude">
                </div>
            </div>
        @endif

        <div class="mt-8 flex justify-between">
            @if ($step > 1)
                <button type="button" wire:click="prevStep"
                    class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-medium py-2 px-6 rounded-md transition-colors text-sm">
                    Back
                </button>
            @else
                <div></div>
            @endif

            @if ($step < 3)
                <button type="button" wire:click="nextStep"
                    class="bg-[#fa8900] hover:bg-[#e87b00] text-white font-medium py-2 px-6 rounded-md shadow-sm transition-colors text-sm">
                    Next Step
                </button>
            @else
                <button type="submit"
                    class="bg-[#fa8900] hover:bg-[#e87b00] text-white font-medium py-2 px-6 rounded-md shadow-sm transition-colors text-sm">
                    Register as Dealer
                </button>
            @endif
        </div>
    </form>


</div>