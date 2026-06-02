<x-superadmin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Platform</p>
                <h1 class="admin-prod-title">Subscription</h1>
                <p class="admin-prod-subtitle">View platform revenue and profit from active vendor subscriptions. To change profit amounts, edit the package under Packages.</p>
            </div>
            <a href="{{ route('superadmin.packages.index') }}" class="admin-prod-btn-primary shrink-0">Manage packages</a>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Active subscriptions</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#232f3e]">{{ $activeSubscriptions->count() }}</p>
            </div>
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Est. monthly revenue</p>
                <p class="mt-2 text-2xl font-extrabold tracking-tight text-[#232f3e]">{{ number_format($monthlyRevenue, 0) }} TZS</p>
            </div>
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Est. monthly profit</p>
                <p class="mt-2 text-2xl font-extrabold tracking-tight text-emerald-700">{{ number_format($monthlyProfit, 0) }} TZS</p>
            </div>
        </div>

        <div class="admin-clay-panel overflow-hidden mb-6">
            <div class="admin-prod-form-head border-b border-white/60">
                <h2 class="admin-prod-form-title">Profit by package</h2>
                <p class="admin-prod-form-hint">Read-only overview. Monthly figures normalize yearly and quarterly billing to a per-month estimate.</p>
            </div>

            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table class="min-w-[1000px]">
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Package</th>
                            <th scope="col" class="admin-prod-th">Price</th>
                            <th scope="col" class="admin-prod-th">Interval</th>
                            <th scope="col" class="admin-prod-th">Vendors</th>
                            <th scope="col" class="admin-prod-th">Profit / period</th>
                            <th scope="col" class="admin-prod-th">Margin</th>
                            <th scope="col" class="admin-prod-th">Est. monthly revenue</th>
                            <th scope="col" class="admin-prod-th">Est. monthly profit</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Package</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($packages as $package)
                            <tr>
                                <td class="font-semibold text-[#232f3e]">{{ $package->name }}</td>
                                <td class="text-slate-600 whitespace-nowrap">{{ $package->formattedPrice() }}</td>
                                <td>
                                    <span class="admin-prod-count-pill admin-prod-count-pill--info">{{ $package->intervalLabel() }}</span>
                                </td>
                                <td>
                                    <span class="admin-prod-count-pill admin-prod-count-pill--neutral">{{ $package->tenants_count }}</span>
                                </td>
                                <td class="font-semibold text-emerald-700 whitespace-nowrap">{{ $package->formattedProfit() }}</td>
                                <td class="text-slate-600">
                                    @if ($margin = $package->profitMarginPercent())
                                        {{ $margin }}%
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-slate-600 whitespace-nowrap">{{ number_format($package->estimatedMonthlyRevenue(), 0) }} TZS</td>
                                <td class="font-semibold text-emerald-700 whitespace-nowrap">{{ number_format($package->estimatedMonthlyProfit(), 0) }} TZS</td>
                                <td class="admin-prod-cell-actions">
                                    <a href="{{ route('superadmin.packages.edit', $package) }}" class="admin-prod-link">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="admin-prod-muted py-8 text-center">
                                    No packages yet.
                                    <a href="{{ route('superadmin.packages.create') }}" class="admin-prod-link ml-1">Create a package</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-clay-panel overflow-hidden">
            <div class="admin-prod-form-head border-b border-white/60">
                <h2 class="admin-prod-form-title">Active subscriptions</h2>
                <p class="admin-prod-form-hint">Vendors currently on a paid package.</p>
            </div>

            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table class="min-w-[800px]">
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Vendor</th>
                            <th scope="col" class="admin-prod-th">Package</th>
                            <th scope="col" class="admin-prod-th">Price</th>
                            <th scope="col" class="admin-prod-th">Profit / period</th>
                            <th scope="col" class="admin-prod-th">Est. monthly profit</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Vendor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activeSubscriptions as $tenant)
                            @php
                                $pkg = $tenant->package;
                                $tenantMonthlyProfit = $pkg ? (float) $pkg->profit * $pkg->monthlyMultiplier() : 0;
                            @endphp
                            <tr>
                                <td class="font-semibold text-[#232f3e]">{{ $tenant->name }}</td>
                                <td class="text-slate-600">{{ $pkg?->name ?? '—' }}</td>
                                <td class="text-slate-600 whitespace-nowrap">{{ $pkg ? $pkg->formattedPrice() : '—' }}</td>
                                <td class="text-emerald-700 whitespace-nowrap">{{ $pkg ? $pkg->formattedProfit() : '—' }}</td>
                                <td class="font-semibold text-emerald-700 whitespace-nowrap">{{ number_format($tenantMonthlyProfit, 0) }} TZS</td>
                                <td class="admin-prod-cell-actions">
                                    <a href="{{ route('superadmin.tenants.edit', $tenant) }}" class="admin-prod-link">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="admin-prod-muted py-8 text-center">No active subscriptions.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-superadmin-layout>
