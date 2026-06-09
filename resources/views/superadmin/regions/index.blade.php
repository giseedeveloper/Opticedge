<x-superadmin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Master catalog</p>
                <h1 class="admin-prod-title">Regions</h1>
                <p class="admin-prod-subtitle">Shared regions available to all vendors on the platform.</p>
            </div>
            <a href="{{ route('superadmin.regions.create') }}" class="admin-prod-btn-primary shrink-0">Add region</a>
        </div>

        @include('superadmin.partials.flash')

        <div class="admin-clay-panel overflow-hidden">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table class="min-w-[640px]">
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Region</th>
                            <th scope="col" class="admin-prod-th">Scope</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($regions as $region)
                            <tr>
                                <td class="font-semibold text-[#232f3e]">{{ $region->name }}</td>
                                <td>
                                    @if ($region->is_platform)
                                        <span class="admin-prod-count-pill admin-prod-count-pill--info">Platform</span>
                                    @else
                                        <span class="admin-prod-count-pill admin-prod-count-pill--neutral">Tenant</span>
                                    @endif
                                </td>
                                <td class="admin-prod-cell-actions">
                                    <div class="admin-prod-actions">
                                        <a href="{{ route('superadmin.regions.edit', $region) }}" class="admin-prod-link">Edit</a>
                                        <form action="{{ route('superadmin.regions.destroy', $region) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Delete this region?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="admin-prod-link admin-prod-link--danger admin-prod-btn-inline">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="admin-prod-muted py-8 text-center">No regions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-superadmin-layout>
