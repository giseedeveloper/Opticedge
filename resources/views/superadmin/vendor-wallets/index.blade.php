<x-superadmin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Platform</p>
                <h1 class="admin-prod-title">Vendor wallets</h1>
                <p class="admin-prod-subtitle">Pre-funded disbursement balances each vendor uses to pay agent commissions.</p>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-4">
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Total held</p>
                <p class="mt-2 text-2xl font-extrabold tracking-tight text-[#232f3e]">{{ number_format($totalBalance, 0) }} TZS</p>
            </div>
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Funded vendors</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#232f3e]">{{ number_format($fundedCount) }}</p>
            </div>
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Lifetime deposits</p>
                <p class="mt-2 text-2xl font-extrabold tracking-tight text-emerald-700">{{ number_format($totalDeposits, 0) }} TZS</p>
            </div>
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Lifetime payouts</p>
                <p class="mt-2 text-2xl font-extrabold tracking-tight text-[#232f3e]">{{ number_format($totalPayouts, 0) }} TZS</p>
            </div>
        </div>

        <div class="admin-clay-panel overflow-hidden">
            <div class="admin-prod-form-head border-b border-white/60">
                <h2 class="admin-prod-form-title">Wallet balance by vendor</h2>
            </div>
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table class="min-w-[720px]">
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Vendor</th>
                            <th scope="col" class="admin-prod-th">Status</th>
                            <th scope="col" class="admin-prod-th">Deposits</th>
                            <th scope="col" class="admin-prod-th">Payouts</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vendors as $vendor)
                            <tr>
                                <td class="font-semibold text-[#232f3e]">{{ $vendor->name }}</td>
                                <td>
                                    @if ($vendor->status === 'active')
                                        <span class="admin-prod-count-pill admin-prod-count-pill--info">Active</span>
                                    @else
                                        <span class="admin-prod-count-pill admin-prod-count-pill--neutral">{{ ucfirst($vendor->status) }}</span>
                                    @endif
                                </td>
                                <td class="text-emerald-700 whitespace-nowrap">{{ number_format($vendor->total_deposits, 0) }} TZS</td>
                                <td class="text-slate-600 whitespace-nowrap">{{ number_format($vendor->total_payouts, 0) }} TZS</td>
                                <td class="text-right whitespace-nowrap font-bold {{ $vendor->balance > 0 ? 'text-[#232f3e]' : 'text-slate-400' }}">{{ number_format($vendor->balance, 0) }} TZS</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="admin-prod-muted py-8 text-center">No vendors yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-superadmin-layout>
