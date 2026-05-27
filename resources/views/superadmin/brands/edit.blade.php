<x-superadmin-layout>
    @include('admin.partials.catalog-styles')
    <div class="admin-prod-page admin-prod-page--narrow">
        <form action="{{ route('superadmin.brands.update', $brand) }}" method="POST" enctype="multipart/form-data" class="admin-clay-panel p-6 space-y-4">
            @csrf @method('PUT')
            <h1 class="admin-prod-title">Edit brand</h1>
            <input name="name" required class="admin-prod-input w-full" value="{{ old('name', $brand->name) }}">
            <input type="file" name="image" accept="image/*" class="admin-prod-input w-full">
            <button type="submit" class="admin-prod-btn-primary">Update</button>
        </form>
    </div>
</x-superadmin-layout>
