<x-superadmin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Platform</p>
                <h1 class="admin-prod-title">Vendors</h1>
                <p class="admin-prod-subtitle">Tenant stores subscribed to platform packages.</p>
            </div>
            <a href="{{ route('superadmin.tenants.create') }}" class="admin-prod-btn-primary shrink-0">Add vendor</a>
        </div>

        @include('superadmin.partials.flash')

        <div class="admin-clay-panel overflow-hidden">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table class="min-w-[900px]">
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Vendor</th>
                            <th scope="col" class="admin-prod-th">Slug</th>
                            <th scope="col" class="admin-prod-th">Package</th>
                            <th scope="col" class="admin-prod-th">Status</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tenants as $tenant)
                            <tr>
                                <td class="font-semibold text-[#232f3e]">{{ $tenant->name }}</td>
                                <td class="text-slate-600">{{ $tenant->slug }}</td>
                                <td class="text-slate-600">{{ $tenant->package?->name ?? '—' }}</td>
                                <td>
                                    @if ($tenant->status === 'active')
                                        <span class="admin-prod-user-status admin-prod-user-status--active">Active</span>
                                    @elseif ($tenant->status === 'suspended')
                                        <span class="admin-prod-dealer-status admin-prod-dealer-status--suspended">Suspended</span>
                                    @else
                                        <span class="admin-prod-user-status admin-prod-user-status--inactive">{{ ucfirst($tenant->status) }}</span>
                                    @endif
                                </td>
                                <td class="admin-prod-cell-actions">
                                    <div class="admin-prod-actions">
                                        <a href="{{ route('superadmin.tenants.edit', $tenant) }}" class="admin-prod-link">Edit</a>
                                        @if ($tenant->status === 'active')
                                            <form action="{{ route('superadmin.tenants.suspend', $tenant) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="admin-prod-link admin-prod-btn-inline">Suspend</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="admin-prod-muted py-8 text-center">No vendors yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($tenants->hasPages())
                <div class="admin-prod-pagination">{{ $tenants->links() }}</div>
            @endif
        </div>
    </div>
</x-superadmin-layout>
