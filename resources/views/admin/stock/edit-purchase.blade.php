@php
    $isPassthrough = $isPassthrough ?? false;
    $listRoute = $isPassthrough ? 'admin.stock.passthrough' : 'admin.stock.purchases';
    $updateRoute = $isPassthrough ? 'admin.stock.update-passthrough' : 'admin.stock.update-purchase';
    $destroyPaymentRoute = $isPassthrough ? 'admin.stock.passthrough-payment-destroy' : 'admin.stock.purchase-payment-destroy';
@endphp
<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page admin-prod-form-wide">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-8">
            <div>
                <p class="admin-prod-eyebrow">Inventory</p>
                <h1 class="admin-prod-title">{{ $isPassthrough ? 'Edit passthrough' : 'Edit purchase' }}</h1>
                <p class="admin-prod-subtitle">{{ $isPassthrough ? 'Update passthrough and payment details.' : 'Update purchase and payment details.' }}</p>
            </div>
            <a href="{{ route($listRoute) }}" class="admin-prod-back shrink-0">Back to list</a>
        </div>

        <div class="admin-clay-panel admin-prod-form-shell overflow-hidden">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Purchase</h2>
            </div>
            <form action="{{ route($updateRoute, $purchase->id) }}" method="POST" class="admin-prod-form-body">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Invoice Number -->
                        <div class="col-span-2">
                            <label for="name" class="admin-prod-label">Invoice Number</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $purchase->name) }}" class="admin-prod-input" placeholder="Auto: P26DS00001">
                            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <!-- Date -->
                        <div class="col-span-1">
                            <label for="date" class="admin-prod-label">Date of Purchase</label>
                            <input type="date" name="date" id="date" value="{{ old('date', $purchase->date) }}" disabled class="admin-prod-input cursor-not-allowed">
                        </div>

                        <!-- Distributor -->
                        <div class="col-span-1">
                            <label for="distributor_name" class="admin-prod-label">Distributor Name</label>
                            <input list="distributors" name="distributor_name" id="distributor_name" value="{{ old('distributor_name', $purchase->distributor_name) }}" disabled class="admin-prod-input cursor-not-allowed">
                        </div>

                        <!-- Category -->
                        <div class="col-span-1">
                            <label for="category_id" class="admin-prod-label">Category</label>
                            <select name="category_id" id="category_id" disabled class="admin-prod-select cursor-not-allowed">
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id', $purchase->product->category_id ?? '') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Model -->
                        <div class="col-span-1">
                            <label for="model" class="admin-prod-label">Model (Product Name)</label>
                            <input type="text" name="model" id="model" value="{{ old('model', $purchase->product->name ?? '') }}" disabled class="admin-prod-input cursor-not-allowed">
                        </div>

                        <!-- Quantity -->
                        <div class="col-span-1">
                            <label for="quantity" class="admin-prod-label">Quantity</label>
                            <input type="number" name="quantity" id="quantity" value="{{ old('quantity', $purchase->quantity) }}" disabled class="admin-prod-input cursor-not-allowed">
                        </div>

                        <!-- Unit Price -->
                        <div class="col-span-1">
                            <label for="unit_price" class="admin-prod-label">Unit Price</label>
                            <input type="number" step="0.01" name="unit_price" id="unit_price" value="{{ old('unit_price', $purchase->unit_price) }}" disabled class="admin-prod-input cursor-not-allowed">
                        </div>

                        <!-- Total Value (Read Only) -->
                        <div class="col-span-1">
                            <label for="total_amount" class="admin-prod-label">Total Purchase Value</label>
                            <input type="text" id="total_amount" readonly class="admin-prod-input font-bold cursor-not-allowed" value="{{ number_format($purchase->quantity * $purchase->unit_price, 2) }}">
                        </div>

                        <!-- Sell Price -->
                        <div class="col-span-1">
                            <label for="sell_price" class="admin-prod-label">Sell Price (optional)</label>
                            <input type="number" step="0.01" name="sell_price" id="sell_price" value="{{ old('sell_price', $purchase->sell_price) }}" min="0" placeholder="Optional" class="admin-prod-input">
                            @error('sell_price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            <p class="text-xs text-slate-500 mt-1">Selling price per unit. If set, updates the product's default price.</p>
                        </div>

                        @php
                            $productImages = [];
                            if ($purchase->product) {
                                $productImages = is_string($purchase->product->images ?? null) ? json_decode($purchase->product->images, true) : ($purchase->product->images ?? []);
                                $productImages = is_array($productImages) ? $productImages : [];
                            }
                        @endphp
                        <!-- Product Images (display only; managed from Product) -->
                        <div class="col-span-2">
                            <label class="admin-prod-label">Product Images</label>
                            @if(count($productImages) > 0)
                                <div class="flex flex-wrap gap-2 mb-2">
                                    @foreach($productImages as $img)
                                        <img src="{{ asset('storage/' . $img) }}" alt="Product" class="w-16 h-16 object-cover rounded border border-slate-200">
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-slate-500">No images set for this product. You can add them from the product edit page.</p>
                            @endif
                        </div>

                        <div class="col-span-2 border-t border-slate-100 pt-4 mt-2">
                            <h3 class="text-lg font-medium text-slate-900 mb-4">Payment Details</h3>
                        </div>

                        <!-- Paid Date -->
                        <div class="col-span-1">
                            <label for="paid_date" class="admin-prod-label">Paid Date</label>
                            <input type="date" name="paid_date" id="paid_date" value="{{ old('paid_date', $purchase->paid_date) }}" class="admin-prod-input">
                        </div>

                        <!-- Paid Amount -->
                        <div class="col-span-1">
                            <label for="paid_amount" class="admin-prod-label">Paid (this time)</label>
                            @php
                                $purchaseTotal = $purchase->total_amount ?? ($purchase->quantity * $purchase->unit_price);
                                $alreadyPaid = (float) ($purchase->paid_amount ?? 0);
                                $pendingNow = max(0, $purchaseTotal - $alreadyPaid);
                            @endphp
                            <p class="text-xs text-slate-600 mb-1">Already paid: <span class="font-medium text-slate-900">{{ number_format($alreadyPaid, 2) }}</span></p>
                            <input
                                type="number"
                                step="0.01"
                                name="paid_amount"
                                id="paid_amount"
                                value="{{ old('paid_amount', 0) }}"
                                min="0"
                                max="{{ $pendingNow }}"
                                class="admin-prod-input"
                                oninput="updatePendingAmount()">
                            @error('paid_amount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            <p class="text-xs text-slate-500 mt-1">
                                You can pay up to the remaining balance: {{ number_format($pendingNow, 2) }}.
                            </p>
                        </div>

                        <!-- Pending Amount (read-only, shows actual pending amount) -->
                        <div class="col-span-1">
                            <label class="admin-prod-label">Pending Amount</label>
                            <input type="text" id="pending_amount" readonly class="admin-prod-input font-medium cursor-not-allowed" value="{{ number_format($pendingNow, 2) }}">
                            <p class="text-xs text-slate-500 mt-1">Actual pending amount = Total − total paid. Updates automatically as you type.</p>
                        </div>

                        <!-- Payment Channel -->
                        <div class="col-span-1">
                            <label for="payment_option_id" class="admin-prod-label">Payment Channel</label>
                            <select name="payment_option_id" id="payment_option_id" class="admin-prod-select">
                                <option value="">Select channel</option>
                                @foreach($paymentOptions as $option)
                                    <option
                                        value="{{ $option->id }}"
                                        data-balance="{{ $option->balance }}"
                                        {{ (string) old('payment_option_id', $purchase->payment_option_id) === (string) $option->id ? 'selected' : '' }}>
                                        {{ $option->name }} (Balance: {{ number_format($option->balance, 2) }})
                                    </option>
                                @endforeach
                            </select>
                            @error('payment_option_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            <p class="text-xs text-slate-500 mt-1"><strong>Required</strong> when “Paid (this time)” is greater than 0 — pick the bank or other channel the money leaves from. Balance for each channel is shown in the list. Only channels listed under <strong>Channels</strong> (not hidden) appear here.</p>
                            <p id="payment_channel_error" class="mt-2 text-sm font-medium text-red-600 hidden" role="alert"></p>
                        </div>

                        <!-- Payment History -->
                        <div class="col-span-2 border-t border-slate-100 pt-4 mt-2">
                            <h3 class="text-lg font-medium text-slate-900 mb-4">Payment History</h3>
                            @php
                                try {
                                    $payments = $purchase->payments ?? collect();
                                } catch (\Exception $e) {
                                    $payments = collect();
                                }
                            @endphp
                            @if($payments && $payments->count() > 0)
                                <div class="bg-slate-50 rounded-lg border border-slate-200 overflow-hidden">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="bg-slate-100 border-b border-slate-200">
                                                <th class="px-4 py-2 text-left text-xs font-medium text-slate-700">Date</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-slate-700">Channel</th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-slate-700">Amount</th>
                                                <th class="px-4 py-2 text-right text-xs font-medium text-slate-700">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200">
                                            @foreach($payments as $payment)
                                                <tr>
                                                    <td class="px-4 py-2 text-slate-600">{{ $payment->paid_date ? $payment->paid_date->format('Y-m-d') : $payment->created_at->format('Y-m-d') }}</td>
                                                    <td class="px-4 py-2 text-slate-600">{{ $payment->paymentOption ? $payment->paymentOption->name : 'N/A' }}</td>
                                                    <td class="px-4 py-2 text-right font-medium text-slate-900">{{ number_format($payment->amount, 2) }}</td>
                                                    <td class="px-4 py-2 text-right">
                                                        {{-- Button submits the standalone delete form rendered outside the main form.
                                                             A nested <form> here would be ignored by the browser and its _method=DELETE
                                                             would hijack the main update form, deleting the whole purchase. --}}
                                                        <button type="submit"
                                                            form="delete-payment-{{ $payment->id }}"
                                                            onclick="return confirm('Delete this payment? The amount will be removed from the purchase and refunded to the payment channel.');"
                                                            class="text-red-600 hover:text-red-800 text-sm font-semibold">Delete</button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="bg-slate-100 border-t-2 border-slate-300">
                                                <td colspan="2" class="px-4 py-2 text-right font-semibold text-slate-700">Total Paid:</td>
                                                <td class="px-4 py-2 text-right font-bold text-slate-900">{{ number_format($payments->sum('amount'), 2) }}</td>
                                                <td class="px-4 py-2"></td>
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
                    </div>

                    <div class="admin-prod-form-footer !mt-6">
                        <a href="{{ route($listRoute) }}" class="admin-prod-btn-ghost">Cancel</a>
                        <button type="submit" id="edit_purchase_submit" class="admin-prod-btn-primary px-8">Update purchase</button>
                    </div>
                </form>

                {{-- Payment-history delete forms live outside the main form (nested forms are invalid HTML). --}}
                @if(isset($payments) && $payments->count() > 0)
                    @foreach($payments as $payment)
                        <form id="delete-payment-{{ $payment->id }}"
                            action="{{ route($destroyPaymentRoute, ['id' => $purchase->id, 'paymentId' => $payment->id]) }}"
                            method="POST" class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endforeach
                @endif
        </div>
    </div>

    <script>
        (function() {
            var pendingBase = {{ $pendingNow }};
            var paidInput = document.getElementById('paid_amount');
            var pendingEl = document.getElementById('pending_amount');
            var optionSelect = document.getElementById('payment_option_id');
            var errEl = document.getElementById('payment_channel_error');
            var submitBtn = document.getElementById('edit_purchase_submit');
            var form = paidInput && paidInput.closest('form');

            function selectedChannelBalance() {
                if (!optionSelect) {
                    return 0;
                }
                var selectedOption = optionSelect.options[optionSelect.selectedIndex];
                if (!selectedOption) {
                    return 0;
                }
                return parseFloat(selectedOption.getAttribute('data-balance')) || 0;
            }

            function selectedChannelLabel() {
                if (!optionSelect) {
                    return '';
                }
                var selectedOption = optionSelect.options[optionSelect.selectedIndex];
                if (!selectedOption || !selectedOption.value) {
                    return '';
                }
                return (selectedOption.textContent || '').trim();
            }

            function setPaymentError(message) {
                if (!errEl) {
                    return;
                }
                if (message) {
                    errEl.textContent = message;
                    errEl.classList.remove('hidden');
                } else {
                    errEl.textContent = '';
                    errEl.classList.add('hidden');
                }
            }

            function updatePendingAmount() {
                if (!paidInput || !pendingEl) {
                    return;
                }

                var payNow = parseFloat(paidInput.value) || 0;
                if (payNow > pendingBase) {
                    payNow = pendingBase;
                    paidInput.value = pendingBase;
                }

                var channelBalance = selectedChannelBalance();
                var hasSelectedChannel = optionSelect && optionSelect.value !== '';
                var eps = 0.0001;
                var needsChannel = payNow > eps && !hasSelectedChannel;
                var exceedsChannelBalance = hasSelectedChannel && payNow > channelBalance + eps;

                if (needsChannel) {
                    paidInput.setCustomValidity('Select a payment channel when paying an amount.');
                    setPaymentError('Select a payment channel — required when “Paid (this time)” is greater than 0.');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.setAttribute('title', 'Select a payment channel to continue.');
                        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                } else if (exceedsChannelBalance) {
                    var label = selectedChannelLabel() || 'this channel';
                    var msg = 'Amount (' + payNow.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' TZS) is greater than the balance on ' + label + ' (available: ' + channelBalance.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' TZS). Lower the amount or choose another channel.';
                    paidInput.setCustomValidity('Payment amount exceeds selected channel balance.');
                    setPaymentError(msg);
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.setAttribute('title', 'Payment exceeds channel balance.');
                        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                } else {
                    paidInput.setCustomValidity('');
                    setPaymentError('');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.removeAttribute('title');
                        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                }

                var pending = Math.max(0, pendingBase - payNow);
                pendingEl.value = pending.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
            
            if (paidInput) {
                paidInput.addEventListener('input', updatePendingAmount);
            }
            if (optionSelect) {
                optionSelect.addEventListener('change', updatePendingAmount);
            }

            if (form) {
                form.addEventListener('submit', function (e) {
                    updatePendingAmount();
                    if (submitBtn && submitBtn.disabled) {
                        e.preventDefault();
                        var t = errEl && errEl.textContent ? errEl.textContent : 'Cannot save: fix payment channel or amount.';
                        window.alert(t);
                        return false;
                    }
                    if (paidInput && !paidInput.checkValidity()) {
                        e.preventDefault();
                        paidInput.reportValidity();
                        return false;
                    }
                });
            }

            updatePendingAmount();
            
        })();
    </script>
</x-admin-layout>
