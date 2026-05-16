<x-team-leader-layout title="Edit address">
    <div class="admin-prod-page !pt-4 sm:!pt-6">
        <div class="admin-prod-toolbar !mb-6">
            <div>
                <p class="admin-prod-eyebrow">Account</p>
                <h1 class="admin-prod-title">Edit address</h1>
                <p class="admin-prod-subtitle">Update this shipping address.</p>
            </div>
        </div>

        <div class="admin-clay-panel max-w-3xl overflow-hidden">
            <form action="{{ route('addresses.update', $address) }}" method="POST" class="admin-prod-form-body space-y-6">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-6">
                        <label for="country" class="admin-prod-label">Country / region</label>
                        <select id="country" name="country" required class="admin-prod-input">
                            <option value="Tanzania" @selected(old('country', $address->country) == 'Tanzania')>Tanzania</option>
                            <option value="United States" @selected(old('country', $address->country) == 'United States')>United States</option>
                            <option value="Kenya" @selected(old('country', $address->country) == 'Kenya')>Kenya</option>
                            <option value="Uganda" @selected(old('country', $address->country) == 'Uganda')>Uganda</option>
                        </select>
                    </div>
                    <div class="sm:col-span-6">
                        <label for="address" class="admin-prod-label">Street address</label>
                        <input type="text" name="address" id="address" required value="{{ old('address', $address->address) }}"
                            autocomplete="street-address" class="admin-prod-input">
                    </div>
                    <div class="sm:col-span-3">
                        <label for="city" class="admin-prod-label">City</label>
                        <input type="text" name="city" id="city" required value="{{ old('city', $address->city) }}"
                            autocomplete="address-level2" class="admin-prod-input">
                    </div>
                    <div class="sm:col-span-3">
                        <label for="state" class="admin-prod-label">State / province</label>
                        <input type="text" name="state" id="state" value="{{ old('state', $address->state) }}"
                            autocomplete="address-level1" class="admin-prod-input">
                    </div>
                    <div class="sm:col-span-3">
                        <label for="zip" class="admin-prod-label">ZIP / postal code</label>
                        <input type="text" name="zip" id="zip" value="{{ old('zip', $address->zip) }}"
                            autocomplete="postal-code" class="admin-prod-input">
                    </div>
                    <div class="sm:col-span-3">
                        <label for="type" class="admin-prod-label">Address type</label>
                        <select name="type" id="type" required class="admin-prod-input">
                            <option value="Home" @selected(old('type', $address->type) == 'Home')>Home</option>
                            <option value="Office" @selected(old('type', $address->type) == 'Office')>Office</option>
                            <option value="Other" @selected(old('type', $address->type) == 'Other')>Other</option>
                        </select>
                    </div>
                    <div class="sm:col-span-3">
                        <label for="latitude" class="admin-prod-label">Latitude (optional)</label>
                        <input type="text" name="latitude" id="latitude" value="{{ old('latitude', $address->latitude) }}"
                            class="admin-prod-input" inputmode="decimal">
                    </div>
                    <div class="sm:col-span-3">
                        <label for="longitude" class="admin-prod-label">Longitude (optional)</label>
                        <input type="text" name="longitude" id="longitude" value="{{ old('longitude', $address->longitude) }}"
                            class="admin-prod-input" inputmode="decimal">
                    </div>
                </div>

                <div class="admin-prod-form-footer !mt-0 flex flex-wrap gap-3">
                    <a href="{{ route('team-leader.addresses.index') }}" class="admin-prod-btn-ghost">Cancel</a>
                    <button type="submit" class="admin-prod-btn-primary">Update address</button>
                </div>
            </form>
        </div>
    </div>
</x-team-leader-layout>
