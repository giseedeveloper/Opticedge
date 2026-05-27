<x-superadmin-layout>
    @include('admin.partials.catalog-styles')
    <div class="admin-prod-page admin-prod-page--narrow">
        <h1 class="admin-prod-title mb-6">Edit package</h1>
        <form action="{{ route('superadmin.packages.update', $package) }}" method="POST" class="admin-clay-panel p-6 space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="admin-prod-label" for="name">Name</label>
                <input id="name" name="name" required class="admin-prod-input w-full" value="{{ old('name', $package->name) }}">
            </div>
            <div>
                <label class="admin-prod-label" for="slug">Slug</label>
                <input id="slug" name="slug" class="admin-prod-input w-full" value="{{ old('slug', $package->slug) }}">
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="admin-prod-label" for="price">Price (TZS)</label>
                    <input id="price" type="number" name="price" min="0" step="0.01" class="admin-prod-input w-full"
                        value="{{ old('price', $package->price) }}">
                </div>
                <div>
                    <label class="admin-prod-label" for="interval">Billing interval</label>
                    <select id="interval" name="interval" class="admin-prod-select w-full" required>
                        @include('superadmin.packages.partials.interval-options', ['selected' => old('interval', $package->interval)])
                    </select>
                </div>
            </div>
            <div>
                <label class="admin-prod-label" for="description">Description</label>
                <textarea id="description" name="description" class="admin-prod-input w-full">{{ old('description', $package->description) }}</textarea>
            </div>
            <div>
                <label class="admin-prod-label" for="features_json">Features JSON</label>
                <textarea id="features_json" name="features_json" class="admin-prod-input w-full font-mono text-xs">{{ old('features_json', $package->features_json ? json_encode($package->features_json) : '') }}</textarea>
            </div>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $package->is_active))> Active
            </label>
            <button type="submit" class="admin-prod-btn-primary">Update</button>
        </form>
    </div>
</x-superadmin-layout>
