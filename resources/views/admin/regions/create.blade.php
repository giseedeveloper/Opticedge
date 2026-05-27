<x-admin-layout>
    @include('admin.partials.catalog-styles')
    <div class="admin-prod-page admin-prod-page--narrow">
        <form action="{{ route('admin.regions.store') }}" method="POST" class="admin-clay-panel p-6 space-y-4">
            @csrf
            <h1 class="admin-prod-title">Add region</h1>
            <p class="text-sm text-slate-500">Tenant-specific regions cannot be removed by tenant admins once saved.</p>
            <input name="name" required class="admin-prod-input w-full" value="{{ old('name') }}">
            <button type="submit" class="admin-prod-btn-primary">Save</button>
        </form>
    </div>
</x-admin-layout>
