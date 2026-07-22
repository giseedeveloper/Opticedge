<x-superadmin-layout>
    @include('admin.partials.catalog-styles')
    <div class="admin-prod-page admin-prod-page--narrow">
        <h1 class="admin-prod-title mb-6">Add package</h1>
        <form action="{{ route('superadmin.packages.store') }}" method="POST" class="admin-clay-panel p-6 space-y-4">
            @csrf
            <div>
                <label class="admin-prod-label" for="name">Name</label>
                <input id="name" name="name" required class="admin-prod-input w-full" value="{{ old('name') }}">
            </div>
            <div>
                <label class="admin-prod-label" for="slug">Slug (optional)</label>
                <input id="slug" name="slug" class="admin-prod-input w-full" value="{{ old('slug') }}">
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="admin-prod-label" for="price">Price (TZS)</label>
                    <input id="price" type="number" name="price" min="0" step="0.01" class="admin-prod-input w-full"
                        value="{{ old('price', 0) }}">
                </div>
                <div>
                    <label class="admin-prod-label" for="interval">Billing interval</label>
                    <select id="interval" name="interval" class="admin-prod-select w-full" required>
                        @include('superadmin.packages.partials.interval-options', ['selected' => old('interval', 'monthly')])
                    </select>
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="admin-prod-label" for="profit">Profit (TZS)</label>
                    <input id="profit" type="number" name="profit" min="0" step="0.01" class="admin-prod-input w-full"
                        value="{{ old('profit', 0) }}">
                </div>
                <div>
                    <label class="admin-prod-label" for="trial_days">Trial (days)</label>
                    <input id="trial_days" type="number" name="trial_days" min="0" step="1" class="admin-prod-input w-full"
                        placeholder="0 = no trial" value="{{ old('trial_days') }}">
                </div>
            </div>

            <fieldset class="rounded-2xl border border-white/80 bg-white/40 p-4">
                <legend class="admin-prod-label px-1">Limits <span class="text-slate-400 font-normal">(leave blank = unlimited)</span></legend>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="admin-prod-label" for="max_agents">Field agents</label>
                        <input id="max_agents" type="number" name="max_agents" min="0" step="1" class="admin-prod-input w-full"
                            placeholder="Unlimited" value="{{ old('max_agents') }}">
                    </div>
                    <div>
                        <label class="admin-prod-label" for="max_admins">Admins</label>
                        <input id="max_admins" type="number" name="max_admins" min="0" step="1" class="admin-prod-input w-full"
                            placeholder="Unlimited" value="{{ old('max_admins') }}">
                    </div>
                    <div>
                        <label class="admin-prod-label" for="max_users">Total users</label>
                        <input id="max_users" type="number" name="max_users" min="0" step="1" class="admin-prod-input w-full"
                            placeholder="Unlimited" value="{{ old('max_users') }}">
                    </div>
                </div>
            </fieldset>

            <div>
                <label class="admin-prod-label" for="description">Best for / Description</label>
                <textarea id="description" name="description" class="admin-prod-input w-full">{{ old('description') }}</textarea>
            </div>

            @php $selectedFeatures = old('features', []); @endphp
            <fieldset class="rounded-2xl border border-white/80 bg-white/40 p-4">
                <legend class="admin-prod-label px-1">Key features</legend>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach (\App\Models\Package::FEATURES as $key => $label)
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="features[{{ $key }}]" value="1" @checked(! empty($selectedFeatures[$key]))>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> Active
            </label>
            <button type="submit" class="admin-prod-btn-primary">Save</button>
        </form>
    </div>
</x-superadmin-layout>
