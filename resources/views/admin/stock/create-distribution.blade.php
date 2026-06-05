<x-admin-layout>
    @include('admin.partials.catalog-styles')

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <style>
            .field-error input, .field-error select, .field-error textarea {
                border-color: #ef4444 !important;
                background-color: #fee2e2;
            }
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
            .dist-line-table th {
                font-size: 0.7rem;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #64748b;
            }
            .dist-imei-modal-backdrop {
                position: fixed;
                inset: 0;
                z-index: 50;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 1rem;
                background: rgba(15, 23, 42, 0.45);
            }
            .dist-imei-modal-backdrop.is-open {
                display: flex;
            }
            .dist-imei-modal {
                width: 100%;
                max-width: 32rem;
                max-height: min(85vh, 560px);
                display: flex;
                flex-direction: column;
                background: #fff;
                border-radius: 0.75rem;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                overflow: hidden;
            }
            .dist-imei-modal__list {
                overflow-y: auto;
                flex: 1;
                min-height: 0;
                border-top: 1px solid #e2e8f0;
                border-bottom: 1px solid #e2e8f0;
            }
            .dist-imei-row {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.625rem 1rem;
                border-bottom: 1px solid #f1f5f9;
                cursor: pointer;
            }
            .dist-imei-row:hover {
                background: #f8fafc;
            }
            .dist-imei-row input[type="checkbox"] {
                accent-color: #f97316;
                width: 1rem;
                height: 1rem;
            }
            .dist-tab-group {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0.75rem;
            }
            .dist-tab-btn {
                display: block;
                width: 100%;
                text-align: left;
                padding: 0.875rem 1rem;
                border-radius: 0.625rem;
                border: 1.5px solid #e2e8f0;
                background: #fff;
                cursor: pointer;
                transition: border-color 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
            }
            .dist-tab-btn:hover {
                border-color: #fdba74;
            }
            .dist-tab-btn--active {
                border-color: #f97316;
                background: #fff7ed;
                box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12);
            }
            .dist-tab-btn__title {
                display: block;
                font-weight: 600;
                color: #0f172a;
                font-size: 0.9rem;
            }
            .dist-tab-btn__hint {
                display: block;
                margin-top: 0.25rem;
                font-size: 0.75rem;
                color: #64748b;
                font-weight: 400;
            }
        </style>
    @endpush

    <div class="admin-prod-page admin-prod-form-wide">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-8">
            <div>
                <p class="admin-prod-eyebrow">Dealers</p>
                <h1 class="admin-prod-title">Create distribution sale</h1>
                <p class="admin-prod-subtitle">Search a model, pick IMEIs in the modal, then set the unit selling price for each line.</p>
            </div>
            <a href="{{ route('admin.stock.distribution') }}" class="admin-prod-back shrink-0">Back to list</a>
        </div>

        <div class="admin-clay-panel admin-prod-form-shell overflow-hidden admin-prod-select2-wrap">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Sale details</h2>
            </div>
            <form method="POST" action="{{ route('admin.stock.store-distribution') }}" class="admin-prod-form-body space-y-6" id="dist-form">
                @csrf
                <input type="hidden" name="seller_name" value="{{ old('seller_name', auth()->user()->name) }}">

                <div>
                    <label for="date" class="admin-prod-label">Date <span class="text-red-500">*</span></label>
                    <input id="date" type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required autofocus max="{{ date('Y-m-d') }}" class="admin-prod-input">
                    @error('date')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="purchase_id" class="admin-prod-label">Purchase <span class="text-red-500">*</span></label>
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
                    <p class="helper-text">Only IMEIs linked to this purchase can be sold on this distribution.</p>
                    <p id="purchase-slots-hint" class="helper-text mt-1 hidden"></p>
                </div>

                <div>
                    <label for="dealer_id" class="admin-prod-label">Dealer <span class="text-red-500">*</span></label>
                    <select id="dealer_id" name="dealer_id" required class="admin-prod-select">
                        <option value="">Select dealer</option>
                        @foreach($dealers as $dealer)
                            <option value="{{ $dealer->id }}" @selected(old('dealer_id') == $dealer->id)>
                                {{ $dealer->name }}@if($dealer->business_name) — {{ $dealer->business_name }}@endif
                            </option>
                        @endforeach
                    </select>
                    @error('dealer_id')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div class="admin-clay-panel border border-slate-200/80 !shadow-none admin-prod-select2-wrap overflow-hidden" id="dist-products-tabs-wrap">
                    <div class="p-4 border-b border-slate-200/60 bg-slate-50/40">
                        <div class="dist-tab-group" role="tablist" aria-label="Distribution products">
                            <button type="button" class="dist-tab-btn dist-tab-btn--active" data-dist-tab="sale" role="tab" aria-selected="true" aria-controls="dist-tab-sale">
                                <span class="dist-tab-btn__title">Add to sale</span>
                                <span class="dist-tab-btn__hint">Pick models and IMEIs for this distribution.</span>
                            </button>
                            <button type="button" class="dist-tab-btn" data-dist-tab="register" role="tab" aria-selected="false" aria-controls="dist-tab-register">
                                <span class="dist-tab-btn__title">Register IMEIs</span>
                                <span class="dist-tab-btn__hint">Paste IMEIs onto the purchase first.</span>
                            </button>
                        </div>
                    </div>
                    <div id="dist-tab-sale" class="dist-tab-panel" role="tabpanel">
                        <div class="p-4 border-b border-slate-200/60">
                            <label for="product_picker" class="admin-prod-label !mb-2">Add model to this sale <span class="text-red-500">*</span></label>
                            <select id="product_picker" class="w-full" data-placeholder="Select a purchase first…" disabled>
                                <option value=""></option>
                            </select>
                            <p class="helper-text mt-2" id="product_picker_hint">Select a purchase above — only models on that purchase appear here. Choosing a model opens IMEIs registered on this purchase for that model.</p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm dist-line-table">
                                <thead class="bg-slate-50/90">
                                    <tr>
                                        <th scope="col" class="text-left px-4 py-3 font-semibold">Model / IMEIs</th>
                                        <th scope="col" class="text-right px-3 py-3 font-semibold w-[9rem]">Unit buy (TZS)</th>
                                        <th scope="col" class="text-right px-3 py-3 font-semibold w-[9rem]">Unit sell (TZS)</th>
                                        <th scope="col" class="text-right px-3 py-3 font-semibold w-[10rem]">Line total</th>
                                        <th scope="col" class="w-12 px-2 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody id="line-items-body"></tbody>
                            </table>
                            <p id="no-lines-hint" class="px-4 py-8 text-center text-slate-500 text-sm">No models added yet — use the search field above.</p>
                        </div>
                    </div>
                    <div id="dist-tab-register" class="dist-tab-panel hidden p-4 space-y-4" role="tabpanel">
                        <p id="dist-register-no-purchase" class="text-sm text-amber-800 bg-amber-50/80 border border-amber-200/70 rounded-lg px-3 py-2">Select a purchase above to register IMEIs.</p>
                        <div id="dist-register-body" class="space-y-4 hidden">
                            <p class="helper-text">After registering, open the <strong>Add to sale</strong> tab and pick the model to include IMEIs in this distribution.</p>
                            <p id="dist-register-no-slots" class="hidden text-sm text-slate-600 bg-slate-50 border border-slate-200/80 rounded-lg px-3 py-2">No open slots on this purchase — sell already registered IMEIs from the <strong>Add to sale</strong> tab.</p>
                            <p id="dist-register-brand-warning" class="hidden text-sm text-amber-800 bg-amber-50/80 border border-amber-200/70 rounded-lg px-3 py-2">This model needs a brand assigned in <strong>Management → Models</strong> before IMEIs can be registered.</p>
                            <div id="dist-register-form" class="space-y-4 hidden">
                        <div>
                            <label for="dist_register_model" class="admin-prod-label">Model</label>
                            <select id="dist_register_model" class="admin-prod-select w-full">
                                <option value="">Select purchase first</option>
                            </select>
                        </div>
                        <div>
                            <label for="dist_register_imei_numbers" class="admin-prod-label">IMEI / serial numbers</label>
                            <p class="text-xs text-slate-500 mb-1"><strong>One IMEI per line.</strong> Do not put multiple IMEIs on the same line (no commas or spaces between codes).</p>
                            <textarea id="dist_register_imei_numbers" rows="8" class="admin-prod-textarea font-mono text-sm" placeholder="352123456789012&#10;352123456789013&#10;352123456789014"></textarea>
                            <p id="dist_register_imei_count" class="helper-text mt-1"></p>
                        </div>
                        <div id="dist_register_feedback" class="hidden text-sm rounded-lg p-3" role="status"></div>
                        <div class="flex flex-wrap gap-2 items-center">
                            <button type="button" id="dist_register_submit" class="admin-prod-btn-primary text-sm py-2 px-5" disabled>Add to purchase</button>
                            <p id="dist_register_model_slots" class="text-xs text-slate-500"></p>
                        </div>
                            </div>
                        </div>
                    </div>
                </div>

                @error('lines')
                    <p class="text-red-600 text-xs font-semibold">{{ $message }}</p>
                @enderror

                <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                    <div class="flex justify-between items-center gap-4 flex-wrap">
                        <span class="font-semibold text-slate-900">Grand total (all lines)</span>
                        <span id="dist-total-display" class="text-2xl font-bold text-slate-900">0.00 TZS</span>
                    </div>
                    <input type="hidden" id="total-amount" name="total_amount_meta" value="0">
                    <p class="text-xs text-slate-600 mt-2">Sum of each line: selected IMEIs × unit sell price from the selected purchase.</p>
                </div>

                <div>
                    <label for="paid_amount" class="admin-prod-label">Paid amount <span class="text-slate-400 font-normal">(optional)</span></label>
                    <input id="paid_amount" type="text" name="paid_amount" value="{{ old('paid_amount') }}" inputmode="decimal" autocomplete="off" placeholder="Split proportionally across lines" class="admin-prod-input">
                    @error('paid_amount')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                    <p class="helper-text" id="payment-status">Optional — partial payments are split across lines by each line’s share of the total.</p>
                </div>

                <div class="admin-prod-form-footer !mt-0 !pt-0 !border-0 !shadow-none">
                    <a href="{{ route('admin.stock.distribution') }}" class="admin-prod-btn-ghost">Cancel</a>
                    <button type="submit" class="admin-prod-btn-primary px-8" id="submit-btn">Record sale</button>
                </div>
            </form>
        </div>
    </div>

    <div id="dist-imei-modal-backdrop" class="dist-imei-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="dist-imei-modal-title" hidden>
        <div class="dist-imei-modal">
            <div class="px-4 py-3 border-b border-slate-200">
                <h3 id="dist-imei-modal-title" class="font-semibold text-[#232f3e]">Select IMEIs</h3>
                <p id="dist-imei-modal-subtitle" class="text-xs text-slate-500 mt-0.5"></p>
            </div>
            <div class="px-4 py-2 flex flex-wrap gap-2 items-center border-b border-slate-100">
                <input type="search" id="dist-imei-filter" placeholder="Filter IMEI…" class="admin-prod-input flex-1 min-w-[10rem] text-sm py-1.5">
                <button type="button" id="dist-imei-select-all" class="text-xs font-semibold text-[#fa8900] hover:underline">Select all</button>
                <button type="button" id="dist-imei-clear-all" class="text-xs font-semibold text-slate-600 hover:underline">Clear</button>
            </div>
            <div id="dist-imei-list" class="dist-imei-modal__list"></div>
            <p id="dist-imei-empty" class="hidden px-4 py-6 text-sm text-center text-slate-500">No available IMEIs for this model on the selected purchase. Register them in the <strong>Register IMEIs</strong> tab or pick another model.</p>
            <div class="px-4 py-3 flex justify-end gap-2 border-t border-slate-200 bg-slate-50/80">
                <button type="button" id="dist-imei-cancel" class="admin-prod-btn-ghost text-sm py-2">Cancel</button>
                <button type="button" id="dist-imei-confirm" class="admin-prod-btn-primary text-sm py-2 px-5">Add to sale</button>
            </div>
        </div>
    </div>


    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            const PRODUCT_META = {};
            const ASSIGNABLE_IMEIS_URL = @json(route('admin.stock.distribution-assignable-imeis'));
            const PURCHASE_MODELS_URL_TEMPLATE = @json(route('admin.stock.distribution-purchase-models', ['purchase' => '__ID__']));
            const PURCHASE_MODELS_FOR_REGISTER_URL_TEMPLATE = @json(route('admin.stock.add-product.purchase.models', ['purchase' => '__ID__']));
            const REGISTER_IMEIS_URL = @json(route('admin.stock.distribution-register-imeis'));
            const PURCHASE_REGISTER_META = @json($purchaseRegisterMeta ?? []);
            const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            let purchaseRegistrationModels = [];
            let selectedModelLimitRemaining = 0;

            const tbody = document.getElementById('line-items-body');
            const noLinesHint = document.getElementById('no-lines-hint');
            const totalDisplay = document.getElementById('dist-total-display');
            const totalHidden = document.getElementById('total-amount');
            const paidInput = document.getElementById('paid_amount');
            const paymentStatus = document.getElementById('payment-status');
            const form = document.getElementById('dist-form');
            const submitBtn = document.getElementById('submit-btn');

            const modalBackdrop = document.getElementById('dist-imei-modal-backdrop');
            const modalSubtitle = document.getElementById('dist-imei-modal-subtitle');
            const modalList = document.getElementById('dist-imei-list');
            const modalEmpty = document.getElementById('dist-imei-empty');
            const modalFilter = document.getElementById('dist-imei-filter');
            const modalConfirm = document.getElementById('dist-imei-confirm');

            let modalProductId = null;
            let modalEditingRow = null;
            let modalImeis = [];

            function parseMoney(el) {
                if (!el || el.value === undefined || el.value === '') return 0;
                return parseFloat(String(el.value).replace(/,/g, '').trim()) || 0;
            }

            function formatCurrency(value) {
                return new Intl.NumberFormat('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }).format(value);
            }

            function escapeHtml(s) {
                const d = document.createElement('div');
                d.textContent = s;
                return d.innerHTML;
            }

            function lineQty(tr) {
                return tr.querySelectorAll('.line-imei-id').length;
            }

            function lineTotalRow(tr) {
                const sell = parseFloat(tr.getAttribute('data-sell-price') || '0') || 0;
                return lineQty(tr) * sell;
            }

            function selectedProductIds(excludeRow) {
                return [...tbody.querySelectorAll('tr[data-line-row]')]
                    .filter(tr => tr !== excludeRow)
                    .map(tr => tr.getAttribute('data-product-id'));
            }

            function usedImeiIds(excludeRow) {
                const ids = new Set();
                tbody.querySelectorAll('tr[data-line-row]').forEach(tr => {
                    if (tr === excludeRow) return;
                    tr.querySelectorAll('.line-imei-id').forEach(inp => ids.add(inp.value));
                });
                return ids;
            }

            function renumberLines() {
                const rows = tbody.querySelectorAll('tr[data-line-row]');
                rows.forEach((tr, idx) => {
                    tr.querySelector('.line-product-id').name = 'lines[' + idx + '][product_id]';
                    tr.querySelectorAll('.line-imei-id').forEach(inp => {
                        inp.name = 'lines[' + idx + '][product_list_ids][]';
                    });
                    const countEl = tr.querySelector('.line-imei-count');
                    const n = lineQty(tr);
                    if (countEl) countEl.textContent = n + ' device' + (n === 1 ? '' : 's');
                });
                noLinesHint.style.display = rows.length ? 'none' : 'block';
            }

            function recalcGrandTotal() {
                let sum = 0;
                let deviceCount = 0;
                tbody.querySelectorAll('tr[data-line-row]').forEach(tr => {
                    const lt = lineTotalRow(tr);
                    sum += lt;
                    deviceCount += lineQty(tr);
                    const cell = tr.querySelector('.line-line-total');
                    if (cell) cell.textContent = formatCurrency(lt) + ' TZS';
                });
                totalDisplay.textContent = formatCurrency(sum) + ' TZS';
                totalHidden.value = sum;

                const paid = parseMoney(paidInput);
                if (sum <= 0) {
                    paymentStatus.textContent = 'Optional — partial payments are split across lines by each line’s share of the total.';
                    paymentStatus.style.color = '#64748b';
                } else if (paid <= 0) {
                    paymentStatus.textContent = 'No upfront payment — record later from Edit sale.';
                    paymentStatus.style.color = '#64748b';
                } else if (Math.abs(paid - sum) < 0.01) {
                    paymentStatus.textContent = '✓ Matches grand total (split across ' + tbody.querySelectorAll('tr[data-line-row]').length + ' line(s))';
                    paymentStatus.style.color = '#10b981';
                } else if (paid > sum * 1.01) {
                    paymentStatus.textContent = '⚠️ Paid exceeds grand total';
                    paymentStatus.style.color = '#ef4444';
                } else {
                    paymentStatus.textContent = 'Partial payment • Remaining ' + formatCurrency(sum - paid) + ' TZS (allocated proportionally per line)';
                    paymentStatus.style.color = '#f59e0b';
                }

                const purchaseOk = document.getElementById('purchase_id').value !== '';
                submitBtn.disabled = sum <= 0 || deviceCount === 0 || document.getElementById('dealer_id').value === '' || !purchaseOk;
                submitBtn.classList.toggle('opacity-50', submitBtn.disabled);
                submitBtn.classList.toggle('cursor-not-allowed', submitBtn.disabled);
            }

            function buildImeiInputsContainer(imeis) {
                const wrap = document.createElement('div');
                wrap.className = 'line-imei-inputs hidden';
                imeis.forEach(item => {
                    const inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.className = 'line-imei-id';
                    inp.value = String(item.id);
                    inp.dataset.label = item.text || item.imei_number || '';
                    wrap.appendChild(inp);
                });
                return wrap;
            }

            function addLine(productId, imeis, existingRow) {
                const idStr = String(productId);
                if (!imeis || !imeis.length) return;

                const meta = PRODUCT_META[idStr];
                if (!meta) return;

                if (!existingRow && selectedProductIds(null).includes(idStr)) {
                    alert('This model is already on the sale. Use “Change IMEIs” on that row, or remove it first.');
                    return;
                }

                const sellPrice = meta.sell_price || meta.suggest || 0;
                const buyPrice = meta.unit_price || meta.buy_price || 0;
                let tr = existingRow;

                if (!tr) {
                    const idx = tbody.querySelectorAll('tr[data-line-row]').length;
                    tr = document.createElement('tr');
                    tr.className = 'border-b border-slate-100 hover:bg-slate-50/50';
                    tr.setAttribute('data-line-row', '1');
                    tr.setAttribute('data-product-id', idStr);
                    tr.setAttribute('data-sell-price', String(sellPrice));
                    tr.setAttribute('data-buy-price', String(buyPrice));

                    tr.innerHTML =
                        '<td class="px-4 py-3 align-top">' +
                            '<div class="font-medium text-[#232f3e]">' + escapeHtml(meta.label) + '</div>' +
                            '<div class="text-xs text-slate-500 mt-0.5"><span class="line-imei-count">' + imeis.length + ' device' + (imeis.length === 1 ? '' : 's') + '</span> · ' +
                            '<button type="button" class="text-[#fa8900] font-semibold hover:underline change-imeis">Change IMEIs</button></div>' +
                            '<input type="hidden" class="line-product-id" name="lines[' + idx + '][product_id]" value="' + idStr + '">' +
                        '</td>' +
                        '<td class="px-3 py-3 align-top text-right font-variant-numeric text-slate-700 line-buy-cell">' + formatCurrency(buyPrice) + '</td>' +
                        '<td class="px-3 py-3 align-top text-right font-variant-numeric text-slate-700 line-sell-cell">' + formatCurrency(sellPrice) + '</td>' +
                        '<td class="px-3 py-3 align-top text-right font-variant-numeric font-semibold text-[#232f3e] line-line-total">0.00 TZS</td>' +
                        '<td class="px-2 py-3 align-top">' +
                            '<button type="button" class="text-red-600 hover:text-red-800 text-sm font-semibold remove-line">Remove</button>' +
                        '</td>';

                    const firstCell = tr.querySelector('td');
                    firstCell.appendChild(buildImeiInputsContainer(imeis));

                    tbody.appendChild(tr);

                    tr.querySelector('.remove-line').addEventListener('click', function () {
                        tr.remove();
                        renumberLines();
                        recalcGrandTotal();
                    });
                    tr.querySelector('.change-imeis').addEventListener('click', function () {
                        openImeiModal(idStr, tr);
                    });
                } else {
                    const oldWrap = tr.querySelector('.line-imei-inputs');
                    if (oldWrap) oldWrap.remove();
                    tr.querySelector('td').appendChild(buildImeiInputsContainer(imeis));
                }

                renumberLines();
                recalcGrandTotal();
            }

            function renderModalList() {
                const filter = (modalFilter.value || '').trim().toLowerCase();
                const used = usedImeiIds(modalEditingRow);
                const visible = modalImeis.filter(row => {
                    if (used.has(String(row.id))) return false;
                    if (!filter) return true;
                    const t = (row.text || row.imei_number || '').toLowerCase();
                    return t.includes(filter);
                });

                modalList.innerHTML = '';
                modalEmpty.classList.toggle('hidden', visible.length > 0);
                modalList.classList.toggle('hidden', visible.length === 0);

                visible.forEach(row => {
                    const label = document.createElement('label');
                    label.className = 'dist-imei-row';
                    label.innerHTML =
                        '<input type="checkbox" class="dist-imei-cb" value="' + escapeHtml(String(row.id)) + '">' +
                        '<span class="text-sm text-[#232f3e] font-mono">' + escapeHtml(row.text || row.imei_number) + '</span>';
                    modalList.appendChild(label);
                });
            }

            function getPurchaseId() {
                return document.getElementById('purchase_id').value;
            }

            function initProductPickerSelect2(purchaseId) {
                const $pick = window.jQuery ? jQuery('#product_picker') : null;
                if (!$pick || !$pick.length || !window.jQuery || !jQuery.fn.select2) return;

                if ($pick.data('select2')) {
                    $pick.select2('destroy');
                }
                $pick.select2({
                    placeholder: purchaseId ? 'Search model on this purchase…' : 'Select a purchase first…',
                    width: '100%',
                    allowClear: true
                });
                $pick.off('select2:select').on('select2:select', function (e) {
                    const id = e.params.data.id;
                    if (id) {
                        openImeiModal(id, null);
                    }
                });
            }

            function syncProductPickerForPurchase() {
                const purchaseId = getPurchaseId();
                const $pick = window.jQuery ? jQuery('#product_picker') : null;
                const hint = document.getElementById('product_picker_hint');
                if (!$pick || !$pick.length) return;

                if ($pick.data('select2')) {
                    $pick.select2('destroy');
                }
                $pick.empty().append(new Option('', '', false, false));

                if (!purchaseId) {
                    $pick.prop('disabled', true);
                    if (hint) {
                        hint.textContent = 'Select a purchase above — only models on that purchase appear here.';
                    }
                    initProductPickerSelect2(null);
                    return;
                }

                $pick.prop('disabled', true);
                if (hint) {
                    hint.textContent = 'Loading models for this purchase…';
                }

                const url = PURCHASE_MODELS_URL_TEMPLATE.replace('__ID__', encodeURIComponent(purchaseId));
                fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        const rows = (json && json.data) ? json.data : [];
                        rows.forEach(function (row) {
                            const id = String(row.product_id);
                            PRODUCT_META[id] = {
                                id: row.product_id,
                                label: row.label,
                                unit_price: row.unit_price || 0,
                                sell_price: row.sell_price || row.suggest || 0,
                                suggest: row.sell_price || row.suggest || 0,
                                available_imeis: row.available_imeis || 0,
                            };
                            const opt = new Option(row.picker_label || row.label, id, false, false);
                            jQuery(opt).attr('data-suggest', row.suggest || 0);
                            if ((row.available_imeis || 0) < 1) {
                                opt.disabled = true;
                            }
                            $pick.append(opt);
                        });
                        $pick.prop('disabled', false);
                        if (hint) {
                            if (!rows.length) {
                                hint.textContent = 'No models on this purchase. Add models via the purchase or register IMEIs in the Register IMEIs tab.';
                            } else {
                                const withImeis = rows.filter(function (r) { return (r.available_imeis || 0) > 0; }).length;
                                hint.textContent = rows.length + ' model(s) on this purchase'
                                    + (withImeis < rows.length ? ' (' + withImeis + ' with IMEIs ready to sell)' : '')
                                    + '. Pick a model to choose its IMEIs on this purchase.';
                            }
                        }
                        initProductPickerSelect2(purchaseId);
                    })
                    .catch(function () {
                        $pick.prop('disabled', false);
                        if (hint) {
                            hint.textContent = 'Could not load models for this purchase.';
                        }
                        initProductPickerSelect2(purchaseId);
                    });
            }

            function clearAllLines() {
                tbody.querySelectorAll('tr[data-line-row]').forEach(function (tr) { tr.remove(); });
                renumberLines();
                recalcGrandTotal();
            }

            function openImeiModal(productId, editingRow) {
                const meta = PRODUCT_META[String(productId)];
                if (!meta) return;

                const purchaseId = getPurchaseId();
                if (!purchaseId) {
                    alert('Select a purchase first.');
                    return;
                }

                modalProductId = String(productId);
                modalEditingRow = editingRow || null;
                modalSubtitle.textContent = meta.label;
                modalFilter.value = '';
                modalList.innerHTML = '<p class="px-4 py-6 text-sm text-slate-500 text-center">Loading IMEIs…</p>';
                modalEmpty.classList.add('hidden');
                modalBackdrop.hidden = false;
                modalBackdrop.classList.add('is-open');

                fetch(ASSIGNABLE_IMEIS_URL + '?product_id=' + encodeURIComponent(productId) + '&purchase_id=' + encodeURIComponent(purchaseId), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                })
                    .then(r => r.json())
                    .then(json => {
                        modalImeis = (json && json.data) ? json.data : [];
                        if (!modalImeis.length) {
                            modalList.innerHTML = '';
                            modalList.classList.add('hidden');
                            modalEmpty.classList.remove('hidden');
                            modalEmpty.textContent = 'No unsold IMEIs for this model on the selected purchase. Register IMEIs on this purchase first, or choose another model.';
                            return;
                        }
                        modalEmpty.textContent = 'No available IMEIs for this model on the selected purchase. Register them in the Register IMEIs tab or pick another model.';
                        renderModalList();
                        if (modalEditingRow) {
                            const selected = new Set(
                                [...modalEditingRow.querySelectorAll('.line-imei-id')].map(i => i.value)
                            );
                            modalList.querySelectorAll('.dist-imei-cb').forEach(cb => {
                                if (selected.has(cb.value)) cb.checked = true;
                            });
                        }
                    })
                    .catch(() => {
                        modalList.innerHTML = '<p class="px-4 py-6 text-sm text-red-600 text-center">Could not load IMEIs.</p>';
                    });
            }

            function closeImeiModal() {
                modalBackdrop.classList.remove('is-open');
                modalBackdrop.hidden = true;
                modalProductId = null;
                modalEditingRow = null;
                modalImeis = [];
            }

            document.getElementById('dist-imei-cancel').addEventListener('click', closeImeiModal);
            modalBackdrop.addEventListener('click', function (e) {
                if (e.target === modalBackdrop) closeImeiModal();
            });
            modalFilter.addEventListener('input', renderModalList);
            document.getElementById('dist-imei-select-all').addEventListener('click', function () {
                modalList.querySelectorAll('.dist-imei-cb').forEach(cb => { cb.checked = true; });
            });
            document.getElementById('dist-imei-clear-all').addEventListener('click', function () {
                modalList.querySelectorAll('.dist-imei-cb').forEach(cb => { cb.checked = false; });
            });
            modalConfirm.addEventListener('click', function () {
                const picked = [...modalList.querySelectorAll('.dist-imei-cb:checked')].map(cb => {
                    const row = modalImeis.find(r => String(r.id) === cb.value);
                    return row || { id: cb.value, text: cb.value };
                });
                if (!picked.length) {
                    alert('Select at least one IMEI.');
                    return;
                }
                addLine(modalProductId, picked, modalEditingRow);
                closeImeiModal();
                if (window.jQuery) jQuery('#product_picker').val(null).trigger('change');
            });

            const registerNoPurchase = document.getElementById('dist-register-no-purchase');
            const registerBody = document.getElementById('dist-register-body');
            const registerForm = document.getElementById('dist-register-form');
            const registerNoSlots = document.getElementById('dist-register-no-slots');
            const registerBrandWarning = document.getElementById('dist-register-brand-warning');
            const purchaseSlotsHint = document.getElementById('purchase-slots-hint');

            function switchDistTab(tabId) {
                document.querySelectorAll('.dist-tab-btn[data-dist-tab]').forEach(function (btn) {
                    const active = btn.getAttribute('data-dist-tab') === tabId;
                    btn.classList.toggle('dist-tab-btn--active', active);
                    btn.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                document.getElementById('dist-tab-sale').classList.toggle('hidden', tabId !== 'sale');
                document.getElementById('dist-tab-register').classList.toggle('hidden', tabId !== 'register');
            }

            function setRegisterTabState(state, options) {
                options = options || {};
                const showForm = state === 'ready';
                registerNoPurchase.classList.toggle('hidden', state !== 'no-purchase');
                registerBody.classList.toggle('hidden', state === 'no-purchase');
                registerNoSlots.classList.toggle('hidden', showForm || state === 'no-purchase');
                registerBrandWarning.classList.toggle('hidden', !options.needsBrand || !showForm);
                registerForm.classList.toggle('hidden', !showForm);
            }

            document.querySelectorAll('.dist-tab-btn[data-dist-tab]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    switchDistTab(btn.getAttribute('data-dist-tab'));
                });
            });
            const registerModelSelect = document.getElementById('dist_register_model');
            const registerImeiTa = document.getElementById('dist_register_imei_numbers');
            const registerImeiCount = document.getElementById('dist_register_imei_count');
            const registerModelSlots = document.getElementById('dist_register_model_slots');
            const registerFeedback = document.getElementById('dist_register_feedback');
            const registerSubmitBtn = document.getElementById('dist_register_submit');

            function countParsedImeis(text) {
                if (!text || !text.trim()) return 0;
                return text.replace(/\r\n/g, '\n').split('\n').map(function (s) { return s.trim(); }).filter(Boolean).length;
            }

            function updateRegisterImeiCount() {
                const n = countParsedImeis(registerImeiTa.value);
                const over = selectedModelLimitRemaining > 0 && n > selectedModelLimitRemaining;
                registerImeiCount.textContent = n > 0
                    ? (n + ' code(s) entered' + (selectedModelLimitRemaining > 0 ? ' · max ' + selectedModelLimitRemaining + ' for this model' : '') + (over ? ' — too many' : ''))
                    : '';
                registerImeiCount.classList.toggle('text-red-600', over);
                updateRegisterSubmitState();
            }

            function updateRegisterSubmitState() {
                const purchaseId = getPurchaseId();
                const modelId = registerModelSelect.value;
                const n = countParsedImeis(registerImeiTa.value);
                const ok = purchaseId && modelId && n > 0 && (selectedModelLimitRemaining <= 0 || n <= selectedModelLimitRemaining);
                registerSubmitBtn.disabled = !ok;
            }

            function showRegisterFeedback(message, isError) {
                registerFeedback.textContent = message;
                registerFeedback.classList.remove('hidden', 'bg-green-50', 'text-green-800', 'bg-red-50', 'text-red-700');
                registerFeedback.classList.add(isError ? 'bg-red-50' : 'bg-green-50', isError ? 'text-red-700' : 'text-green-800');
            }

            function hideRegisterFeedback() {
                registerFeedback.classList.add('hidden');
                registerFeedback.textContent = '';
            }

            function populateRegisterModelSelect(models, autoSelectSingle) {
                const $sel = window.jQuery ? jQuery('#dist_register_model') : null;
                if ($sel && $sel.data('select2')) {
                    $sel.select2('destroy');
                }
                registerModelSelect.innerHTML = '';
                if (!models.length) {
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = 'No open slots on this purchase';
                    registerModelSelect.appendChild(opt);
                    selectedModelLimitRemaining = 0;
                } else {
                    const includePlaceholder = models.length > 1;
                    if (includePlaceholder) {
                        const empty = document.createElement('option');
                        empty.value = '';
                        empty.textContent = 'Select model';
                        registerModelSelect.appendChild(empty);
                    }
                    models.forEach(function (m) {
                        const opt = document.createElement('option');
                        opt.value = String(m.product_id);
                        opt.textContent = m.label || ('Model #' + m.product_id);
                        opt.dataset.limitRemaining = String(m.limit_remaining || 0);
                        registerModelSelect.appendChild(opt);
                    });
                    if (autoSelectSingle && models.length === 1) {
                        registerModelSelect.value = String(models[0].product_id);
                    }
                }
                if ($sel && window.jQuery && jQuery.fn.select2) {
                    $sel.select2({ placeholder: 'Select model', width: '100%', allowClear: false });
                    if (autoSelectSingle && models.length === 1) {
                        $sel.val(String(models[0].product_id)).trigger('change.select2');
                    }
                }
                updateRegisterModelSlotsLabel();
                updateRegisterSubmitState();
            }

            function applyPurchaseRegistrationRows(rows) {
                const openSlotRows = rows.filter(function (m) {
                    return (m.limit_remaining || 0) > 0;
                });
                purchaseRegistrationModels = openSlotRows.map(function (m) {
                    return {
                        product_id: m.product_id,
                        limit_remaining: m.limit_remaining,
                        label: m.label || m.model,
                        can_register: m.can_register !== false,
                    };
                });
                const totalSlots = openSlotRows.reduce(function (sum, m) {
                    return sum + (parseInt(m.limit_remaining, 10) || 0);
                }, 0);
                const needsBrand = openSlotRows.some(function (m) {
                    return m.can_register === false;
                });
                const registerableModels = purchaseRegistrationModels.filter(function (m) {
                    return m.can_register !== false;
                });

                if (openSlotRows.length > 0) {
                    purchaseSlotsHint.textContent = needsBrand
                        ? (totalSlots + ' open slot(s), but assign a brand to the model in Management → Models before registering IMEIs.')
                        : (totalSlots + ' open slot(s) on this purchase — use the Register IMEIs tab to add devices.');
                    setRegisterTabState('ready', { needsBrand: needsBrand });
                    populateRegisterModelSelect(registerableModels.length ? registerableModels : purchaseRegistrationModels, true);
                    return;
                }

                if (rows.length === 0) {
                    purchaseSlotsHint.textContent = 'No models found on this purchase.';
                } else {
                    purchaseSlotsHint.textContent = 'No open slots on this purchase — sell IMEIs already registered from the Add to sale tab.';
                }
                setRegisterTabState('no-slots');
                populateRegisterModelSelect([]);
            }

            function updateRegisterModelSlotsLabel() {
                const opt = registerModelSelect.options[registerModelSelect.selectedIndex];
                selectedModelLimitRemaining = opt && opt.dataset.limitRemaining ? parseInt(opt.dataset.limitRemaining, 10) : 0;
                registerModelSlots.textContent = selectedModelLimitRemaining > 0
                    ? selectedModelLimitRemaining + ' slot(s) left for this model'
                    : (purchaseRegistrationModels.length ? 'No slots for selected model' : '');
                updateRegisterImeiCount();
            }

            function loadPurchaseRegistrationMeta() {
                const purchaseId = getPurchaseId();
                hideRegisterFeedback();
                purchaseRegistrationModels = [];
                selectedModelLimitRemaining = 0;

                if (!purchaseId) {
                    setRegisterTabState('no-purchase');
                    purchaseSlotsHint.classList.add('hidden');
                    populateRegisterModelSelect([]);
                    return;
                }

                registerBody.classList.remove('hidden');
                registerForm.classList.add('hidden');
                registerNoSlots.classList.add('hidden');
                registerBrandWarning.classList.add('hidden');
                purchaseSlotsHint.classList.remove('hidden');
                purchaseSlotsHint.textContent = 'Loading purchase slots…';

                const embeddedRows = PURCHASE_REGISTER_META[purchaseId] || PURCHASE_REGISTER_META[parseInt(purchaseId, 10)] || [];
                if (embeddedRows.length) {
                    applyPurchaseRegistrationRows(embeddedRows);
                }

                const url = PURCHASE_MODELS_FOR_REGISTER_URL_TEMPLATE.replace('__ID__', encodeURIComponent(purchaseId));
                fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        const rows = (json && json.data) ? json.data : embeddedRows;
                        applyPurchaseRegistrationRows(rows);
                    })
                    .catch(function () {
                        if (embeddedRows.length) {
                            applyPurchaseRegistrationRows(embeddedRows);
                            return;
                        }
                        purchaseSlotsHint.textContent = 'Could not load purchase slot info.';
                        setRegisterTabState('no-slots');
                        populateRegisterModelSelect([]);
                    });
            }

            registerModelSelect.addEventListener('change', updateRegisterModelSlotsLabel);
            registerImeiTa.addEventListener('input', updateRegisterImeiCount);

            registerSubmitBtn.addEventListener('click', function () {
                const purchaseId = getPurchaseId();
                const catalogProductId = registerModelSelect.value;
                const imeiNumbers = registerImeiTa.value;
                if (!purchaseId || !catalogProductId || !imeiNumbers.trim()) return;

                registerSubmitBtn.disabled = true;
                hideRegisterFeedback();

                fetch(REGISTER_IMEIS_URL, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        purchase_id: parseInt(purchaseId, 10),
                        catalog_product_id: parseInt(catalogProductId, 10),
                        imei_numbers: imeiNumbers,
                    }),
                })
                    .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }); })
                    .then(function (res) {
                        if (!res.ok || !res.json.ok) {
                            showRegisterFeedback(res.json.message || 'Could not add devices.', true);
                            return;
                        }
                        showRegisterFeedback(res.json.message || ('Added ' + res.json.created + ' device(s).'), false);
                        registerImeiTa.value = '';
                        updateRegisterImeiCount();
                        switchDistTab('sale');
                        if (res.json.models) {
                            applyPurchaseRegistrationRows(res.json.models);
                        } else {
                            loadPurchaseRegistrationMeta();
                        }
                        syncProductPickerForPurchase();
                    })
                    .catch(function () {
                        showRegisterFeedback('Request failed. Try again.', true);
                    })
                    .finally(function () {
                        updateRegisterSubmitState();
                    });
            });

            document.getElementById('dealer_id').addEventListener('change', recalcGrandTotal);
            function onPurchaseChanged() {
                clearAllLines();
                syncProductPickerForPurchase();
                loadPurchaseRegistrationMeta();
                recalcGrandTotal();
            }

            document.getElementById('purchase_id').addEventListener('change', onPurchaseChanged);
            if (window.jQuery) {
                jQuery('#purchase_id').on('change', onPurchaseChanged);
            }
            paidInput.addEventListener('input', recalcGrandTotal);

            form.addEventListener('submit', function (e) {
                if (!getPurchaseId()) {
                    e.preventDefault();
                    alert('Select a purchase for this distribution sale.');
                    return false;
                }
                const rows = tbody.querySelectorAll('tr[data-line-row]');
                if (rows.length === 0) {
                    e.preventDefault();
                    alert('Add at least one phone model to the sale.');
                    return false;
                }
                let missingImeis = false;
                rows.forEach(tr => {
                    if (lineQty(tr) < 1) missingImeis = true;
                });
                if (missingImeis) {
                    e.preventDefault();
                    alert('Each line must have at least one IMEI selected.');
                    return false;
                }
                const paid = parseMoney(paidInput);
                const sum = parseFloat(totalHidden.value) || 0;
                if (paid > sum * 1.01 + 0.01) {
                    e.preventDefault();
                    alert('Paid amount cannot exceed the grand total.');
                    return false;
                }
                const dealerName = document.getElementById('dealer_id').options[document.getElementById('dealer_id').selectedIndex].text;
                const devices = [...rows].reduce((n, tr) => n + lineQty(tr), 0);
                return confirm('Record distribution sale?\n\nDealer: ' + dealerName + '\nLines: ' + rows.length + '\nDevices: ' + devices + '\nGrand total: ' + formatCurrency(sum) + ' TZS');
            });

            document.addEventListener('DOMContentLoaded', function () {
                if (window.jQuery && jQuery.fn.select2) {
                    jQuery('#dealer_id').select2({
                        placeholder: 'Select dealer',
                        width: '100%',
                        allowClear: false
                    });
                    jQuery('#purchase_id').select2({
                        placeholder: 'Select purchase',
                        width: '100%',
                        allowClear: false
                    });
                    syncProductPickerForPurchase();
                    loadPurchaseRegistrationMeta();
                }
                recalcGrandTotal();
            });
        </script>
    @endpush
</x-admin-layout>
