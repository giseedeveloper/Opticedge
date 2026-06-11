<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page admin-prod-page--narrow">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-8">
            <div>
                <p class="admin-prod-eyebrow">Dealers</p>
                <h1 class="admin-prod-title">Edit distribution sale</h1>
                <p class="admin-prod-subtitle">Record installment payments like purchases; pending updates automatically.</p>
            </div>
            <a href="{{ route('admin.stock.distribution') }}" class="admin-prod-back shrink-0">Back to list</a>
        </div>

        <div class="admin-clay-panel admin-prod-form-shell overflow-hidden">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Payment</h2>
            </div>
            <form action="{{ route('admin.stock.update-distribution', $sale->id) }}" method="POST" class="admin-prod-form-body space-y-6">
                @csrf
                @method('PUT')

                @php
                    $saleTotal = (float) ($sale->total_selling_value ?? 0);
                    $alreadyPaid = (float) ($sale->paid_amount ?? 0);
                    $pendingNow = max(0, $saleTotal - $alreadyPaid);
                @endphp

                <div class="text-sm text-slate-600 rounded-lg border border-slate-200/80 bg-slate-50/50 p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="space-y-2">
                            <p><span class="font-semibold text-slate-800">Invoice:</span> {{ $sale->displayInvoiceNumber() }}</p>
                            <p><span class="font-semibold text-slate-800">Dealer:</span> {{ $sale->dealer_name ?? $sale->dealer?->name ?? 'N/A' }}</p>
                            <p><span class="font-semibold text-slate-800">Product:</span> {{ $sale->product ? ($sale->product->name) : 'N/A' }}</p>
                            <p><span class="font-semibold text-slate-800">Total selling value:</span> {{ number_format($saleTotal, 2) }} TZS</p>
                            <p><span class="font-semibold text-slate-800">Already paid:</span> {{ number_format($alreadyPaid, 2) }} TZS</p>
                            <p><span class="font-semibold text-slate-800">Remaining:</span> <strong class="text-amber-800">{{ number_format($pendingNow, 2) }} TZS</strong></p>
                        </div>
                        <a href="{{ route('admin.stock.distribution-invoice', $sale->id) }}"
                           class="admin-prod-btn-primary shrink-0"
                           title="Download invoice"
                           aria-label="Download invoice">
                            Download invoice
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="collection_date" class="admin-prod-label">Collection / paid date</label>
                        <input type="date" name="collection_date" id="collection_date" value="{{ old('collection_date', $sale->collection_date) }}" class="admin-prod-input">
                        @error('collection_date')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="paid_amount" class="admin-prod-label">Pay (this time)</label>
                        <input
                            type="number"
                            step="0.01"
                            name="paid_amount"
                            id="paid_amount"
                            value="{{ old('paid_amount', 0) }}"
                            min="0"
                            max="{{ $pendingNow }}"
                            class="admin-prod-input"
                            oninput="updatePendingPreview()">
                        @error('paid_amount')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-slate-500 mt-1">
                            You can pay up to the remaining balance: {{ number_format($pendingNow, 2) }} TZS.
                        </p>
                    </div>
                    <div>
                        <label for="payment_option_id" class="admin-prod-label">Payment channel <span class="text-red-600">*</span></label>
                        <select name="payment_option_id" id="payment_option_id" class="admin-prod-select">
                            <option value="">Select channel</option>
                            @foreach($paymentOptions as $option)
                                <option value="{{ $option->id }}"
                                    data-balance="{{ $option->balance }}"
                                    {{ (string) old('payment_option_id', $sale->payment_option_id) === (string) $option->id ? 'selected' : '' }}>
                                    {{ $option->name }} (Balance: {{ number_format($option->balance, 2) }})
                                </option>
                            @endforeach
                        </select>
                        @error('payment_option_id')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-slate-500 mt-1">Required when you enter a pay amount. This installment is credited to the channel balance (money received).</p>
                    </div>
                    <div>
                        <label class="admin-prod-label">Remaining after this payment</label>
                        <input type="text" id="pending_preview" readonly class="admin-prod-input font-medium cursor-not-allowed" value="{{ number_format($pendingNow, 2) }}">
                        <p class="text-xs text-slate-500 mt-1">Total selling value − (already paid + pay this time). Updates as you type.</p>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-4 mt-2">
                    <h3 class="text-lg font-medium text-slate-900 mb-4">Payment history</h3>
                    @php
                        try {
                            $distPayments = $sale->payments ?? collect();
                        } catch (\Exception $e) {
                            $distPayments = collect();
                        }
                    @endphp
                    @if($distPayments && $distPayments->count() > 0)
                        <div class="bg-slate-50 rounded-lg border border-slate-200 overflow-hidden">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-slate-100 border-b border-slate-200">
                                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-700">Date</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-slate-700">Channel</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-slate-700">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach($distPayments as $payment)
                                        <tr>
                                            <td class="px-4 py-2 text-slate-600">{{ $payment->paid_date ? $payment->paid_date->format('Y-m-d') : $payment->created_at->format('Y-m-d') }}</td>
                                            <td class="px-4 py-2 text-slate-600">{{ $payment->paymentOption ? $payment->paymentOption->name : 'N/A' }}</td>
                                            <td class="px-4 py-2 text-right font-medium text-slate-900">{{ number_format($payment->amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="bg-slate-100 border-t-2 border-slate-300">
                                        <td colspan="2" class="px-4 py-2 text-right font-semibold text-slate-700">Total paid:</td>
                                        <td class="px-4 py-2 text-right font-bold text-slate-900">{{ number_format($distPayments->sum('amount'), 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="bg-slate-50 rounded-lg border border-slate-200 p-4 text-center text-slate-500 text-sm">
                            No payment history recorded yet.
                        </div>
                    @endif
                </div>

                <div class="admin-prod-form-footer !mt-0 !pt-0 !border-0 !shadow-none">
                    <a href="{{ route('admin.stock.distribution') }}" class="admin-prod-btn-ghost">Cancel</a>
                    <button type="submit" class="admin-prod-btn-primary px-8">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function() {
            var pendingBase = {{ $pendingNow }};
            var paidInput = document.getElementById('paid_amount');
            var pendingEl = document.getElementById('pending_preview');

            function updatePendingPreview() {
                if (!paidInput || !pendingEl) {
                    return;
                }
                var payNow = parseFloat(paidInput.value) || 0;
                if (payNow > pendingBase) {
                    payNow = pendingBase;
                    paidInput.value = pendingBase;
                }
                var after = Math.max(0, pendingBase - payNow);
                pendingEl.value = after.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            if (paidInput) {
                paidInput.addEventListener('input', updatePendingPreview);
            }
        })();
    </script>
</x-admin-layout>
