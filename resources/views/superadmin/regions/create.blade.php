<x-superadmin-layout>
    @include('admin.partials.catalog-styles')
    <div class="admin-prod-page admin-prod-page--narrow">
        <form action="{{ route('superadmin.regions.store') }}" method="POST" class="admin-clay-panel p-6 space-y-4">
            @csrf
            <h1 class="admin-prod-title">Add region</h1>
            <input name="name" required class="admin-prod-input w-full" placeholder="Region name" value="{{ old('name') }}">
            <button type="submit" class="admin-prod-btn-primary">Save</button>
        </form>
    </div>
</x-superadmin-layout>
