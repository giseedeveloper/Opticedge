<x-superadmin-layout>
    @include('admin.partials.catalog-styles')
    <div class="admin-prod-page admin-prod-page--narrow">
        <h1 class="admin-prod-title mb-6">Edit vendor</h1>
        <form action="{{ route('superadmin.tenants.update', $tenant) }}" method="POST" class="admin-clay-panel p-6 space-y-4">
            @csrf @method('PUT')
            <div><label class="admin-prod-label">Name</label><input name="name" value="{{ old('name', $tenant->name) }}" required class="admin-prod-input w-full"></div>
            <div><label class="admin-prod-label">Slug</label><input name="slug" value="{{ old('slug', $tenant->slug) }}" required class="admin-prod-input w-full"></div>
            <div><label class="admin-prod-label">Brand name</label><input name="brand_name" value="{{ old('brand_name', $tenant->brand_name) }}" class="admin-prod-input w-full"></div>
            <div><label class="admin-prod-label">Package</label>
                <select name="package_id" class="admin-prod-select w-full">
                    <option value="">—</option>
                    @foreach ($packages as $p)
                        <option value="{{ $p->id }}" @selected(old('package_id', $tenant->package_id) == $p->id)>
                            {{ $p->name }} — {{ $p->formattedPrice() }} / {{ $p->intervalLabel() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div><label class="admin-prod-label">Status</label>
                <select name="status" class="admin-prod-input w-full"><option value="active" @selected($tenant->status==='active')>active</option><option value="suspended" @selected($tenant->status==='suspended')>suspended</option></select>
            </div>
            <button type="submit" class="admin-prod-btn-primary">Update</button>
        </form>
    </div>
</x-superadmin-layout>
