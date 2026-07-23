<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <a href="{{ route('admin.payout.index') }}" class="admin-prod-back mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Pay out
        </a>

        <div class="admin-prod-toolbar !mb-4">
            <div>
                <p class="admin-prod-eyebrow">Pay out</p>
                <h1 class="admin-prod-title">Wallet history</h1>
                <p class="admin-prod-subtitle">Deposits and payouts on your disbursement wallet.</p>
            </div>
            <div class="admin-clay-panel px-5 py-3 shrink-0 text-right">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Balance</p>
                <p class="text-2xl font-extrabold tracking-tight text-[#232f3e]">{{ number_format($balance, 0) }} TZS</p>
            </div>
        </div>

        <div class="admin-clay-panel overflow-hidden">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table data-no-datatable class="min-w-[640px]">
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Date</th>
                            <th scope="col" class="admin-prod-th">Type</th>
                            <th scope="col" class="admin-prod-th">Description</th>
                            <th scope="col" class="admin-prod-th">Amount</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Balance after</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $txn)
                            @php
                                $isCredit = $txn->direction === 'credit';
                                $typeLabel = match ($txn->type) {
                                    'topup' => 'Top-up',
                                    'payout' => 'Payout',
                                    'payout_reversal' => 'Payout reversal',
                                    'adjustment' => 'Adjustment',
                                    default => ucfirst(str_replace('_', ' ', $txn->type)),
                                };
                            @endphp
                            <tr>
                                <td class="text-slate-600 whitespace-nowrap">{{ $txn->created_at->format('d M Y H:i') }}</td>
                                <td>
                                    <span class="admin-prod-count-pill {{ $isCredit ? 'admin-prod-count-pill--info' : 'admin-prod-count-pill--neutral' }}">{{ $typeLabel }}</span>
                                </td>
                                <td class="text-slate-600">{{ $txn->description ?: '—' }}</td>
                                <td class="whitespace-nowrap font-semibold {{ $isCredit ? 'text-emerald-700' : 'text-red-700' }}">
                                    {{ $isCredit ? '+' : '−' }}{{ number_format((float) $txn->amount, 0) }} TZS
                                </td>
                                <td class="whitespace-nowrap text-right font-medium text-[#232f3e]">{{ number_format((float) $txn->balance_after, 0) }} TZS</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-slate-500 py-10">No wallet activity yet. Use <strong>Deposit</strong> on the Pay out page to top up.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.partials.table-pagination', ['paginator' => $transactions, 'label' => 'transactions'])
        </div>
    </div>
</x-admin-layout>
