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

        <div class="admin-clay-panel admin-prod-form-shell overflow-hidden">
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

                <div class="admin-clay-panel border border-slate-200/80 !shadow-none admin-prod-select2-wrap">
                    <div class="p-4 border-b border-slate-200/60">
                        <label for="product_picker" class="admin-prod-label !mb-2">Add model to this sale <span class="text-red-500">*</span></label>
                        <select id="product_picker" class="w-full" data-placeholder="Search category / model…">
                            <option value=""></option>
                            @foreach($products as $product)
                                <option
                                    value="{{ $product->id }}"
                                    data-stock="{{ (int) $product->stock_quantity }}"
                                    data-suggest="{{ (float) ($product->price ?? 0) }}"
                                >
                                    {{ $product->category?->name ?? '—' }} — {{ $product->name }} (stock {{ (int) $product->stock_quantity }})
                                </option>
                            @endforeach
                        </select>
                        <p class="helper-text mt-2">Choose a model — a window opens to select one or more IMEIs, then set unit price on the line.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm dist-line-table">
                            <thead class="bg-slate-50/90">
                                <tr>
                                    <th scope="col" class="text-left px-4 py-3 font-semibold">Model / IMEIs</th>
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

                @error('lines')
                    <p class="text-red-600 text-xs font-semibold">{{ $message }}</p>
                @enderror

                <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                    <div class="flex justify-between items-center gap-4 flex-wrap">
                        <span class="font-semibold text-slate-900">Grand total (all lines)</span>
                        <span id="dist-total-display" class="text-2xl font-bold text-slate-900">0.00 TZS</span>
                    </div>
                    <input type="hidden" id="total-amount" name="total_amount_meta" value="0">
                    <p class="text-xs text-slate-600 mt-2">Sum of each line: selected IMEIs × unit selling price.</p>
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
            <p id="dist-imei-empty" class="hidden px-4 py-6 text-sm text-center text-slate-500">No available IMEIs for this model.</p>
            <div class="px-4 py-3 flex justify-end gap-2 border-t border-slate-200 bg-slate-50/80">
                <button type="button" id="dist-imei-cancel" class="admin-prod-btn-ghost text-sm py-2">Cancel</button>
                <button type="button" id="dist-imei-confirm" class="admin-prod-btn-primary text-sm py-2 px-5">Add to sale</button>
            </div>
        </div>
    </div>

    @php
        $productMeta = $products->keyBy('id')->map(function ($p) {
            return [
                'id' => $p->id,
                'label' => ($p->category?->name ?? '—') . ' — ' . $p->name,
                'stock' => (int) ($p->stock_quantity ?? 0),
                'suggest' => (float) ($p->price ?? 0),
            ];
        })->toArray();
    @endphp

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            const PRODUCT_META = @json($productMeta);
            const ASSIGNABLE_IMEIS_URL = @json(route('admin.stock.distribution-assignable-imeis'));

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
                return lineQty(tr) * parseMoney(tr.querySelector('.line-price'));
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
                    tr.querySelector('.line-price').name = 'lines[' + idx + '][selling_price]';
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

                submitBtn.disabled = sum <= 0 || deviceCount === 0 || document.getElementById('dealer_id').value === '';
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

                const suggest = meta.suggest > 0 ? meta.suggest : '';
                let tr = existingRow;

                if (!tr) {
                    const idx = tbody.querySelectorAll('tr[data-line-row]').length;
                    tr = document.createElement('tr');
                    tr.className = 'border-b border-slate-100 hover:bg-slate-50/50';
                    tr.setAttribute('data-line-row', '1');
                    tr.setAttribute('data-product-id', idStr);

                    tr.innerHTML =
                        '<td class="px-4 py-3 align-top">' +
                            '<div class="font-medium text-[#232f3e]">' + escapeHtml(meta.label) + '</div>' +
                            '<div class="text-xs text-slate-500 mt-0.5"><span class="line-imei-count">' + imeis.length + ' device' + (imeis.length === 1 ? '' : 's') + '</span> · ' +
                            '<button type="button" class="text-[#fa8900] font-semibold hover:underline change-imeis">Change IMEIs</button></div>' +
                            '<input type="hidden" class="line-product-id" name="lines[' + idx + '][product_id]" value="' + idStr + '">' +
                        '</td>' +
                        '<td class="px-3 py-3 align-top text-right">' +
                            '<input type="text" required inputmode="decimal" placeholder="0" class="admin-prod-input text-right w-full max-w-[8rem] ml-auto line-price" name="lines[' + idx + '][selling_price]" value="' + (suggest !== '' ? suggest : '') + '">' +
                        '</td>' +
                        '<td class="px-3 py-3 align-top text-right font-variant-numeric font-semibold text-[#232f3e] line-line-total">0.00 TZS</td>' +
                        '<td class="px-2 py-3 align-top">' +
                            '<button type="button" class="text-red-600 hover:text-red-800 text-sm font-semibold remove-line">Remove</button>' +
                        '</td>';

                    const firstCell = tr.querySelector('td');
                    firstCell.appendChild(buildImeiInputsContainer(imeis));

                    tbody.appendChild(tr);

                    tr.querySelector('.line-price').addEventListener('input', recalcGrandTotal);
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

            function openImeiModal(productId, editingRow) {
                const meta = PRODUCT_META[String(productId)];
                if (!meta) return;

                modalProductId = String(productId);
                modalEditingRow = editingRow || null;
                modalSubtitle.textContent = meta.label;
                modalFilter.value = '';
                modalList.innerHTML = '<p class="px-4 py-6 text-sm text-slate-500 text-center">Loading IMEIs…</p>';
                modalEmpty.classList.add('hidden');
                modalBackdrop.hidden = false;
                modalBackdrop.classList.add('is-open');

                fetch(ASSIGNABLE_IMEIS_URL + '?product_id=' + encodeURIComponent(productId), {
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
                            return;
                        }
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

            document.getElementById('dealer_id').addEventListener('change', recalcGrandTotal);
            paidInput.addEventListener('input', recalcGrandTotal);

            form.addEventListener('submit', function (e) {
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
                    var $pick = jQuery('#product_picker');
                    $pick.select2({
                        placeholder: 'Search category / model…',
                        width: '100%',
                        allowClear: true
                    });
                    $pick.on('select2:select', function (e) {
                        const id = e.params.data.id;
                        if (id) {
                            openImeiModal(id, null);
                        }
                    });
                }
                recalcGrandTotal();
            });
        </script>
    @endpush
</x-admin-layout>
