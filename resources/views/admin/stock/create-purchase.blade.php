@php
    $isPassthrough = $isPassthrough ?? false;
    $listRoute = $isPassthrough ? 'admin.stock.passthrough' : 'admin.stock.purchases';
    $storeRoute = $isPassthrough ? 'admin.stock.store-passthrough' : 'admin.stock.store-purchase';
@endphp
<x-admin-layout>
    @include('admin.partials.catalog-styles')
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <style>
            .select2-container--default .select2-selection--single {
                min-height: 42px;
                padding: 6px 8px;
                border-color: #cbd5e1;
            }
            .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 28px;
            }
            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 40px;
            }
        </style>
    @endpush
    <div class="admin-prod-page admin-prod-form-wide">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-8">
            <div>
                <p class="admin-prod-eyebrow">Inventory</p>
                <h1 class="admin-prod-title">{{ $isPassthrough ? 'Add passthrough' : 'Add purchase' }}</h1>
                <p class="admin-prod-subtitle">{{ $isPassthrough ? 'Record stock without IMEI tracking.' : 'Record a new stock purchase.' }}</p>
            </div>
            <a href="{{ route($listRoute) }}" class="admin-prod-back shrink-0">Back to list</a>
        </div>

        <div class="admin-clay-panel admin-prod-form-shell overflow-hidden admin-prod-select2-wrap">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Purchase details</h2>
                <p class="admin-prod-form-hint">Invoice, branch, and pricing.</p>
            </div>
            <form action="{{ route($storeRoute) }}" method="POST" enctype="multipart/form-data" class="admin-prod-form-body">
                    @csrf
                    @if($fromStock)
                        <input type="hidden" name="stock_id" value="{{ $fromStock->id }}">
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @if($fromStock)
                            <!-- Stock name (from stock – read-only) -->
                            <div class="col-span-2">
                                <label class="admin-prod-label">Stock</label>
                                <div class="admin-prod-readonly-box font-medium">{{ $fromStock->name }}</div>
                                <p class="text-xs text-slate-500 mt-1">Category and model from products in this stock (as added in the app). Quantity = stock limit.</p>
                            </div>
                        @endif

                        <!-- Date -->
                        <div class="col-span-1">
                            <label for="date" class="admin-prod-label">Date of Purchase <span class="text-red-500">*</span></label>
                            <input type="date" name="date" id="date" value="{{ old('date', date('Y-m-d')) }}" required max="{{ date('Y-m-d') }}" class="admin-prod-input">
                            @error('date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            <p class="text-xs text-slate-500 mt-1">Date cannot be in the future</p>
                        </div>

                        <!-- Distributor -->
                        <div class="col-span-1">
                            <label for="distributor_name" class="admin-prod-label">Distributor Name <span class="text-red-500">*</span></label>
                            <select name="distributor_name" id="distributor_name" required class="admin-prod-select">
                                <option value="">{{ __('Select vendor…') }}</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->name }}" {{ old('distributor_name') === $vendor->name ? 'selected' : '' }}>
                                        {{ $vendor->name }}@if($vendor->office_name) — {{ $vendor->office_name }}@endif
                                    </option>
                                @endforeach
                            </select>
                            @error('distributor_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <!-- Branch -->
                        <div class="col-span-2">
                            <label for="branch_id" class="admin-prod-label">Branch <span class="text-red-500">*</span></label>
                            <select name="branch_id" id="branch_id" required class="admin-prod-select">
                                <option value="">Select branch…</option>
                                @foreach($branches ?? [] as $branch)
                                    <option value="{{ $branch->id }}" {{ (string) old('branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <!-- Category + model: from stock (read-only), or multiple catalog lines -->
                        @if($fromStock)
                            <div class="col-span-1">
                                <label class="admin-prod-label">Category</label>
                                <div class="admin-prod-readonly-box">{{ $fromStock->purchase_category_name ?? '–' }}</div>
                                <input type="hidden" name="category_id" value="{{ $fromStock->purchase_category_id }}">
                                @error('category_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div class="col-span-1">
                                <label class="admin-prod-label">Model (product name)</label>
                                <div class="admin-prod-readonly-box">{{ $fromStock->purchase_model }}</div>
                                <input type="hidden" name="model" value="{{ $fromStock->purchase_model }}">
                                @error('model') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        @else
                            @php
                                $initialLines = old('lines');
                                if (! is_array($initialLines) || count($initialLines) === 0) {
                                    $initialLines = [['product_id' => '', 'quantity' => '', 'unit_price' => '', 'sell_price' => '']];
                                }
                            @endphp
                            <div class="col-span-2">
                                <label class="admin-prod-label">Models &amp; quantities <span class="text-red-500">*</span></label>
                                <p class="text-xs text-slate-500 mb-2">Add one row per device model. Each row has its own unit cost, optional sell price, and quantity{{ $isPassthrough ? '' : ' (IMEI slots when you add devices later)' }}.</p>
                                @error('lines') <span class="text-red-500 text-xs block mb-2">{{ $message }}</span> @enderror
                                <div class="overflow-x-auto border border-slate-200 rounded-lg">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-slate-50 text-slate-700">
                                            <tr>
                                                <th class="text-left px-3 py-2 font-semibold">Model</th>
                                                <th class="text-left px-3 py-2 font-semibold w-28">Qty</th>
                                                <th class="text-left px-3 py-2 font-semibold w-32">Unit</th>
                                                <th class="text-left px-3 py-2 font-semibold w-32">Sell</th>
                                                <th class="w-10"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="purchase_line_rows">
                                            @foreach($initialLines as $idx => $lineRow)
                                                <tr class="purchase-line-row align-top" data-line-index="{{ $idx }}">
                                                    <td class="px-3 py-2">
                                                        <select name="lines[{{ $idx }}][product_id]" class="admin-prod-select js-line-product-select w-full min-w-[220px]" required>
                                                            <option value="">Select model…</option>
                                                            @foreach($productsForSelect as $p)
                                                                <option value="{{ $p->id }}" {{ (string) ($lineRow['product_id'] ?? '') === (string) $p->id ? 'selected' : '' }}>
                                                                    {{ ($p->category?->name ?? '—') }} — {{ $p->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        @error('lines.'.$idx.'.product_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        <input type="number" name="lines[{{ $idx }}][quantity]" value="{{ $lineRow['quantity'] ?? '' }}" min="1" required class="admin-prod-input line-qty w-full" oninput="calculateTotal()">
                                                        @error('lines.'.$idx.'.quantity') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        <input type="number" step="0.01" name="lines[{{ $idx }}][unit_price]" value="{{ $lineRow['unit_price'] ?? '' }}" min="0.01" required class="admin-prod-input line-unit w-full" oninput="calculateTotal()">
                                                        @error('lines.'.$idx.'.unit_price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                    </td>
                                                    <td class="px-3 py-2">
                                                        <input type="number" step="0.01" name="lines[{{ $idx }}][sell_price]" value="{{ $lineRow['sell_price'] ?? '' }}" min="0" placeholder="—" class="admin-prod-input line-sell w-full" oninput="calculateTotal()">
                                                        @error('lines.'.$idx.'.sell_price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                    </td>
                                                    <td class="px-1 py-2 text-right">
                                                        <button type="button" class="text-rose-600 hover:text-rose-800 text-xs font-medium remove-line-row" title="Remove row">✕</button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <button type="button" id="add_purchase_line" class="mt-2 text-sm font-medium text-[#fa8900] hover:underline">+ Add another model</button>
                            </div>
                        @endif

                        <!-- Quantity: from stock limit (read-only) or embedded in line rows -->
                        @if($fromStock)
                        <div class="col-span-1">
                            <label for="quantity" class="admin-prod-label">Quantity <span class="text-red-500">*</span></label>
                                <div class="admin-prod-readonly-box">{{ $fromStock->purchase_quantity }}</div>
                                <input type="hidden" name="quantity" id="quantity" value="{{ $fromStock->purchase_quantity }}">
                            @error('quantity') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <!-- Unit Price -->
                        <div class="col-span-1">
                            <label for="unit_price" class="admin-prod-label">Unit Price <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" name="unit_price" id="unit_price" value="{{ old('unit_price') }}" required min="0.01" class="admin-prod-input" oninput="calculateTotal()">
                            @error('unit_price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            <p class="text-xs text-slate-500 mt-1">Must be greater than 0</p>
                        </div>

                        <!-- Sell Price -->
                        <div class="col-span-1">
                            <label for="sell_price" class="admin-prod-label">Sell Price (optional)</label>
                            <input type="number" step="0.01" name="sell_price" id="sell_price" value="{{ old('sell_price') }}" min="0" placeholder="Optional" class="admin-prod-input">
                            @error('sell_price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            <p class="text-xs text-slate-500 mt-1">Resale price if different from cost</p>
                        </div>
                        @endif

                        <!-- Total Value (Read Only) -->
                        <div class="col-span-2">
                            <label for="total_amount" class="admin-prod-label">Total Purchase Value</label>
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-slate-600">Total:</span>
                                    <input type="text" id="total_amount" readonly class="text-2xl font-bold text-slate-900 bg-transparent border-0 p-0 text-right cursor-not-allowed" value="0.00">
                                </div>
                                <p class="text-xs text-slate-500 mt-2">
                                    @if($fromStock)
                                        Calculated as: Quantity × Unit Price
                                    @else
                                        Sum of (quantity × unit price) for all models
                                    @endif
                                </p>
                            </div>
                            <input type="hidden" id="total_value" name="total_value">
                        </div>

                        <div class="col-span-2">
                            <label for="note" class="admin-prod-label">Note <span class="text-slate-400 font-normal">(optional)</span></label>
                            <textarea name="note" id="note" rows="3" class="admin-prod-textarea" placeholder="Internal note about this purchase…">{{ old('note') }}</textarea>
                            @error('note') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-span-2 border-t border-slate-100 pt-4 mt-2">
                            <h3 class="text-lg font-medium text-slate-900 mb-2">Payment (optional)</h3>
                            <p class="text-xs text-slate-500 mb-4">If you pay the supplier now, choose which <strong>channel</strong> (bank / mobile / cash) the money leaves from. Only channels shown under <strong>Channels</strong> (not hidden) appear here.</p>
                        </div>

                        <div class="col-span-1">
                            <label for="paid_date" class="admin-prod-label">Paid date</label>
                            <input type="date" name="paid_date" id="paid_date" value="{{ old('paid_date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" class="admin-prod-input">
                            @error('paid_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-span-1">
                            <label for="paid_amount" class="admin-prod-label">Paid amount (optional)</label>
                            <input type="number" step="0.01" name="paid_amount" id="paid_amount" value="{{ old('paid_amount', '') }}" min="0" placeholder="0.00" class="admin-prod-input">
                            @error('paid_amount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-span-1">
                            <label for="payment_option_id" class="admin-prod-label">Payment channel</label>
                            <select name="payment_option_id" id="payment_option_id" class="admin-prod-select">
                                <option value="">{{ __('Select channel…') }}</option>
                                @foreach($paymentOptions ?? [] as $option)
                                    <option value="{{ $option->id }}" {{ (string) old('payment_option_id') === (string) $option->id ? 'selected' : '' }}>
                                        {{ $option->name }} ({{ \App\Models\PaymentOption::types()[$option->type] ?? $option->type }} — {{ number_format($option->balance, 2) }} TZS)
                                    </option>
                                @endforeach
                            </select>
                            @error('payment_option_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-span-1">
                            <label for="payment_receipt_image" class="admin-prod-label">Payment receipt (optional)</label>
                            <input type="file" name="payment_receipt_image" id="payment_receipt_image" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" class="admin-prod-input text-sm">
                            @error('payment_receipt_image') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                    </div>

                    <div class="admin-prod-form-footer !mt-6">
                        <a href="{{ route($listRoute) }}" class="admin-prod-btn-ghost">Cancel</a>
                        <button type="submit" class="admin-prod-btn-primary px-8" onclick="return validatePurchaseForm()">{{ $isPassthrough ? 'Save passthrough' : 'Save purchase' }}</button>
                    </div>
                </form>
        </div>
    </div>
    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            const purchaseFromStock = @json((bool) $fromStock);
            const isPassthrough = @json((bool) $isPassthrough);

            function reindexPurchaseLineRows() {
                const rows = document.querySelectorAll('#purchase_line_rows tr.purchase-line-row');
                rows.forEach(function(tr, i) {
                    tr.querySelectorAll('[name^="lines["]').forEach(function(el) {
                        const n = el.getAttribute('name');
                        if (!n) return;
                        el.setAttribute('name', n.replace(/lines\[\d+\]/, 'lines[' + i + ']'));
                    });
                });
            }

            function initLineProductSelect2($sel) {
                if (!window.jQuery || !jQuery.fn.select2) return;
                if ($sel.data('select2')) {
                    $sel.select2('destroy');
                }
                $sel.select2({
                    placeholder: 'Search category — model…',
                    width: '100%',
                    allowClear: false
                });
            }

            function validatePurchaseForm() {
                const branchId = document.getElementById('branch_id')?.value || '';
                if (!branchId) {
                    alert('❌ Branch is required');
                    document.getElementById('branch_id')?.focus();
                    return false;
                }

                const paid = parseFloat(document.getElementById('paid_amount')?.value) || 0;
                if (paid > 0) {
                    const channel = document.getElementById('payment_option_id')?.value || '';
                    if (!channel) {
                        alert('❌ Select a payment channel when paying an amount now (e.g. your bank account).');
                        document.getElementById('payment_option_id')?.focus();
                        return false;
                    }
                }

                if (purchaseFromStock) {
                    const quantity = parseFloat(document.getElementById('quantity')?.value) || 0;
                    const price = parseFloat(document.getElementById('unit_price')?.value) || 0;
                    if (quantity <= 0) {
                        alert('❌ Quantity must be greater than 0');
                        document.getElementById('quantity')?.focus();
                        return false;
                    }
                    if (price <= 0) {
                        alert('❌ Unit price must be greater than 0');
                        document.getElementById('unit_price')?.focus();
                        return false;
                    }
                    const total = (quantity * price).toFixed(2);
                    return confirm('✓ Confirm purchase?\n\nQuantity: ' + quantity + '\nUnit Price: ' + price.toFixed(2) + ' TZS\nTotal: ' + total + ' TZS');
                }

                const rows = document.querySelectorAll('#purchase_line_rows tr.purchase-line-row');
                if (!rows.length) {
                    alert('❌ Add at least one model row.');
                    return false;
                }

                const seenProducts = {};
                let total = 0;
                let totalQty = 0;
                for (let r = 0; r < rows.length; r++) {
                    const tr = rows[r];
                    const sel = tr.querySelector('.js-line-product-select');
                    const pid = sel && sel.value ? String(sel.value) : '';
                    if (!pid) {
                        alert('❌ Select a catalog model on every row.');
                        sel && sel.focus();
                        return false;
                    }
                    if (seenProducts[pid]) {
                        alert('❌ The same model appears twice. Use one row per model or merge quantities.');
                        return false;
                    }
                    seenProducts[pid] = true;

                    const q = parseFloat(tr.querySelector('.line-qty')?.value) || 0;
                    const u = parseFloat(tr.querySelector('.line-unit')?.value) || 0;
                    if (q <= 0) {
                        alert('❌ Each row needs quantity greater than 0.');
                        tr.querySelector('.line-qty')?.focus();
                        return false;
                    }
                    if (u <= 0) {
                        alert('❌ Each row needs unit price greater than 0.');
                        tr.querySelector('.line-unit')?.focus();
                        return false;
                    }
                    total += q * u;
                    totalQty += q;
                }

                const label = isPassthrough ? 'passthrough' : 'purchase';
                const qtyLabel = isPassthrough ? 'Total quantity' : 'Total devices (IMEI slots)';
                return confirm('✓ Confirm ' + label + '?\n\nRows: ' + rows.length + '\n' + qtyLabel + ': ' + totalQty + '\nTotal value: ' + total.toFixed(2) + ' TZS');
            }

            function calculateTotal() {
                let total = 0;
                let ok = true;

                if (purchaseFromStock) {
                    const qty = parseFloat(document.getElementById('quantity')?.value) || 0;
                    const price = parseFloat(document.getElementById('unit_price')?.value) || 0;
                    total = qty * price;
                    ok = qty > 0 && price > 0;
                } else {
                    document.querySelectorAll('#purchase_line_rows tr.purchase-line-row').forEach(function(tr) {
                        const q = parseFloat(tr.querySelector('.line-qty')?.value) || 0;
                        const u = parseFloat(tr.querySelector('.line-unit')?.value) || 0;
                        total += q * u;
                        const sel = tr.querySelector('.js-line-product-select');
                        if (!sel || !sel.value || q <= 0 || u <= 0) {
                            ok = false;
                        }
                    });
                }

                const el = document.getElementById('total_amount');
                const hiddenEl = document.getElementById('total_value');
                if (el) {
                    el.value = total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' TZS';
                }
                if (hiddenEl) {
                    hiddenEl.value = total;
                }

                const submitBtn = document.querySelector('form[action*="store-purchase"] [type="submit"]') || document.querySelector('[type="submit"]');
                if (submitBtn) {
                    if (ok) {
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    } else {
                        submitBtn.disabled = true;
                        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    }
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                calculateTotal();

                if (window.jQuery && jQuery.fn.select2) {
                    var $vendorSel = jQuery('#distributor_name');
                    $vendorSel.select2({
                        placeholder: 'Select vendor…',
                        width: '100%',
                        allowClear: true
                    });

                    var $branchSel = jQuery('#branch_id');
                    $branchSel.select2({
                        placeholder: 'Select branch…',
                        width: '100%',
                        allowClear: false
                    });
                }

                if (!purchaseFromStock && window.jQuery && jQuery.fn.select2) {
                    jQuery('#purchase_line_rows .js-line-product-select').each(function() {
                        initLineProductSelect2(jQuery(this));
                    });

                    document.getElementById('add_purchase_line')?.addEventListener('click', function() {
                        const tbody = document.getElementById('purchase_line_rows');
                        const first = tbody.querySelector('tr.purchase-line-row');
                        if (!first) return;
                        jQuery('#purchase_line_rows .js-line-product-select').each(function() {
                            if (jQuery(this).data('select2')) {
                                jQuery(this).select2('destroy');
                            }
                        });
                        const clone = first.cloneNode(true);
                        clone.querySelectorAll('input').forEach(function(inp) {
                            inp.value = '';
                        });
                        const sel = clone.querySelector('.js-line-product-select');
                        if (sel) {
                            sel.selectedIndex = 0;
                        }
                        tbody.appendChild(clone);
                        reindexPurchaseLineRows();
                        jQuery('#purchase_line_rows .js-line-product-select').each(function() {
                            initLineProductSelect2(jQuery(this));
                        });
                        calculateTotal();
                    });

                    document.getElementById('purchase_line_rows')?.addEventListener('click', function(e) {
                        if (!e.target.classList.contains('remove-line-row')) return;
                        const tr = e.target.closest('tr.purchase-line-row');
                        const tbody = document.getElementById('purchase_line_rows');
                        if (!tr || !tbody) return;
                        if (tbody.querySelectorAll('tr.purchase-line-row').length <= 1) {
                            alert('At least one model row is required.');
                            return;
                        }
                        jQuery('#purchase_line_rows .js-line-product-select').each(function() {
                            if (jQuery(this).data('select2')) {
                                jQuery(this).select2('destroy');
                            }
                        });
                        tr.remove();
                        reindexPurchaseLineRows();
                        jQuery('#purchase_line_rows .js-line-product-select').each(function() {
                            initLineProductSelect2(jQuery(this));
                        });
                        calculateTotal();
                    });
                }
            });
        </script>
    @endpush
</x-admin-layout>
