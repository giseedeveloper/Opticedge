<x-superadmin-layout>
    @include('admin.partials.catalog-styles')
    <div class="admin-prod-page admin-prod-page--narrow">
        <form action="{{ route('superadmin.models.update', $model) }}" method="POST" class="admin-clay-panel p-6 space-y-4">
            @csrf @method('PUT')
            <h1 class="admin-prod-title">Edit model</h1>
            <select name="category_id" required class="admin-prod-input w-full">@foreach($brands as $b)<option value="{{ $b->id }}" @selected($model->category_id==$b->id)>{{ $b->name }}</option>@endforeach</select>
            <input name="name" required class="admin-prod-input w-full" value="{{ old('name', $model->name) }}">
            <textarea name="description" class="admin-prod-input w-full">{{ old('description', $model->description) }}</textarea>
            <button type="submit" class="admin-prod-btn-primary">Update</button>
        </form>
    </div>
</x-superadmin-layout>
