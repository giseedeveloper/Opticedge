<x-team-leader-layout title="Credit sales">
    <div class="admin-prod-page">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="admin-prod-title">Credit sales</h1>
                <p class="admin-prod-subtitle">Watu / credit sales you recorded from your custody devices.</p>
            </div>
            <a href="{{ route('team-leader.record-sale') }}" class="admin-prod-btn-primary">Record sale</a>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif

        <div class="admin-clay-panel overflow-x-auto">
            <table class="min-w-[720px] w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-slate-500 border-b border-slate-200">
                        <th class="py-3 pr-4">Date</th>
                        <th class="py-3 pr-4">Customer</th>
                        <th class="py-3 pr-4">Product</th>
                        <th class="py-3 pr-4">IMEI</th>
                        <th class="py-3 pr-4">Total</th>
                        <th class="py-3 pr-4">Paid</th>
                        <th class="py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($credits as $credit)
                        <tr>
                            <td class="py-3 pr-4">{{ $credit->date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="py-3 pr-4 font-medium text-slate-800">{{ $credit->customer_name }}</td>
                            <td class="py-3 pr-4">{{ ($credit->product?->category?->name ?? '—') . ' – ' . ($credit->product?->name ?? '—') }}</td>
                            <td class="py-3 pr-4">{{ $credit->productListItem?->imei_number ?? '—' }}</td>
                            <td class="py-3 pr-4">{{ number_format((float) $credit->total_amount, 0) }}</td>
                            <td class="py-3 pr-4">{{ number_format((float) ($credit->paid_amount ?? 0), 0) }}</td>
                            <td class="py-3">{{ $credit->payment_status }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500">No credit sales yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-team-leader-layout>
