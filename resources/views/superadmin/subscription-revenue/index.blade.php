<x-superadmin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Platform</p>
                <h1 class="admin-prod-title">Subscription revenue</h1>
                <p class="admin-prod-subtitle">Actual money received from vendor subscription payments, reconciled from completed Selcom transactions. Demo payments are excluded.</p>
            </div>
            <a href="{{ route('superadmin.subscription-profits.index') }}" class="admin-prod-btn-ghost shrink-0">View estimates</a>
        </div>

        <form method="GET" action="{{ route('superadmin.subscription-revenue.index') }}"
            class="admin-clay-panel p-4 mb-6 flex flex-wrap items-end gap-3">
            <div>
                <label for="from" class="admin-prod-label">From</label>
                <input type="date" name="from" id="from" value="{{ $from }}" class="admin-prod-input">
            </div>
            <div>
                <label for="to" class="admin-prod-label">To</label>
                <input type="date" name="to" id="to" value="{{ $to }}" class="admin-prod-input">
            </div>
            <button type="submit" class="admin-prod-btn-primary px-6">Apply</button>
            @if($from || $to)
                <a href="{{ route('superadmin.subscription-revenue.index') }}" class="admin-prod-btn-ghost px-4">Clear</a>
            @endif
            <p class="text-xs text-slate-500 basis-full sm:basis-auto sm:ml-auto self-center">
                {{ $from || $to ? 'Filtered range' : 'All time' }}
            </p>
        </form>

        <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Total revenue</p>
                <p class="mt-2 text-2xl font-extrabold tracking-tight text-[#232f3e]">{{ number_format($totalRevenue, 0) }} TZS</p>
            </div>
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Total profit</p>
                <p class="mt-2 text-2xl font-extrabold tracking-tight text-emerald-700">{{ number_format($totalProfit, 0) }} TZS</p>
            </div>
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Payments</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#232f3e]">{{ number_format($paymentsCount) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ number_format($registrationsCount) }} new · {{ number_format($renewalsCount) }} renewals</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="admin-clay-panel overflow-hidden">
                <div class="admin-prod-form-head border-b border-white/60">
                    <h2 class="admin-prod-form-title">Revenue by package</h2>
                </div>
                <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                    <table class="min-w-[480px]">
                        <thead>
                            <tr>
                                <th scope="col" class="admin-prod-th">Package</th>
                                <th scope="col" class="admin-prod-th">Payments</th>
                                <th scope="col" class="admin-prod-th">Revenue</th>
                                <th scope="col" class="admin-prod-th admin-prod-th--end">Profit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($byPackage as $pkg)
                                <tr>
                                    <td class="font-semibold text-[#232f3e]">{{ $pkg['name'] }}</td>
                                    <td class="text-slate-600">{{ number_format($pkg['payments']) }}</td>
                                    <td class="text-slate-600 whitespace-nowrap">{{ number_format($pkg['revenue'], 0) }} TZS</td>
                                    <td class="font-semibold text-emerald-700 whitespace-nowrap text-right">{{ number_format($pkg['profit'], 0) }} TZS</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="admin-prod-muted py-8 text-center">No payments in this range.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="admin-clay-panel overflow-hidden">
                <div class="admin-prod-form-head border-b border-white/60">
                    <h2 class="admin-prod-form-title">Revenue by vendor</h2>
                </div>
                <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                    <table class="min-w-[480px]">
                        <thead>
                            <tr>
                                <th scope="col" class="admin-prod-th">Vendor</th>
                                <th scope="col" class="admin-prod-th">Payments</th>
                                <th scope="col" class="admin-prod-th">Revenue</th>
                                <th scope="col" class="admin-prod-th admin-prod-th--end">Profit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($byVendor as $vendor)
                                <tr>
                                    <td class="font-semibold text-[#232f3e]">{{ $vendor['name'] }}</td>
                                    <td class="text-slate-600">{{ number_format($vendor['payments']) }}</td>
                                    <td class="text-slate-600 whitespace-nowrap">{{ number_format($vendor['revenue'], 0) }} TZS</td>
                                    <td class="font-semibold text-emerald-700 whitespace-nowrap text-right">{{ number_format($vendor['profit'], 0) }} TZS</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="admin-prod-muted py-8 text-center">No payments in this range.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="admin-clay-panel overflow-hidden">
            <div class="admin-prod-form-head border-b border-white/60">
                <h2 class="admin-prod-form-title">Payments</h2>
                <p class="admin-prod-form-hint">Individual completed subscription payments (most recent first, up to 200).</p>
            </div>
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table class="min-w-[760px]">
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Date</th>
                            <th scope="col" class="admin-prod-th">Vendor</th>
                            <th scope="col" class="admin-prod-th">Package</th>
                            <th scope="col" class="admin-prod-th">Type</th>
                            <th scope="col" class="admin-prod-th">Amount</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr>
                                <td class="text-slate-600 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($payment->created_at)->format('d M Y') }}</td>
                                <td class="font-semibold text-[#232f3e]">{{ $payment->vendor_display }}</td>
                                <td class="text-slate-600">{{ $payment->package_display }}</td>
                                <td>
                                    @if($payment->intent_type === 'renewal')
                                        <span class="admin-prod-count-pill admin-prod-count-pill--info">Renewal</span>
                                    @else
                                        <span class="admin-prod-count-pill admin-prod-count-pill--neutral">New</span>
                                    @endif
                                </td>
                                <td class="text-slate-600 whitespace-nowrap">{{ number_format($payment->amount, 0) }} TZS</td>
                                <td class="font-semibold text-emerald-700 whitespace-nowrap text-right">{{ number_format($payment->profit, 0) }} TZS</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="admin-prod-muted py-8 text-center">No completed subscription payments{{ $from || $to ? ' in this range' : '' }} yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-superadmin-layout>
