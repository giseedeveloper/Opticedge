<x-admin-layout>
    @include('admin.partials.catalog-styles')

    @push('styles')
        <style>
            .ap-imei-tabs {
                display: flex;
                gap: 0.375rem;
                margin-bottom: 0.875rem;
                padding: 0.25rem;
                border-radius: 0.625rem;
                background: #f1f5f9;
                width: fit-content;
            }
            .ap-imei-tab {
                border: none;
                border-radius: 0.5rem;
                padding: 0.4375rem 0.875rem;
                font-size: 0.75rem;
                font-weight: 700;
                color: #64748b;
                background: transparent;
                cursor: pointer;
            }
            .ap-imei-tab--active {
                background: #fff;
                color: #ea580c;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
            }
            .ap-imei-tab-panel.hidden { display: none; }
            .ap-imei-list {
                max-height: min(28rem, 52vh);
                overflow-y: auto;
                border: 1px solid #e2e8f0;
                border-radius: 0.625rem;
                background: #fff;
            }
            .ap-imei-row {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.625rem 1rem;
                border-bottom: 1px solid #f1f5f9;
                cursor: pointer;
            }
            .ap-imei-row:last-child { border-bottom: none; }
            .ap-imei-row:hover { background: #f8fafc; }
            .ap-imei-row input[type="checkbox"] { accent-color: #f97316; width: 1rem; height: 1rem; }
            .ap-imei-row__serial {
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                font-size: 0.8125rem;
                font-weight: 600;
                color: #232f3e;
            }
            .ap-imei-empty { padding: 2rem 1rem; text-align: center; color: #94a3b8; font-size: 0.875rem; }
            .ap-scanner-layout {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr);
                gap: 1rem;
            }
            @media (max-width: 900px) { .ap-scanner-layout { grid-template-columns: 1fr; } }
            .ap-scanner-input-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                margin-bottom: 0.5rem;
            }
            .ap-scanner-input-head__label { font-size: 0.75rem; font-weight: 700; color: #334155; }
            .ap-scanner-input-head__count { font-size: 0.6875rem; font-weight: 700; color: #64748b; font-variant-numeric: tabular-nums; }
            .ap-scanner-input-head__count--warn { color: #dc2626; }
            .ap-imei-scanner-input {
                width: 100%;
                min-height: 11rem;
                max-height: 22rem;
                resize: vertical;
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                font-size: 0.8125rem;
                line-height: 1.45;
            }
            .ap-scanner-results-col {
                border: 1px solid #e2e8f0;
                border-radius: 0.75rem;
                background: #f8fafc;
                overflow: hidden;
                display: flex;
                flex-direction: column;
                min-height: 0;
            }
            .ap-scanner-stats {
                display: flex;
                flex-wrap: wrap;
                gap: 0.375rem;
                padding: 0.625rem 0.75rem;
                border-bottom: 1px solid #e2e8f0;
                background: #fff;
            }
            .ap-scanner-stats.hidden { display: none; }
            .ap-scanner-stat {
                padding: 0.2rem 0.55rem;
                border-radius: 9999px;
                font-size: 0.6875rem;
                font-weight: 700;
            }
            .ap-scanner-stat--total { background: #f1f5f9; color: #475569; }
            .ap-scanner-stat--valid { background: #dcfce7; color: #166534; }
            .ap-scanner-stat--invalid { background: #fee2e2; color: #b91c1c; }
            .ap-scanner-stat--selected { background: #ffedd5; color: #c2410c; }
            .ap-scanner-filters {
                display: flex;
                flex-wrap: wrap;
                gap: 0.375rem;
                padding: 0.5rem 0.75rem;
                border-bottom: 1px solid #e2e8f0;
                background: #fff;
            }
            .ap-scanner-filters.hidden { display: none; }
            .ap-scanner-filter {
                border: 1px solid #e2e8f0;
                border-radius: 9999px;
                padding: 0.2rem 0.625rem;
                font-size: 0.6875rem;
                font-weight: 700;
                color: #64748b;
                background: #fff;
                cursor: pointer;
            }
            .ap-scanner-filter--active {
                border-color: #fdba74;
                background: #fff7ed;
                color: #c2410c;
            }
            .ap-imei-scanner-results {
                flex: 1;
                min-height: 16rem;
                max-height: min(28rem, 52vh);
                overflow-y: auto;
                background: #fff;
            }
            .ap-imei-scanner-line {
                display: grid;
                grid-template-columns: 2.25rem minmax(0, 1fr) auto;
                align-items: center;
                gap: 0.5rem 0.75rem;
                padding: 0.375rem 0.75rem;
                border-bottom: 1px solid #f1f5f9;
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                font-size: 0.75rem;
            }
            .ap-imei-scanner-line--valid { background: #f0fdf4; color: #166534; }
            .ap-imei-scanner-line--invalid { background: #fef2f2; color: #b91c1c; }
            .ap-imei-scanner-line--skipped { background: #fffbeb; color: #b45309; }
            .ap-imei-scanner-line__status {
                font-size: 0.625rem;
                font-weight: 700;
                padding: 0.15rem 0.4rem;
                border-radius: 9999px;
                max-width: 11rem;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .ap-imei-scanner-empty,
            .ap-imei-scanner-limit {
                padding: 1.5rem 1rem;
                text-align: center;
                color: #94a3b8;
                font-size: 0.8125rem;
            }
            .ap-imei-scanner-limit {
                color: #b45309;
                background: #fffbeb;
                border-top: 1px solid #fde68a;
                font-weight: 600;
            }
            .ap-register-summary {
                padding: 0.875rem 1rem;
                border-radius: 0.625rem;
                background: #fff7ed;
                border: 1px solid #fed7aa;
                font-size: 0.8125rem;
                color: #9a3412;
            }
            .ap-register-summary strong { color: #c2410c; }
            .ap-register-summary.hidden { display: none; }
            .ap-imei-panel--locked { opacity: 0.55; pointer-events: none; }
        </style>
    @endpush

    <div class="admin-prod-page admin-prod-form-wide">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-8">
            <div>
                <p class="admin-prod-eyebrow">Inventory</p>
                <h1 class="admin-prod-title">Add product (IMEI)</h1>
                <p class="admin-prod-subtitle">Pick purchase/stock and model, then paste or scan IMEIs — each code must be exactly 15 digits.</p>
            </div>
            <a href="{{ route('admin.stock.stocks') }}" class="admin-prod-back shrink-0">Back to stocks</a>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="admin-prod-alert admin-prod-alert--warning mb-4" role="alert">{{ session('error') }}</div>
        @endif

        <div class="admin-clay-panel admin-prod-form-shell overflow-hidden space-y-0">
            <div class="admin-prod-form-body space-y-6">
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-4">
                    <h2 class="text-sm font-semibold text-slate-900 mb-2">Capture & Scan barcodes</h2>
                    <p class="text-xs text-slate-600 mb-3">Capture a photo of the IMEI barcode — scanned codes are added to the Scanner tab below.</p>
                    <input type="file" id="barcode_photos" accept="image/*" class="admin-prod-file">
                    <button type="button" id="btn_decode_photos" class="mt-3 bg-slate-800 text-white text-sm px-4 py-2 rounded-lg hover:bg-slate-700">Capture & Scan</button>
                    <p id="decode_status" class="text-xs text-slate-500 mt-2 min-h-[1rem]"></p>
                </div>

                <form action="{{ route('admin.stock.store-add-product') }}" method="POST" id="add-product-form">
                    @csrf
                    <textarea name="imei_numbers" id="imei_numbers" class="hidden" aria-hidden="true">{{ old('imei_numbers') }}</textarea>

                    <div class="space-y-4">
                        <div>
                            @if(($addProductTarget ?? 'stock') === 'purchase')
                                <label for="add_product_target" class="admin-prod-label">Purchase</label>
                                <select name="purchase_id" id="add_product_target" required class="admin-prod-select"
                                    data-models-mode="purchase"
                                    data-models-url-template="{{ route('admin.stock.add-product.purchase.models', ['purchase' => '__ID__']) }}">
                                    <option value="">Select purchase</option>
                                    @foreach($purchasePickerRows ?? [] as $p)
                                        <option value="{{ $p->id }}" {{ (string) old('purchase_id') === (string) $p->id ? 'selected' : '' }}>{{ $p->name ?? 'Purchase #'.$p->id }}</option>
                                    @endforeach
                                </select>
                                @error('purchase_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                @if(($purchasePickerRows ?? collect())->isEmpty())
                                    <p class="text-xs text-amber-700 mt-1">No purchases with remaining slots. Create a purchase with quantity / limit first.</p>
                                @endif
                            @else
                                <label for="add_product_target" class="admin-prod-label">Stock</label>
                                <select name="stock_id" id="add_product_target" required class="admin-prod-select"
                                    data-models-mode="stock"
                                    data-models-url-template="{{ url('admin/stock/stocks') }}/__ID__/models">
                                    <option value="">Select stock</option>
                                    @foreach($stocks as $s)
                                        <option value="{{ $s->id }}" {{ (string) old('stock_id') === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                    @endforeach
                                </select>
                                @error('stock_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            @endif
                        </div>
                        <div>
                            <label for="catalog_product_id" class="admin-prod-label">Model <span class="text-slate-500 font-normal">(from selected {{ ($addProductTarget ?? 'stock') === 'purchase' ? 'purchase' : 'stock' }})</span></label>
                            <select name="catalog_product_id" id="catalog_product_id" required class="admin-prod-select">
                                <option value="">{{ ($addProductTarget ?? 'stock') === 'purchase' ? 'Select purchase first' : 'Select stock first' }}</option>
                            </select>
                            @error('catalog_product_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div id="ap-imei-panel" class="mt-6 ap-imei-panel--locked">
                        <p class="helper-text mb-3">Register new devices on the selected purchase — each IMEI must be exactly 15 digits.</p>
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                            <div class="ap-imei-tabs !mb-0" role="tablist" aria-label="IMEI entry mode">
                                <button type="button" class="ap-imei-tab ap-imei-tab--active" data-ap-imei-tab="list" role="tab" aria-selected="true">List</button>
                                <button type="button" class="ap-imei-tab" data-ap-imei-tab="scanner" role="tab" aria-selected="false">Scanner</button>
                            </div>
                            <button type="button" id="ap-imei-clear-all" class="admin-prod-btn-ghost text-xs py-2" disabled>Clear</button>
                        </div>

                        <div id="ap-imei-tab-list" class="ap-imei-tab-panel" role="tabpanel">
                            <div class="ap-imei-list" id="ap-imei-list">
                                <p class="ap-imei-empty">Select a model, then paste IMEIs in the Scanner tab or use Capture & Scan.</p>
                            </div>
                        </div>

                        <div id="ap-imei-tab-scanner" class="ap-imei-tab-panel hidden" role="tabpanel">
                            <div class="ap-scanner-layout">
                                <div>
                                    <div class="ap-scanner-input-head">
                                        <span class="ap-scanner-input-head__label">Paste or scan IMEIs</span>
                                        <span class="ap-scanner-input-head__count" id="ap-scanner-line-count">0 / 500 unique lines</span>
                                    </div>
                                    <textarea id="ap-imei-scanner-input" class="admin-prod-input ap-imei-scanner-input py-2 px-3"
                                        placeholder="One IMEI per line — paste up to 500 at once…" disabled spellcheck="false"></textarea>
                                    <p class="helper-text mt-2">Each line is one IMEI with exactly 15 digits. Green = ready to register. Red = wrong length, duplicate, or already in the system.</p>
                                </div>
                                <div class="ap-scanner-results-col">
                                    <div class="ap-scanner-stats hidden" id="ap-scanner-stats">
                                        <span class="ap-scanner-stat ap-scanner-stat--total" id="ap-stat-total">0 scanned</span>
                                        <span class="ap-scanner-stat ap-scanner-stat--valid" id="ap-stat-valid">0 valid</span>
                                        <span class="ap-scanner-stat ap-scanner-stat--invalid" id="ap-stat-invalid">0 invalid</span>
                                        <span class="ap-scanner-stat ap-scanner-stat--selected" id="ap-stat-selected">0 selected</span>
                                    </div>
                                    <div class="ap-scanner-filters hidden" id="ap-scanner-filters">
                                        <button type="button" class="ap-scanner-filter ap-scanner-filter--active" data-scanner-filter="all">All</button>
                                        <button type="button" class="ap-scanner-filter" data-scanner-filter="valid">Valid</button>
                                        <button type="button" class="ap-scanner-filter" data-scanner-filter="invalid">Invalid</button>
                                    </div>
                                    <div class="ap-imei-scanner-results" id="ap-scanner-results">
                                        <p class="ap-imei-scanner-empty">Scan or paste IMEIs to validate in bulk.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ap-register-summary hidden mt-4" id="ap-register-summary">
                            Registering <strong id="ap-summary-count">0</strong> device(s) on <strong id="ap-summary-purchase">—</strong>, model <strong id="ap-summary-model">—</strong>.
                        </div>
                    </div>

                    @error('imei_numbers')
                        <div class="text-red-500 text-xs mt-4 p-2 bg-red-50 rounded whitespace-pre-wrap">{{ $message }}</div>
                    @enderror

                    <div class="admin-prod-form-footer !mt-6 !px-0 !border-0 !shadow-none">
                        <button type="submit" class="admin-prod-btn-primary px-8" id="ap-submit-btn" disabled>Save all</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const IMEI_LEN = 15;
                const SCANNER_MAX = 500;
                const VALIDATE_URL = @json(route('admin.stock.add-product.validate-imeis'));
                const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const targetSelect = document.getElementById('add_product_target');
                const modelSelect = document.getElementById('catalog_product_id');
                const imeiPanel = document.getElementById('ap-imei-panel');
                const imeiListEl = document.getElementById('ap-imei-list');
                const scannerInput = document.getElementById('ap-imei-scanner-input');
                const scannerResults = document.getElementById('ap-scanner-results');
                const hiddenImeis = document.getElementById('imei_numbers');
                const submitBtn = document.getElementById('ap-submit-btn');
                const form = document.getElementById('add-product-form');

                let activeTab = 'list';
                let selectedImeis = new Set();
                let scannerParsedCache = [];
                let scannerOverLimit = false;
                let scannerFilter = 'all';
                let registeredLookup = new Set();
                let slotsRemaining = 0;
                let purchaseLabel = '—';
                let modelLabel = '—';
                let validateTimer = null;
                let validateRequestId = 0;

                function normalizeImei(value) {
                    return String(value || '').trim().replace(/\s+/g, '');
                }

                function escapeHtml(s) {
                    const d = document.createElement('div');
                    d.textContent = s;
                    return d.innerHTML;
                }

                function getTargetPayload() {
                    const mode = targetSelect.dataset.modelsMode || 'stock';
                    const id = targetSelect.value;
                    if (!id) return null;
                    return mode === 'purchase'
                        ? { purchase_id: parseInt(id, 10) }
                        : { stock_id: parseInt(id, 10) };
                }

                function evaluateLine(rawLine) {
                    const imei = normalizeImei(rawLine);
                    if (!imei) return null;
                    if (!/^\d+$/.test(imei)) {
                        return { imei: imei, valid: false, status: 'Digits only' };
                    }
                    if (imei.length !== IMEI_LEN) {
                        return { imei: imei, valid: false, status: 'Must be ' + IMEI_LEN + ' digits' };
                    }
                    if (registeredLookup.has(imei)) {
                        return { imei: imei, valid: false, status: 'Already registered' };
                    }
                    return { imei: imei, valid: true, status: 'Ready' };
                }

                function parseScannerLines() {
                    const lines = (scannerInput.value || '').split(/\r?\n/);
                    const parsed = [];
                    const seen = new Set();
                    let uniqueCount = 0;
                    let overLimit = false;
                    let validCount = 0;

                    lines.forEach(function (line, lineIndex) {
                        const result = evaluateLine(line);
                        if (!result) return;

                        const key = result.imei;
                        if (seen.has(key)) {
                            parsed.push({ imei: result.imei, valid: false, kind: 'invalid', status: 'Duplicate in list', lineIndex: lineIndex + 1 });
                            return;
                        }
                        seen.add(key);
                        uniqueCount += 1;

                        if (uniqueCount > SCANNER_MAX) {
                            overLimit = true;
                            parsed.push({ imei: result.imei, valid: false, kind: 'skipped', status: 'Over 500 limit', lineIndex: lineIndex + 1 });
                            return;
                        }

                        let kind = result.valid ? 'valid' : 'invalid';
                        let status = result.status;
                        if (result.valid) {
                            validCount += 1;
                            if (validCount > slotsRemaining && slotsRemaining > 0) {
                                kind = 'invalid';
                                status = 'Over slot limit';
                                result.valid = false;
                            } else if (slotsRemaining <= 0) {
                                kind = 'invalid';
                                status = 'No slots left';
                                result.valid = false;
                            }
                        }

                        parsed.push(Object.assign({}, result, { kind: kind, lineIndex: lineIndex + 1 }));
                    });

                    return {
                        parsed: parsed,
                        overLimit: overLimit,
                        uniqueCount: uniqueCount,
                        stats: {
                            total: parsed.length,
                            valid: parsed.filter(function (i) { return i.kind === 'valid'; }).length,
                            invalid: parsed.filter(function (i) { return i.kind === 'invalid' || i.kind === 'skipped'; }).length,
                        },
                    };
                }

                function syncHiddenAndSummary() {
                    const list = [...selectedImeis];
                    hiddenImeis.value = list.join('\n');
                    const summary = document.getElementById('ap-register-summary');
                    if (list.length > 0) {
                        summary.classList.remove('hidden');
                        document.getElementById('ap-summary-count').textContent = String(list.length);
                        document.getElementById('ap-summary-purchase').textContent = purchaseLabel;
                        document.getElementById('ap-summary-model').textContent = modelLabel;
                    } else {
                        summary.classList.add('hidden');
                    }
                    submitBtn.disabled = list.length === 0 || !modelSelect.value || !targetSelect.value;
                    document.getElementById('ap-imei-clear-all').disabled = list.length === 0 && !(scannerInput.value || '').trim();
                }

                function applySelectionFromScanner() {
                    const bundle = parseScannerLines();
                    scannerParsedCache = bundle.parsed;
                    scannerOverLimit = bundle.overLimit;
                    selectedImeis.clear();
                    bundle.parsed.forEach(function (item) {
                        if (item.kind === 'valid') selectedImeis.add(item.imei);
                    });
                    syncHiddenAndSummary();
                    updateScannerLineCount(bundle.uniqueCount, bundle.overLimit);
                    updateScannerStats(bundle.stats, selectedImeis.size);
                    renderImeiList();
                }

                function renderImeiList() {
                    imeiListEl.innerHTML = '';
                    const rows = [...selectedImeis].sort();
                    if (!rows.length) {
                        imeiListEl.innerHTML = '<p class="ap-imei-empty">No valid IMEIs selected — use the Scanner tab to paste or scan codes.</p>';
                        return;
                    }
                    rows.forEach(function (imei) {
                        const label = document.createElement('label');
                        label.className = 'ap-imei-row';
                        label.innerHTML =
                            '<input type="checkbox" value="' + escapeHtml(imei) + '" checked>' +
                            '<div class="ap-imei-row__serial">' + escapeHtml(imei) + '</div>' +
                            '<span class="text-xs font-bold text-green-700 bg-green-50 px-2 py-0.5 rounded-full">Ready</span>';
                        label.querySelector('input').addEventListener('change', function (e) {
                            if (e.target.checked) selectedImeis.add(imei);
                            else selectedImeis.delete(imei);
                            syncHiddenAndSummary();
                        });
                        imeiListEl.appendChild(label);
                    });
                }

                function renderScannerResults() {
                    if (!scannerParsedCache.length) {
                        const bundle = parseScannerLines();
                        scannerParsedCache = bundle.parsed;
                        updateScannerLineCount(bundle.uniqueCount, bundle.overLimit);
                        updateScannerStats(bundle.stats, selectedImeis.size);
                    }
                    const parsed = scannerParsedCache;
                    if (!parsed.length) {
                        scannerResults.innerHTML = '<p class="ap-imei-scanner-empty">Scan or paste IMEIs to validate in bulk.</p>';
                        updateScannerStats({ total: 0, valid: 0, invalid: 0 }, 0);
                        return;
                    }
                    const visible = parsed.filter(function (item) {
                        if (scannerFilter === 'valid') return item.kind === 'valid';
                        if (scannerFilter === 'invalid') return item.kind === 'invalid' || item.kind === 'skipped';
                        return true;
                    });
                    if (!visible.length) {
                        scannerResults.innerHTML = '<p class="ap-imei-scanner-empty">No IMEIs match this filter.</p>';
                        return;
                    }
                    const html = visible.map(function (item) {
                        const cls = item.kind === 'valid' ? 'ap-imei-scanner-line--valid' : (item.kind === 'skipped' ? 'ap-imei-scanner-line--skipped' : 'ap-imei-scanner-line--invalid');
                        return '<div class="ap-imei-scanner-line ' + cls + '">' +
                            '<span>' + (item.lineIndex || '') + '</span>' +
                            '<span title="' + escapeHtml(item.imei) + '">' + escapeHtml(item.imei) + '</span>' +
                            '<span class="ap-imei-scanner-line__status">' + escapeHtml(item.status) + '</span></div>';
                    }).join('');
                    const limitNote = scannerOverLimit
                        ? '<p class="ap-imei-scanner-limit">Only the first ' + SCANNER_MAX + ' unique IMEIs are processed.</p>'
                        : '';
                    scannerResults.innerHTML = html + limitNote;
                }

                function updateScannerLineCount(uniqueCount, overLimit) {
                    const el = document.getElementById('ap-scanner-line-count');
                    el.textContent = uniqueCount + ' / ' + SCANNER_MAX + ' unique lines';
                    el.classList.toggle('ap-scanner-input-head__count--warn', overLimit || uniqueCount > SCANNER_MAX);
                }

                function updateScannerStats(stats, selectedCount) {
                    const wrap = document.getElementById('ap-scanner-stats');
                    const filters = document.getElementById('ap-scanner-filters');
                    if (!stats.total) {
                        wrap.classList.add('hidden');
                        filters.classList.add('hidden');
                        return;
                    }
                    wrap.classList.remove('hidden');
                    filters.classList.remove('hidden');
                    document.getElementById('ap-stat-total').textContent = stats.total + ' scanned';
                    document.getElementById('ap-stat-valid').textContent = stats.valid + ' valid';
                    document.getElementById('ap-stat-invalid').textContent = stats.invalid + ' invalid';
                    document.getElementById('ap-stat-selected').textContent = selectedCount + ' selected';
                }

                function queueScannerUpdate() {
                    if (validateTimer) clearTimeout(validateTimer);
                    validateTimer = setTimeout(function () {
                        refreshRegisteredLookup().then(function () {
                            scannerParsedCache = [];
                            applySelectionFromScanner();
                            renderScannerResults();
                        });
                    }, 180);
                }

                function refreshRegisteredLookup() {
                    const payload = getTargetPayload();
                    const productId = modelSelect.value;
                    const imeis = (scannerInput.value || '').split(/\r?\n/)
                        .map(normalizeImei)
                        .filter(function (i) { return i.length === IMEI_LEN && /^\d+$/.test(i); });
                    const unique = [...new Set(imeis)].slice(0, SCANNER_MAX);

                    if (!payload || !productId || !unique.length) {
                        registeredLookup = new Set();
                        return Promise.resolve();
                    }

                    const requestId = ++validateRequestId;
                    return fetch(VALIDATE_URL, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': CSRF_TOKEN,
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(Object.assign({}, payload, {
                            catalog_product_id: parseInt(productId, 10),
                            imeis: unique,
                        })),
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (json) {
                            if (requestId !== validateRequestId) return;
                            if (json.ok) {
                                registeredLookup = new Set((json.registered || []).map(normalizeImei));
                                slotsRemaining = parseInt(json.slots_remaining, 10) || 0;
                                purchaseLabel = json.purchase_label || purchaseLabel;
                                modelLabel = json.model_label || modelLabel;
                            }
                        })
                        .catch(function () { /* keep client-side validation */ });
                }

                function resetImeiPanel() {
                    selectedImeis.clear();
                    scannerInput.value = '';
                    scannerParsedCache = [];
                    registeredLookup.clear();
                    scannerInput.disabled = true;
                    imeiPanel.classList.add('ap-imei-panel--locked');
                    hiddenImeis.value = '';
                    imeiListEl.innerHTML = '<p class="ap-imei-empty">Select a model, then paste IMEIs in the Scanner tab or use Capture & Scan.</p>';
                    scannerResults.innerHTML = '<p class="ap-imei-scanner-empty">Scan or paste IMEIs to validate in bulk.</p>';
                    updateScannerLineCount(0, false);
                    updateScannerStats({ total: 0, valid: 0, invalid: 0 }, 0);
                    syncHiddenAndSummary();
                }

                function unlockImeiPanel(meta) {
                    slotsRemaining = parseInt(meta.limit_remaining, 10) || 0;
                    modelLabel = meta.label || modelLabel;
                    const opt = targetSelect.options[targetSelect.selectedIndex];
                    purchaseLabel = opt ? opt.text : purchaseLabel;
                    scannerInput.disabled = slotsRemaining <= 0;
                    imeiPanel.classList.toggle('ap-imei-panel--locked', slotsRemaining <= 0);
                    syncHiddenAndSummary();
                }

                function setImeiTab(tab) {
                    activeTab = tab === 'scanner' ? 'scanner' : 'list';
                    document.querySelectorAll('.ap-imei-tab').forEach(function (btn) {
                        const active = btn.getAttribute('data-ap-imei-tab') === activeTab;
                        btn.classList.toggle('ap-imei-tab--active', active);
                        btn.setAttribute('aria-selected', active ? 'true' : 'false');
                    });
                    document.getElementById('ap-imei-tab-list').classList.toggle('hidden', activeTab !== 'list');
                    document.getElementById('ap-imei-tab-scanner').classList.toggle('hidden', activeTab !== 'scanner');
                    if (activeTab === 'list') renderImeiList();
                    else renderScannerResults();
                }

                targetSelect.addEventListener('change', function () {
                    resetImeiPanel();
                    const targetId = this.value;
                    const urlTemplate = this.dataset.modelsUrlTemplate || '';
                    const emptyLabel = (this.dataset.modelsMode === 'purchase') ? 'Select purchase first' : 'Select stock first';
                    modelSelect.innerHTML = '<option value="">Loading…</option>';
                    if (!targetId) {
                        modelSelect.innerHTML = '<option value="">' + emptyLabel + '</option>';
                        return;
                    }
                    fetch(urlTemplate.replace('__ID__', encodeURIComponent(targetId)), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            const list = data.data || [];
                            modelSelect.innerHTML = '<option value="">Select model</option>';
                            list.forEach(function (item) {
                                if (!item.product_id) return;
                                const opt = document.createElement('option');
                                opt.value = String(item.product_id);
                                opt.textContent = item.label || item.model || ('#' + item.product_id);
                                opt.dataset.limitRemaining = String(item.limit_remaining || 0);
                                modelSelect.appendChild(opt);
                            });
                        })
                        .catch(function () {
                            modelSelect.innerHTML = '<option value="">Error loading models</option>';
                        });
                });

                modelSelect.addEventListener('change', function () {
                    selectedImeis.clear();
                    scannerInput.value = '';
                    scannerParsedCache = [];
                    const opt = modelSelect.options[modelSelect.selectedIndex];
                    if (!opt || !opt.value) {
                        resetImeiPanel();
                        return;
                    }
                    modelLabel = opt.textContent || '—';
                    const payload = getTargetPayload();
                    if (payload) {
                        fetch(VALIDATE_URL, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': CSRF_TOKEN,
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify(Object.assign({}, payload, {
                                catalog_product_id: parseInt(opt.value, 10),
                                imeis: [],
                            })),
                        })
                            .then(function (r) { return r.json(); })
                            .then(function (json) {
                                if (json.ok) {
                                    unlockImeiPanel({
                                        limit_remaining: json.slots_remaining,
                                        label: json.model_label || modelLabel,
                                    });
                                    purchaseLabel = json.purchase_label || purchaseLabel;
                                } else {
                                    resetImeiPanel();
                                }
                                setImeiTab('scanner');
                            })
                            .catch(function () {
                                unlockImeiPanel({
                                    limit_remaining: opt.dataset.limitRemaining || 0,
                                    label: modelLabel,
                                });
                                setImeiTab('scanner');
                            });
                    }
                });

                scannerInput.addEventListener('input', queueScannerUpdate);

                document.querySelectorAll('.ap-imei-tab').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        setImeiTab(btn.getAttribute('data-ap-imei-tab'));
                    });
                });

                document.querySelectorAll('.ap-scanner-filter').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        scannerFilter = btn.getAttribute('data-scanner-filter') || 'all';
                        document.querySelectorAll('.ap-scanner-filter').forEach(function (b) {
                            b.classList.toggle('ap-scanner-filter--active', b === btn);
                        });
                        renderScannerResults();
                    });
                });

                document.getElementById('ap-imei-clear-all').addEventListener('click', function () {
                    selectedImeis.clear();
                    scannerInput.value = '';
                    scannerParsedCache = [];
                    registeredLookup.clear();
                    renderImeiList();
                    renderScannerResults();
                    updateScannerLineCount(0, false);
                    updateScannerStats({ total: 0, valid: 0, invalid: 0 }, 0);
                    syncHiddenAndSummary();
                });

                form.addEventListener('submit', function (e) {
                    syncHiddenAndSummary();
                    if (selectedImeis.size === 0) {
                        e.preventDefault();
                        alert('Add at least one valid 15-digit IMEI.');
                    }
                });

                /* Barcode photo scan → scanner textarea */
                var fileInput = document.getElementById('barcode_photos');
                var btnDecode = document.getElementById('btn_decode_photos');
                var statusEl = document.getElementById('decode_status');
                var _zxingReady = false;
                var _zxingLoadPromise = null;

                function loadZXing() {
                    if (_zxingReady) return Promise.resolve();
                    if (_zxingLoadPromise) return _zxingLoadPromise;
                    _zxingLoadPromise = new Promise(function (resolve, reject) {
                        var s = document.createElement('script');
                        s.src = 'https://cdn.jsdelivr.net/npm/@zxing/library@0.21.3/umd/index.min.js';
                        s.onload = function () { _zxingReady = true; resolve(); };
                        s.onerror = reject;
                        document.head.appendChild(s);
                    });
                    return _zxingLoadPromise;
                }

                function mergeCodes(codes) {
                    var existing = (scannerInput.value || '').replace(/\r\n/g, '\n').split('\n').map(normalizeImei).filter(Boolean);
                    var seen = {};
                    existing.forEach(function (c) { seen[c] = true; });
                    var added = 0;
                    codes.forEach(function (c) {
                        c = normalizeImei(c);
                        if (c && !seen[c]) { seen[c] = true; existing.push(c); added += 1; }
                    });
                    scannerInput.value = existing.join('\n');
                    setImeiTab('scanner');
                    queueScannerUpdate();
                    return added;
                }

                async function decodeFileZXing(reader, file) {
                    var found = new Set();
                    var imgUrl = URL.createObjectURL(file);
                    var img = await new Promise(function (res, rej) {
                        var i = new Image();
                        i.onload = function () { res(i); };
                        i.onerror = rej;
                        i.src = imgUrl;
                    });
                    var W = img.naturalWidth, H = img.naturalHeight;
                    var canvas = document.createElement('canvas');
                    var ctx = canvas.getContext('2d');
                    async function tryRegion(sx, sy, sw, sh) {
                        if (sw < 10 || sh < 10) return;
                        canvas.width = sw; canvas.height = sh;
                        ctx.clearRect(0, 0, sw, sh);
                        ctx.drawImage(img, sx, sy, sw, sh, 0, 0, sw, sh);
                        try {
                            var result = await reader.decodeFromImageUrl(canvas.toDataURL('image/jpeg', 0.92));
                            var text = (result && (result.text || (result.getText && result.getText()))) || '';
                            if (text.trim()) found.add(normalizeImei(text));
                        } catch (e) { /* no barcode */ }
                    }
                    await tryRegion(0, 0, W, H);
                    var grids = [[4,3],[3,3],[4,2],[3,2]];
                    for (var g = 0; g < grids.length; g++) {
                        var rows = grids[g][0], cols = grids[g][1];
                        if (Math.floor(W / cols) < 30 || Math.floor(H / rows) < 30) continue;
                        var cellW = Math.floor(W / cols), cellH = Math.floor(H / rows);
                        for (var r = 0; r < rows; r++) {
                            for (var c = 0; c < cols; c++) {
                                await tryRegion(c * cellW, r * cellH, cellW, cellH);
                            }
                        }
                        if (found.size > 0 && g >= 1) break;
                    }
                    URL.revokeObjectURL(imgUrl);
                    return Array.from(found);
                }

                btnDecode.addEventListener('click', async function () {
                    if (!modelSelect.value) {
                        statusEl.textContent = 'Select purchase/stock and model first.';
                        return;
                    }
                    var files = fileInput.files;
                    if (!files || !files.length) {
                        statusEl.textContent = 'Choose a photo first.';
                        return;
                    }
                    btnDecode.disabled = true;
                    statusEl.textContent = 'Loading decoder…';
                    try { await loadZXing(); } catch (e) {
                        statusEl.textContent = 'Could not load barcode library.';
                        btnDecode.disabled = false;
                        return;
                    }
                    statusEl.textContent = 'Scanning barcode from photo…';
                    var allCodes = [];
                    try {
                        var reader = new ZXing.BrowserMultiFormatReader();
                        for (var i = 0; i < files.length; i++) {
                            allCodes = allCodes.concat(await decodeFileZXing(reader, files[i]));
                        }
                    } catch (e) {
                        statusEl.textContent = 'Decode error: ' + (e.message || e);
                        btnDecode.disabled = false;
                        return;
                    }
                    btnDecode.disabled = false;
                    if (allCodes.length) {
                        var added = mergeCodes(allCodes);
                        statusEl.textContent = 'Found ' + allCodes.length + ' barcode(s). Added ' + added + ' new code(s).';
                    } else {
                        statusEl.textContent = 'No barcode found — paste IMEIs manually in the Scanner tab.';
                    }
                });

                @if(old('stock_id') || old('purchase_id'))
                    targetSelect.dispatchEvent(new Event('change'));
                    setTimeout(function () {
                        var m = @json(old('catalog_product_id'));
                        if (m && modelSelect.options.length) {
                            modelSelect.value = String(m);
                            modelSelect.dispatchEvent(new Event('change'));
                            var oldImeis = @json(old('imei_numbers'));
                            if (oldImeis) {
                                scannerInput.value = oldImeis;
                                queueScannerUpdate();
                            }
                        }
                    }, 600);
                @endif
            })();
        </script>
    @endpush
</x-admin-layout>
