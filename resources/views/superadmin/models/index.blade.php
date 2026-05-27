<x-superadmin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Master catalog</p>
                <h1 class="admin-prod-title">Models</h1>
                <p class="admin-prod-subtitle">Platform-wide device models linked to master brands.</p>
            </div>
            <a href="{{ route('superadmin.models.create') }}" class="admin-prod-btn-primary shrink-0">Add model</a>
        </div>

        @include('superadmin.partials.flash')

        <form method="GET" action="{{ route('superadmin.models.index') }}"
            class="admin-clay-panel mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
            <label for="model-search" class="sr-only">Search models</label>
            <input id="model-search" type="search" name="search" value="{{ $search }}"
                placeholder="Search by model or brand name…"
                class="admin-prod-input w-full flex-1" autocomplete="off">
            <div class="flex shrink-0 gap-2">
                <button type="submit" class="admin-prod-btn-primary">Search</button>
                @if ($search !== '')
                    <a href="{{ route('superadmin.models.index') }}" class="admin-prod-btn-ghost">Clear</a>
                @endif
            </div>
        </form>

        <div class="admin-clay-panel overflow-hidden">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table class="min-w-[720px]">
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Model</th>
                            <th scope="col" class="admin-prod-th">Brand</th>
                            <th scope="col" class="admin-prod-th">Scope</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($models as $model)
                            <tr>
                                <td class="font-semibold text-[#232f3e]">{{ $model->name }}</td>
                                <td class="text-slate-600">{{ $model->category?->name ?? '—' }}</td>
                                <td>
                                    @if ($model->is_platform)
                                        <span class="admin-prod-count-pill admin-prod-count-pill--info">Platform</span>
                                    @else
                                        <span class="admin-prod-count-pill admin-prod-count-pill--neutral">Tenant</span>
                                    @endif
                                </td>
                                <td class="admin-prod-cell-actions">
                                    <div class="admin-prod-actions">
                                        <a href="{{ route('superadmin.models.edit', $model) }}" class="admin-prod-link">Edit</a>
                                        <form action="{{ route('superadmin.models.destroy', $model) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Delete this model?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="admin-prod-link admin-prod-link--danger admin-prod-btn-inline">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="admin-prod-muted py-8 text-center">
                                    @if ($search !== '')
                                        No models match “{{ $search }}”.
                                        <a href="{{ route('superadmin.models.index') }}" class="admin-prod-link ml-1">Clear search</a>
                                    @else
                                        No models yet.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($models->hasPages())
                <div class="admin-prod-pagination">{{ $models->links() }}</div>
            @endif
        </div>
    </div>
</x-superadmin-layout>
