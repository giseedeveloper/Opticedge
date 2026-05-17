<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page admin-prod-page--narrow">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-8">
            <div>
                <p class="admin-prod-eyebrow">Users & Dealers</p>
                <h1 class="admin-prod-title">Add regional manager</h1>
                <p class="admin-prod-subtitle">Create a regional manager account. They are active immediately and can sign in to the regional manager area.</p>
            </div>
            <a href="{{ route('admin.customers.regional-managers.index') }}" class="admin-prod-back shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to regional managers
            </a>
        </div>

        @if($errors->any())
            <div class="admin-prod-alert admin-prod-alert--error mb-6" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="admin-clay-panel admin-prod-form-shell overflow-hidden">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Account</h2>
                <p class="admin-prod-form-hint">Name, contact, region, password, and optional notes.</p>
            </div>
            @if($regions->isEmpty())
                <div class="admin-prod-form-body">
                    <p class="text-sm text-slate-600">No regions found. Run migrations and seed Tanzanian regions first.</p>
                </div>
            @else
                <form method="POST" action="{{ route('admin.customers.regional-managers.store') }}" class="admin-prod-form-body space-y-6">
                    @csrf
                    <div>
                        <label for="name" class="admin-prod-label">Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                            class="admin-prod-input" autocomplete="name">
                        @error('name')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="email" class="admin-prod-label">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required
                            class="admin-prod-input" autocomplete="email">
                        @error('email')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="phone" class="admin-prod-label">Phone</label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
                            class="admin-prod-input" autocomplete="tel" placeholder="e.g. +255 …">
                        @error('phone')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="region_id" class="admin-prod-label">Region</label>
                        <select id="region_id" name="region_id" required class="admin-prod-input">
                            <option value="">Select region</option>
                            @foreach ($regions as $region)
                                <option value="{{ $region->id }}" @selected(old('region_id') == $region->id)>{{ $region->name }}</option>
                            @endforeach
                        </select>
                        @error('region_id')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password" class="admin-prod-label">Password</label>
                        <input type="password" id="password" name="password" required class="admin-prod-input"
                            autocomplete="new-password">
                        @error('password')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="admin-prod-label">Confirm password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                            class="admin-prod-input" autocomplete="new-password">
                    </div>
                    <div>
                        <label for="business_name" class="admin-prod-label">Organization / title (optional)</label>
                        <input type="text" id="business_name" name="business_name" value="{{ old('business_name') }}"
                            class="admin-prod-input" autocomplete="organization">
                        @error('business_name')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="notes" class="admin-prod-label">Other notes (optional)</label>
                        <textarea id="notes" name="notes" rows="3" class="admin-prod-input" placeholder="Internal notes">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="admin-prod-form-footer">
                        <a href="{{ route('admin.customers.regional-managers.index') }}" class="admin-prod-btn-ghost">Cancel</a>
                        <button type="submit" class="admin-prod-btn-primary px-8">Create regional manager</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-admin-layout>
