<x-superadmin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Platform</p>
                <h1 class="admin-prod-title">Packages</h1>
                <p class="admin-prod-subtitle">Subscription tiers assigned to vendors.</p>
            </div>
            <a href="{{ route('superadmin.packages.create') }}" class="admin-prod-btn-primary shrink-0">Add package</a>
        </div>

        @include('superadmin.partials.flash')

        <div class="admin-clay-panel overflow-hidden">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table class="min-w-[900px]">
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Name</th>
                            <th scope="col" class="admin-prod-th">Slug</th>
                            <th scope="col" class="admin-prod-th">Price</th>
                            <th scope="col" class="admin-prod-th">Profit</th>
                            <th scope="col" class="admin-prod-th">Interval</th>
                            <th scope="col" class="admin-prod-th">Agents</th>
                            <th scope="col" class="admin-prod-th">Admins</th>
                            <th scope="col" class="admin-prod-th">Trial</th>
                            <th scope="col" class="admin-prod-th">Vendors</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($packages as $package)
                            <tr>
                                <td class="font-semibold text-[#232f3e]">{{ $package->name }}</td>
                                <td class="text-slate-600">{{ $package->slug }}</td>
                                <td class="text-slate-600 whitespace-nowrap">{{ $package->formattedPrice() }}</td>
                                <td class="text-emerald-700 whitespace-nowrap">{{ $package->formattedProfit() }}</td>
                                <td>
                                    <span class="admin-prod-count-pill admin-prod-count-pill--info">{{ $package->intervalLabel() }}</span>
                                </td>
                                <td class="text-slate-600 whitespace-nowrap">{{ $package->limitLabel($package->max_agents) }}</td>
                                <td class="text-slate-600 whitespace-nowrap">{{ $package->limitLabel($package->max_admins) }}</td>
                                <td class="text-slate-600 whitespace-nowrap">{{ $package->trialLabel() }}</td>
                                <td>
                                    <span class="admin-prod-count-pill admin-prod-count-pill--neutral">{{ $package->tenants_count }}</span>
                                </td>
                                <td class="admin-prod-cell-actions">
                                    <div class="admin-prod-actions">
                                        <a href="{{ route('superadmin.packages.edit', $package) }}" class="admin-prod-link">Edit</a>
                                        <form action="{{ route('superadmin.packages.destroy', $package) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Delete this package?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="admin-prod-link admin-prod-link--danger admin-prod-btn-inline">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="admin-prod-muted py-8 text-center">No packages yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-superadmin-layout>
