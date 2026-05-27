<x-superadmin-layout>
    @include('admin.partials.catalog-styles')
    <div class="admin-prod-page admin-prod-page--narrow">
        <form action="{{ route('superadmin.models.store') }}" method="POST" class="admin-clay-panel p-6 space-y-4">
            @csrf
            <h1 class="admin-prod-title">Add model</h1>
            <select name="category_id" required class="admin-prod-input w-full">@foreach($brands as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select>
            <input name="name" required class="admin-prod-input w-full" placeholder="Model name">
            <textarea name="description" class="admin-prod-input w-full" placeholder="Description"></textarea>
            <button type="submit" class="admin-prod-btn-primary">Save</button>
        </form>
    </div>
</x-superadmin-layout>
