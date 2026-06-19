<x-admin-layout>
    @include('admin.partials.catalog-styles')

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <style>
            .helper-text {
                font-size: 0.75rem;
                color: #64748b;
                margin-top: 0.375rem;
            }
            .admin-prod-select2-wrap .select2-container--default .select2-selection--single {
                min-height: 42px;
                padding: 6px 8px;
                border-color: #cbd5e1;
            }
            .admin-prod-select2-wrap .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 28px;
            }
            .admin-prod-select2-wrap .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 40px;
            }
            .price-summary {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(8rem, 1fr));
                gap: 0.75rem;
            }
            .price-summary__item {
                padding: 0.75rem;
                border-radius: 0.5rem;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
            }
            .price-summary__label {
                font-size: 0.6875rem;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #64748b;
            }
            .price-summary__value {
                font-size: 0.9375rem;
                font-weight: 700;
                color: #0f172a;
                margin-top: 0.25rem;
            }
        </style>
    @endpush

    <div class="admin-prod-page admin-prod-form-wide admin-prod-select2-wrap">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-8">
            <div>
                <p class="admin-prod-eyebrow">Agents</p>
                <h1 class="admin-prod-title">Record manual agent sale</h1>
                <p class="admin-prod-subtitle">Customer name → purchase → model → quantity → sell price from that purchase.</p>
            </div>
            <a href="{{ route('admin.stock.agent-sales') }}" class="admin-prod-back shrink-0">Back to list</a>
        </div>

        <div class="admin-clay-panel admin-prod-form-shell overflow-hidden">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Sale details</h2>
            </div>
            <form method="POST" action="{{ route('admin.stock.store-agent-sale') }}" class="admin-prod-form-body space-y-6" id="sale-form">
                @csrf
                <input type="hidden" name="seller_name" value="{{ old('seller_name', auth()->user()->name) }}">

                <div>
                    <label for="customer_name" class="admin-prod-label">1. Customer name <span class="text-red-500">*</span></label>
                    <input id="customer_name" type="text" name="customer_name" value="{{ old('customer_name') }}" placeholder="Customer name" required class="admin-prod-input">
                    @error('customer_name')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="date" class="admin-prod-label">Date <span class="text-red-500">*</span></label>
                    <input id="date" type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required max="{{ date('Y-m-d') }}" class="admin-prod-input">
                    @error('date')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="purchase_id" class="admin-prod-label">2. Purchase <span class="text-red-500">*</span></label>
                    <select id="purchase_id" name="purchase_id" required class="admin-prod-select">
                        <option value="">Select purchase</option>
                        @foreach($purchases as $purchase)
                            @php
                                $invoiceNo = $purchase->name ?? ('Purchase #' . $purchase->id);
                                $purchaseLabel = 'Inv no. ' . $invoiceNo;
                                $models = collect();
                                if (($purchase->lines ?? collect())->isNotEmpty()) {
                                    $models = $purchase->lines->map(fn ($line) => $line->product?->name)->filter()->unique();
                                } elseif ($purchase->product) {
                                    $models = collect([$purchase->product->name]);
                                }
                                if ($models->isNotEmpty()) {
                                    $purchaseLabel .= ' — ' . $models->implode(', ');
                                }
                            @endphp
                            <option value="{{ $purchase->id }}" @selected((string) old('purchase_id') === (string) $purchase->id)>
                                {{ $purchaseLabel }}
                            </option>
                        @endforeach
                    </select>
                    @error('purchase_id')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                    <p class="helper-text">Only models and warehouse stock linked to this purchase can be sold.</p>
                </div>

                <div>
                    <label for="product_id" class="admin-prod-label">3. Model on this purchase <span class="text-red-500">*</span></label>
                    <select id="product_id" name="product_id" required class="admin-prod-select" disabled>
                        <option value="">Select a purchase first</option>
                    </select>
                    @error('product_id')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                    <p class="helper-text" id="product-hint">Select a purchase to load models with available quantity.</p>
                </div>

                <div class="price-summary hidden" id="price-summary">
                    <div class="price-summary__item">
                        <div class="price-summary__label">Unit buy (purchase)</div>
                        <div class="price-summary__value" id="unit-buy-display">—</div>
                    </div>
                    <div class="price-summary__item">
                        <div class="price-summary__label">Suggested sell</div>
                        <div class="price-summary__value" id="suggested-sell-display">—</div>
                    </div>
                    <div class="price-summary__item">
                        <div class="price-summary__label">Available</div>
                        <div class="price-summary__value" id="available-display">—</div>
                    </div>
                </div>

                <div>
                    <label for="quantity_sold" class="admin-prod-label">4. Quantity <span class="text-red-500">*</span></label>
                    <input id="quantity_sold" type="number" name="quantity_sold" value="{{ old('quantity_sold', 1) }}" required min="1" class="admin-prod-input" disabled>
                    @error('quantity_sold')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                    <p class="helper-text" id="qty-hint">Choose a model to see how many devices are available.</p>
                </div>

                <div>
                    <label for="selling_price" class="admin-prod-label">5. Selling price per unit (TZS) <span class="text-red-500">*</span></label>
                    <input id="selling_price" type="number" step="0.01" name="selling_price" value="{{ old('selling_price') }}" required min="0" class="admin-prod-input" disabled>
                    @error('selling_price')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                    <p class="helper-text">Prefilled from the purchase sell price — you can adjust if needed.</p>
                </div>

                <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                    <div class="flex justify-between items-center">
                        <span class="font-semibold text-slate-900">Total amount</span>
                        <span id="total-amount-display" class="text-2xl font-bold text-slate-900">0.00 TZS</span>
                    </div>
                    <input type="hidden" id="total-amount" name="total_amount" value="0">
                    <p class="text-xs text-slate-600 mt-2">Quantity × selling price per unit</p>
                </div>

                <div class="admin-prod-form-footer !mt-0 !pt-0 !border-0 !shadow-none">
                    <a href="{{ route('admin.stock.agent-sales') }}" class="admin-prod-btn-ghost">Cancel</a>
                    <button type="submit" class="admin-prod-btn-primary px-8 opacity-50 cursor-not-allowed" id="submit-btn" disabled>Record sale</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            const PURCHASE_MODELS_URL = @json(route('admin.stock.agent-sale-purchase-models', ['purchase' => '__ID__']));
            const PRODUCT_META = {};
            const oldProductId = @json(old('product_id'));
            const oldPurchaseId = @json(old('purchase_id'));

            const purchaseSelect = document.getElementById('purchase_id');
            const productSelect = document.getElementById('product_id');
            const quantityInput = document.getElementById('quantity_sold');
            const priceInput = document.getElementById('selling_price');
            const totalDisplay = document.getElementById('total-amount-display');
            const totalHidden = document.getElementById('total-amount');
            const submitBtn = document.getElementById('submit-btn');
            const productHint = document.getElementById('product-hint');
            const qtyHint = document.getElementById('qty-hint');
            const priceSummary = document.getElementById('price-summary');

            function formatCurrency(value) {
                return new Intl.NumberFormat('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }).format(value) + ' TZS';
            }

            function selectedMeta() {
                const id = productSelect.value;
                return id ? PRODUCT_META[id] : null;
            }

            function updateProductSummary() {
                const meta = selectedMeta();
                if (!meta) {
                    priceSummary.classList.add('hidden');
                    quantityInput.disabled = true;
                    priceInput.disabled = true;
                    qtyHint.textContent = 'Choose a model to see how many devices are available.';
                    return;
                }

                priceSummary.classList.remove('hidden');
                document.getElementById('unit-buy-display').textContent = formatCurrency(meta.unit_price || 0);
                document.getElementById('suggested-sell-display').textContent = formatCurrency(meta.sell_price || 0);
                document.getElementById('available-display').textContent = String(meta.available_units || 0) + ' device' + ((meta.available_units || 0) === 1 ? '' : 's');

                quantityInput.disabled = meta.available_units <= 0;
                priceInput.disabled = meta.available_units <= 0;
                quantityInput.max = meta.available_units > 0 ? String(meta.available_units) : '';
                qtyHint.textContent = meta.available_units > 0
                    ? 'Up to ' + meta.available_units + ' device(s) available on this purchase.'
                    : 'No warehouse stock left for this model on the selected purchase.';

                if (meta.available_units > 0 && (!priceInput.value || parseFloat(priceInput.value) <= 0)) {
                    priceInput.value = meta.sell_price || meta.unit_price || '';
                }
            }

            function calculateTotal() {
                const quantity = parseFloat(quantityInput.value) || 0;
                const price = parseFloat(priceInput.value) || 0;
                const total = quantity * price;
                totalDisplay.textContent = formatCurrency(total);
                totalHidden.value = total;
                validateSubmit();
            }

            function validateSubmit() {
                const meta = selectedMeta();
                const quantity = parseInt(quantityInput.value, 10) || 0;
                const price = parseFloat(priceInput.value) || 0;
                const customerName = document.getElementById('customer_name').value.trim();
                const purchaseOk = purchaseSelect.value !== '';
                const productOk = productSelect.value !== '';
                const qtyOk = meta && quantity > 0 && quantity <= (meta.available_units || 0);
                const ok = customerName && purchaseOk && productOk && qtyOk && price > 0;

                submitBtn.disabled = !ok;
                submitBtn.classList.toggle('opacity-50', !ok);
                submitBtn.classList.toggle('cursor-not-allowed', !ok);
            }

            function resetProductSelect(message) {
                if (window.jQuery && jQuery.fn.select2 && jQuery(productSelect).data('select2')) {
                    jQuery(productSelect).select2('destroy');
                }
                productSelect.innerHTML = '';
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = message || 'Select a purchase first';
                productSelect.appendChild(opt);
                productSelect.disabled = true;
                Object.keys(PRODUCT_META).forEach(function (k) { delete PRODUCT_META[k]; });
                updateProductSummary();
                calculateTotal();
            }

            function initProductSelect2() {
                if (!window.jQuery || !jQuery.fn.select2) return;
                jQuery(productSelect).select2({
                    placeholder: 'Select model on this purchase',
                    width: '100%',
                    allowClear: false,
                });
                jQuery(productSelect).off('change').on('change', function () {
                    updateProductSummary();
                    calculateTotal();
                });
            }

            function loadModelsForPurchase(purchaseId, preselectProductId) {
                if (!purchaseId) {
                    resetProductSelect();
                    productHint.textContent = 'Select a purchase to load models with available quantity.';
                    return;
                }

                resetProductSelect('Loading models…');
                productHint.textContent = 'Loading models for this purchase…';

                fetch(PURCHASE_MODELS_URL.replace('__ID__', encodeURIComponent(purchaseId)), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        const rows = (json && json.data) ? json.data : [];
                        resetProductSelect(rows.length ? 'Select model' : 'No models on this purchase');

                        rows.forEach(function (row) {
                            const id = String(row.product_id);
                            PRODUCT_META[id] = row;
                            const option = document.createElement('option');
                            option.value = id;
                            option.textContent = row.picker_label || row.label;
                            if (row.available_units <= 0) {
                                option.disabled = true;
                            }
                            productSelect.appendChild(option);
                        });

                        productSelect.disabled = rows.length === 0;
                        initProductSelect2();

                        const withStock = rows.filter(function (r) { return (r.available_units || 0) > 0; }).length;
                        productHint.textContent = rows.length
                            ? rows.length + ' model(s) on this purchase — ' + withStock + ' with stock available.'
                            : 'No models on this purchase. Register IMEIs on the purchase first.';

                        if (preselectProductId && PRODUCT_META[String(preselectProductId)]) {
                            productSelect.value = String(preselectProductId);
                            if (window.jQuery) {
                                jQuery(productSelect).val(String(preselectProductId)).trigger('change.select2');
                            }
                        }

                        updateProductSummary();
                        calculateTotal();
                    })
                    .catch(function () {
                        resetProductSelect('Could not load models');
                        productHint.textContent = 'Could not load models for this purchase.';
                    });
            }

            purchaseSelect.addEventListener('change', function () {
                loadModelsForPurchase(purchaseSelect.value, null);
            });

            quantityInput.addEventListener('input', calculateTotal);
            priceInput.addEventListener('input', calculateTotal);
            document.getElementById('customer_name').addEventListener('input', validateSubmit);

            document.getElementById('sale-form').addEventListener('submit', function (e) {
                const meta = selectedMeta();
                const quantity = parseInt(quantityInput.value, 10) || 0;
                if (!meta || quantity <= 0 || quantity > (meta.available_units || 0)) {
                    e.preventDefault();
                    alert('Quantity exceeds available stock on this purchase.');
                    return false;
                }
                const customerName = document.getElementById('customer_name').value.trim();
                const price = parseFloat(priceInput.value) || 0;
                if (!confirm('Record agent sale?\n\nCustomer: ' + customerName + '\nQuantity: ' + quantity + '\nUnit price: ' + formatCurrency(price) + '\nTotal: ' + formatCurrency(quantity * price))) {
                    e.preventDefault();
                    return false;
                }
            });

            document.addEventListener('DOMContentLoaded', function () {
                if (window.jQuery && jQuery.fn.select2) {
                    jQuery('#purchase_id').select2({ placeholder: 'Select purchase', width: '100%', allowClear: false });
                    jQuery('#purchase_id').on('change', function () {
                        loadModelsForPurchase(purchaseSelect.value, null);
                    });
                }

                if (oldPurchaseId) {
                    loadModelsForPurchase(String(oldPurchaseId), oldProductId ? String(oldProductId) : null);
                } else {
                    resetProductSelect();
                }

                calculateTotal();
                validateSubmit();
            });
        </script>
    @endpush
</x-admin-layout>
