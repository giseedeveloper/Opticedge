<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <a href="{{ route('admin.stock.passthrough') }}" class="admin-prod-back mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to passthrough
        </a>

        <div class="admin-prod-toolbar !mb-6">
            <div>
                <p class="admin-prod-eyebrow">Passthrough</p>
                <h1 class="admin-prod-title">{{ $purchase->name ?? 'Passthrough #' . $purchase->id }}</h1>
                <p class="admin-prod-subtitle">Stock quantity recorded without IMEI tracking.</p>
                <div class="mt-3 text-sm text-slate-600 space-y-1">
                    <p><span class="font-medium text-slate-800">Date:</span> {{ $purchase->date }}</p>
                    <p><span class="font-medium text-slate-800">Branch:</span> {{ $purchase->branch?->name ?? '—' }}</p>
                    <p><span class="font-medium text-slate-800">Distributor:</span> {{ $purchase->distributor_name ?? '—' }}</p>
                    @php
                        $totalVal = $purchase->total_amount ?? ($purchase->quantity * $purchase->unit_price);
                        $paidVal = (float) ($purchase->paid_amount ?? 0);
                    @endphp
                    <p><span class="font-medium text-slate-800">Total:</span> {{ number_format((float) $totalVal, 2) }} TZS
                        · <span class="font-medium">Paid:</span> {{ number_format($paidVal, 2) }}
                        · <span class="font-medium">Pending:</span> {{ number_format(max(0, $totalVal - $paidVal), 2) }}</p>
                    <p>
                        <span class="font-medium text-slate-800">Payment status:</span>
                        <span class="admin-prod-dealer-status {{ $purchase->payment_status === 'paid' ? 'admin-prod-dealer-status--active' : ($purchase->payment_status === 'partial' ? 'admin-prod-dealer-status--pending' : 'admin-prod-dealer-status--suspended') }}">
                            {{ $purchase->payment_status }}
                        </span>
                    </p>
                </div>
                @if(($purchase->lines ?? collect())->isNotEmpty())
                    <div class="mt-4 text-sm text-slate-600 space-y-1">
                        <p class="font-medium text-slate-800">Line items</p>
                        @foreach($purchase->lines as $line)
                            @php $lp = $line->product; @endphp
                            <div>
                                <span class="font-medium text-slate-800">{{ $lp?->name ?? '—' }}</span>
                                <span class="text-slate-500">· qty {{ $line->quantity }}</span>
                                <span class="text-slate-500">· unit {{ number_format((float) $line->unit_price, 2) }}</span>
                                @if($line->sell_price !== null)
                                    <span class="text-slate-500">· sell {{ number_format((float) $line->sell_price, 2) }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @elseif($purchase->product)
                    <p class="mt-3 text-sm text-slate-600">
                        <span class="font-medium text-slate-800">{{ $purchase->product->name }}</span>
                        · qty {{ $purchase->quantity }}
                        · unit {{ number_format((float) $purchase->unit_price, 2) }}
                    </p>
                @endif
                @if(!empty($purchase->note))
                    <p class="mt-3 text-sm text-slate-700 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2"><span class="font-medium text-slate-900">Note:</span> {{ $purchase->note }}</p>
                @endif
            </div>
            <a href="{{ route('admin.stock.edit-passthrough', $purchase->id) }}" class="admin-prod-btn-primary shrink-0">Edit / pay</a>
        </div>

        @if($purchase->payment_receipt_image)
            <div class="admin-clay-panel admin-prod-form-shell overflow-hidden mb-6">
                <div class="admin-prod-form-head">
                    <h2 class="admin-prod-form-title">Payment receipt</h2>
                </div>
                <div class="admin-prod-form-body">
                    <a href="{{ asset('storage/' . $purchase->payment_receipt_image) }}" target="_blank" rel="noopener noreferrer">
                        <img src="{{ asset('storage/' . $purchase->payment_receipt_image) }}" alt="Receipt" class="max-h-64 rounded-lg border border-slate-200">
                    </a>
                </div>
            </div>
        @endif

        @if(($purchase->payments ?? collect())->isNotEmpty())
            <div class="admin-clay-panel overflow-hidden">
                <div class="admin-prod-form-head">
                    <h2 class="admin-prod-form-title">Payment history</h2>
                </div>
                <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th class="admin-prod-th">Date</th>
                                <th class="admin-prod-th">Channel</th>
                                <th class="admin-prod-th admin-prod-th--end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchase->payments as $payment)
                                <tr>
                                    <td>{{ $payment->paid_date }}</td>
                                    <td>{{ $payment->paymentOption?->name ?? '—' }}</td>
                                    <td class="text-right font-variant-numeric">{{ number_format((float) $payment->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-admin-layout>
