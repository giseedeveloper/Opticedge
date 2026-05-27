<x-superadmin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Master catalog</p>
                <h1 class="admin-prod-title">Brands</h1>
                <p class="admin-prod-subtitle">Platform-wide device brands shared with every vendor.</p>
            </div>
            <a href="{{ route('superadmin.brands.create') }}" class="admin-prod-btn-primary shrink-0">Add brand</a>
        </div>

        @include('superadmin.partials.flash')

        <div class="admin-clay-panel overflow-hidden">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table class="min-w-[640px]">
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th admin-prod-th--image">Logo</th>
                            <th scope="col" class="admin-prod-th">Brand</th>
                            <th scope="col" class="admin-prod-th">Scope</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($brands as $brand)
                            <tr>
                                <td>
                                    <div class="admin-prod-thumb admin-prod-thumb--tile">
                                        @if ($brand->image)
                                            <img src="{{ asset('storage/'.$brand->image) }}" alt="">
                                        @else
                                            <span class="text-xs font-bold text-slate-400">{{ strtoupper(substr($brand->name, 0, 1)) }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="font-semibold text-[#232f3e]">{{ $brand->name }}</td>
                                <td>
                                    @if ($brand->is_platform)
                                        <span class="admin-prod-count-pill admin-prod-count-pill--info">Platform</span>
                                    @else
                                        <span class="admin-prod-count-pill admin-prod-count-pill--neutral">Tenant</span>
                                    @endif
                                </td>
                                <td class="admin-prod-cell-actions">
                                    <div class="admin-prod-actions">
                                        <a href="{{ route('superadmin.brands.edit', $brand) }}" class="admin-prod-link">Edit</a>
                                        <form action="{{ route('superadmin.brands.destroy', $brand) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Delete this brand?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="admin-prod-link admin-prod-link--danger admin-prod-btn-inline">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="admin-prod-muted py-8 text-center">No brands yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($brands->hasPages())
                <div class="admin-prod-pagination">{{ $brands->links() }}</div>
            @endif
        </div>
    </div>
</x-superadmin-layout>
