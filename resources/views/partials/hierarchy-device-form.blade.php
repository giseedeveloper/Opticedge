@props([
    'title',
    'subtitle',
    'backUrl',
    'backLabel' => 'Back',
    'formAction',
    'recipientLabel',
    'recipientName',
    'recipientOptions' => [],
    'recipientSelected' => null,
    'productOptions' => [],
    'assignableUrl',
    'submitLabel' => 'Submit',
    'imeiHelp' => '',
])

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .hier-stepper { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.5rem; }
        @media (max-width: 640px) { .hier-stepper { grid-template-columns: 1fr; } }
        .hier-step {
            display: flex; align-items: flex-start; gap: 0.625rem; padding: 0.75rem 0.875rem;
            border-radius: 0.75rem; border: 1.5px solid #e2e8f0; background: #fff;
        }
        .hier-step--active { border-color: #f97316; box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12); }
        .hier-step--done { border-color: #86efac; background: #f0fdf4; }
        .hier-step__num {
            flex-shrink: 0; width: 1.625rem; height: 1.625rem; border-radius: 9999px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.6875rem; font-weight: 800; background: #f1f5f9; color: #64748b;
        }
        .hier-step--active .hier-step__num { background: linear-gradient(135deg, #fa8900, #e07800); color: #fff; }
        .hier-step--done .hier-step__num { background: #22c55e; color: #fff; }
        .hier-step__label { font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #94a3b8; }
        .hier-step--active .hier-step__label { color: #ea580c; }
        .hier-step--done .hier-step__label { color: #16a34a; }
        .hier-step__value { font-size: 0.8125rem; font-weight: 600; color: #232f3e; margin-top: 0.125rem; line-height: 1.3; }
        .hier-panel { border: 1px solid rgba(255,255,255,0.7); border-radius: 1rem; background: linear-gradient(145deg, #fff 0%, #f8fafc 100%); overflow: hidden; }
        .hier-panel__head { padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; background: rgba(248, 250, 252, 0.8); }
        .hier-panel__body { padding: 1.25rem; }
        .hier-panel--locked { opacity: 0.55; pointer-events: none; }
        .hier-helper { font-size: 0.75rem; color: #64748b; margin-top: 0.375rem; }
        .hier-imei-tabs { display: flex; gap: 0.375rem; padding: 0.25rem; border-radius: 0.625rem; background: #f1f5f9; width: fit-content; }
        .hier-imei-tab {
            border: none; border-radius: 0.5rem; padding: 0.4375rem 0.875rem;
            font-size: 0.75rem; font-weight: 700; color: #64748b; background: transparent; cursor: pointer;
        }
        .hier-imei-tab--active { background: #fff; color: #ea580c; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08); }
        .hier-imei-tab-panel.hidden { display: none; }
        .hier-scanner-layout { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr); gap: 1rem; }
        @media (max-width: 900px) { .hier-scanner-layout { grid-template-columns: 1fr; } }
        .hier-scanner-input-col, .hier-scanner-results-col { display: flex; flex-direction: column; min-height: 0; }
        .hier-scanner-input-head { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.5rem; }
        .hier-scanner-input-head__label { font-size: 0.75rem; font-weight: 700; color: #334155; }
        .hier-scanner-input-head__count { font-size: 0.6875rem; font-weight: 700; color: #64748b; font-variant-numeric: tabular-nums; }
        .hier-scanner-input-head__count--warn { color: #dc2626; }
        .hier-scanner-input {
            width: 100%; min-height: 11rem; max-height: 22rem; resize: vertical;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.8125rem; line-height: 1.45;
        }
        .hier-scanner-results-col { border: 1px solid #e2e8f0; border-radius: 0.75rem; background: #f8fafc; overflow: hidden; }
        .hier-scanner-stats { display: flex; flex-wrap: wrap; gap: 0.375rem; padding: 0.625rem 0.75rem; border-bottom: 1px solid #e2e8f0; background: #fff; }
        .hier-scanner-stats.hidden { display: none; }
        .hier-scanner-stat { display: inline-flex; padding: 0.2rem 0.55rem; border-radius: 9999px; font-size: 0.6875rem; font-weight: 700; font-variant-numeric: tabular-nums; }
        .hier-scanner-stat--total { background: #f1f5f9; color: #475569; }
        .hier-scanner-stat--valid { background: #dcfce7; color: #166534; }
        .hier-scanner-stat--invalid { background: #fee2e2; color: #b91c1c; }
        .hier-scanner-stat--selected { background: #ffedd5; color: #c2410c; }
        .hier-scanner-filters { display: flex; flex-wrap: wrap; gap: 0.375rem; padding: 0.5rem 0.75rem; border-bottom: 1px solid #e2e8f0; background: #fff; }
        .hier-scanner-filters.hidden { display: none; }
        .hier-scanner-filter { border: 1px solid #e2e8f0; border-radius: 9999px; padding: 0.2rem 0.625rem; font-size: 0.6875rem; font-weight: 700; color: #64748b; background: #fff; cursor: pointer; }
        .hier-scanner-filter--active { border-color: #fdba74; background: #fff7ed; color: #c2410c; }
        .hier-imei-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; margin-bottom: 0.75rem; }
        .hier-imei-search { flex: 1; min-width: 12rem; }
        .hier-imei-list, .hier-scanner-results {
            max-height: min(28rem, 52vh); overflow-y: auto; overflow-x: hidden;
            border: 1px solid #e2e8f0; border-radius: 0.625rem; background: #fff;
        }
        .hier-scanner-results { flex: 1; min-height: 16rem; border: none; border-radius: 0; contain: content; }
        .hier-imei-row {
            display: flex; align-items: center; gap: 0.75rem; padding: 0.625rem 1rem;
            border-bottom: 1px solid #f1f5f9; cursor: pointer;
        }
        .hier-imei-row:last-child { border-bottom: none; }
        .hier-imei-row:hover { background: #f8fafc; }
        .hier-imei-row input[type="checkbox"] { accent-color: #f97316; width: 1rem; height: 1rem; flex-shrink: 0; }
        .hier-imei-row__serial { font-family: ui-monospace, monospace; font-size: 0.8125rem; font-weight: 600; color: #232f3e; }
        .hier-imei-row__model { font-size: 0.75rem; color: #64748b; }
        .hier-scanner-line {
            display: grid; grid-template-columns: 2.25rem minmax(0, 1fr) auto; align-items: center;
            gap: 0.5rem 0.75rem; padding: 0.375rem 0.75rem; border-bottom: 1px solid #f1f5f9;
            font-family: ui-monospace, monospace; font-size: 0.75rem;
        }
        .hier-scanner-line:last-child { border-bottom: none; }
        .hier-scanner-line__num { font-size: 0.625rem; font-weight: 700; color: #94a3b8; text-align: right; }
        .hier-scanner-line__imei { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .hier-scanner-line--valid { background: #f0fdf4; color: #166534; }
        .hier-scanner-line--invalid { background: #fef2f2; color: #b91c1c; }
        .hier-scanner-line--skipped { background: #fffbeb; color: #b45309; }
        .hier-scanner-line__status { flex-shrink: 0; max-width: 11rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.625rem; font-weight: 700; padding: 0.15rem 0.4rem; border-radius: 9999px; }
        .hier-scanner-line--valid .hier-scanner-line__status { background: #dcfce7; color: #166534; }
        .hier-scanner-line--invalid .hier-scanner-line__status { background: #fee2e2; color: #b91c1c; }
        .hier-scanner-line--skipped .hier-scanner-line__status { background: #fef3c7; color: #b45309; }
        .hier-imei-empty, .hier-scanner-empty, .hier-scanner-limit { padding: 1.5rem 1rem; text-align: center; color: #94a3b8; font-size: 0.8125rem; }
        .hier-scanner-limit { color: #b45309; background: #fffbeb; border-top: 1px solid #fde68a; font-weight: 600; }
        .hier-imei-summary { display: flex; flex-wrap: wrap; gap: 0.5rem 1rem; margin-bottom: 0.75rem; font-size: 0.75rem; color: #64748b; }
        .hier-imei-summary.hidden { display: none; }
        .hier-assign-summary { padding: 0.875rem 1.25rem; border-radius: 0.625rem; background: #fff7ed; border: 1px solid #fed7aa; font-size: 0.8125rem; color: #9a3412; }
        .hier-assign-summary.hidden { display: none; }
        .admin-prod-select2-wrap .select2-container--default .select2-selection--single { min-height: 42px; padding: 6px 10px; border-color: #cbd5e1; border-radius: 0.5rem; }
    </style>
@endpush

<div class="admin-prod-page admin-prod-form-wide !pt-4 sm:!pt-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-6">
        <div>
            <h1 class="admin-prod-title">{{ $title }}</h1>
            <p class="admin-prod-subtitle">{{ $subtitle }}</p>
        </div>
        <a href="{{ $backUrl }}" class="admin-prod-back shrink-0">{{ $backLabel }}</a>
    </div>

    @if (session('success'))
        <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="admin-prod-alert admin-prod-alert--error mb-4" role="alert">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ $formAction }}" id="hierarchy-device-form">
        @csrf
        <div id="imei-hidden-inputs"></div>

        <div class="hier-stepper mb-6">
            <div class="hier-step hier-step--active" data-step="1">
                <span class="hier-step__num">1</span>
                <div>
                    <p class="hier-step__label">{{ $recipientLabel }}</p>
                    <p class="hier-step__value" id="step-label-recipient">Not selected</p>
                </div>
            </div>
            <div class="hier-step" data-step="2">
                <span class="hier-step__num">2</span>
                <div>
                    <p class="hier-step__label">Product</p>
                    <p class="hier-step__value" id="step-label-product">Not selected</p>
                </div>
            </div>
            <div class="hier-step" data-step="3">
                <span class="hier-step__num">3</span>
                <div>
                    <p class="hier-step__label">IMEIs</p>
                    <p class="hier-step__value" id="step-label-imeis">0 selected</p>
                </div>
            </div>
        </div>

        <div class="space-y-4 admin-prod-select2-wrap">
            <div class="hier-panel admin-clay-panel !rounded-2xl" id="panel-recipient">
                <div class="hier-panel__head">
                    <h2 class="admin-prod-form-title text-base">1. {{ $recipientLabel }}</h2>
                </div>
                <div class="hier-panel__body">
                    <label for="{{ $recipientName }}" class="admin-prod-label">{{ $recipientLabel }}</label>
                    <select id="{{ $recipientName }}" name="{{ $recipientName }}" class="admin-prod-select w-full" required>
                        <option value="">Select…</option>
                        @foreach ($recipientOptions as $opt)
                            <option value="{{ $opt->id }}"
                                {{ (string) old($recipientName, $recipientSelected) === (string) $opt->id ? 'selected' : '' }}>
                                {{ $opt->name }}@if (!empty($opt->email)) · {{ $opt->email }}@endif
                            </option>
                        @endforeach
                    </select>
                    @error($recipientName)
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="hier-panel admin-clay-panel !rounded-2xl" id="panel-product">
                <div class="hier-panel__head">
                    <h2 class="admin-prod-form-title text-base">2. Product</h2>
                </div>
                <div class="hier-panel__body">
                    <label for="product_id" class="admin-prod-label">Model</label>
                    <select id="product_id" name="product_id" class="admin-prod-select w-full" required disabled>
                        <option value="">Select {{ strtolower($recipientLabel) }} first…</option>
                        @foreach ($productOptions as $p)
                            <option value="{{ $p->id }}" {{ (string) old('product_id') === (string) $p->id ? 'selected' : '' }}>
                                {{ $p->category->name ?? '—' }} — {{ $p->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="hier-panel admin-clay-panel !rounded-2xl hier-panel--locked" id="panel-imeis">
                <div class="hier-panel__head">
                    <h2 class="admin-prod-form-title text-base">3. IMEIs</h2>
                    @if ($imeiHelp)
                        <p class="admin-prod-form-hint !mt-0.5">{{ $imeiHelp }}</p>
                    @endif
                </div>
                <div class="hier-panel__body">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                        <div class="hier-imei-tabs" role="tablist">
                            <button type="button" class="hier-imei-tab hier-imei-tab--active" data-imei-tab="list">List</button>
                            <button type="button" class="hier-imei-tab" data-imei-tab="scanner">Scanner</button>
                        </div>
                        <button type="button" id="imei-clear-all" class="admin-prod-btn-ghost text-xs py-2" disabled>Clear</button>
                    </div>

                    <div id="imei-tab-list" class="hier-imei-tab-panel">
                        <div class="hier-imei-toolbar">
                            <input type="search" id="imei-search" class="admin-prod-input hier-imei-search py-2 text-sm" placeholder="Search IMEI…" disabled>
                            <button type="button" id="imei-select-all" class="admin-prod-btn-ghost text-xs py-2" disabled>Select all available</button>
                        </div>
                        <div class="hier-imei-summary hidden" id="imei-summary"></div>
                        <div class="hier-imei-list" id="imei-list">
                            <p class="hier-imei-empty">Select a product to load IMEIs.</p>
                        </div>
                    </div>

                    <div id="imei-tab-scanner" class="hier-imei-tab-panel hidden">
                        <div class="hier-scanner-layout">
                            <div class="hier-scanner-input-col">
                                <div class="hier-scanner-input-head">
                                    <span class="hier-scanner-input-head__label">Paste or scan IMEIs</span>
                                    <span class="hier-scanner-input-head__count" id="imei-scanner-line-count">0 / 500 lines</span>
                                </div>
                                <textarea id="imei-scanner-input" class="admin-prod-input hier-scanner-input py-2 px-3"
                                    placeholder="One IMEI per line — paste up to 500 at once…" disabled spellcheck="false"></textarea>
                                <p class="hier-helper">Green = available in your custody. Red = not found or not available. Only the first 500 unique lines are processed.</p>
                            </div>
                            <div class="hier-scanner-results-col">
                                <div class="hier-scanner-stats hidden" id="imei-scanner-stats">
                                    <span class="hier-scanner-stat hier-scanner-stat--total" id="scanner-stat-total">0 scanned</span>
                                    <span class="hier-scanner-stat hier-scanner-stat--valid" id="scanner-stat-valid">0 valid</span>
                                    <span class="hier-scanner-stat hier-scanner-stat--invalid" id="scanner-stat-invalid">0 invalid</span>
                                    <span class="hier-scanner-stat hier-scanner-stat--selected" id="scanner-stat-selected">0 selected</span>
                                </div>
                                <div class="hier-scanner-filters hidden" id="imei-scanner-filters">
                                    <button type="button" class="hier-scanner-filter hier-scanner-filter--active" data-scanner-filter="all">All</button>
                                    <button type="button" class="hier-scanner-filter" data-scanner-filter="valid">Valid</button>
                                    <button type="button" class="hier-scanner-filter" data-scanner-filter="invalid">Invalid</button>
                                </div>
                                <div class="hier-scanner-results" id="imei-scanner-results">
                                    <p class="hier-scanner-empty">Scan or paste IMEIs to validate in bulk.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @error('product_list_ids')
                        <p class="text-red-600 text-xs mt-2 font-semibold">{{ $message }}</p>
                    @enderror
                    @error('product_list_ids.*')
                        <p class="text-red-600 text-xs mt-2 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="hier-assign-summary mt-6 hidden" id="assign-summary">
            Assigning <strong id="summary-count">0</strong> device(s) to <strong id="summary-recipient">—</strong>, model <strong id="summary-product">—</strong>.
        </div>

        <div class="admin-prod-form-footer mt-6 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ $backUrl }}" class="admin-prod-btn-ghost">Cancel</a>
            <button type="submit" class="admin-prod-btn-primary px-8" id="submit-btn" disabled>{{ $submitLabel }}</button>
        </div>
    </form>
</div>

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        (function () {
            const assignableUrl = @json($assignableUrl);
            const oldImeiIds = @json(array_map('strval', old('product_list_ids', [])));
            const $recipient = jQuery('#{{ $recipientName }}');
            const $product = jQuery('#product_id');
            const $imeiList = document.getElementById('imei-list');
            const $imeiSearch = document.getElementById('imei-search');
            const $imeiScannerInput = document.getElementById('imei-scanner-input');
            const $imeiScannerResults = document.getElementById('imei-scanner-results');
            const $hiddenInputs = document.getElementById('imei-hidden-inputs');
            const $submit = document.getElementById('submit-btn');

            const SCANNER_MAX = 500;
            let selectedImeiIds = new Set(oldImeiIds);
            let imeiRows = [];
            let imeiLookup = new Map();
            let activeImeiTab = 'list';
            let scannerFilter = 'all';
            let scannerParsedCache = [];
            let scannerOverLimit = false;
            let scannerDebounceTimer = null;

            function normalizeImei(v) { return String(v || '').trim().replace(/\s+/g, ''); }
            function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

            function buildImeiLookup() {
                imeiLookup = new Map();
                imeiRows.forEach(function (row) {
                    const key = normalizeImei(row.imei_number || row.text || '');
                    if (key && !imeiLookup.has(key)) imeiLookup.set(key, row);
                });
            }

            function evaluateScannerLine(rawLine) {
                const imei = normalizeImei(rawLine);
                if (!imei) return null;
                const row = imeiLookup.get(imei);
                if (!row) return { imei, valid: false, status: 'Not in your custody' };
                if (row.selectable === false) return { imei, valid: false, row, status: row.status_label || 'Not available' };
                return { imei, valid: true, row, status: 'Available' };
            }

            function parseScannerLines() {
                const lines = ($imeiScannerInput.value || '').split(/\r?\n/);
                const parsed = [];
                const seen = new Set();
                let uniqueCount = 0;
                let overLimit = false;

                lines.forEach(function (line, lineIndex) {
                    const result = evaluateScannerLine(line);
                    if (!result) return;
                    const key = normalizeImei(result.imei);
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
                    parsed.push(Object.assign({}, result, { kind: result.valid ? 'valid' : 'invalid', lineIndex: lineIndex + 1 }));
                });

                return {
                    parsed,
                    overLimit,
                    uniqueCount,
                    stats: {
                        total: parsed.length,
                        valid: parsed.filter(function (i) { return i.kind === 'valid'; }).length,
                        invalid: parsed.filter(function (i) { return i.kind === 'invalid' || i.kind === 'skipped'; }).length,
                    },
                };
            }

            function syncHiddenInputs() {
                $hiddenInputs.innerHTML = '';
                selectedImeiIds.forEach(function (id) {
                    const inp = document.createElement('input');
                    inp.type = 'hidden';
                    inp.name = 'product_list_ids[]';
                    inp.value = id;
                    $hiddenInputs.appendChild(inp);
                });
            }

            function recipientLabel() {
                const opt = $recipient.find('option:selected');
                return opt.val() ? (opt.text().split(' · ')[0] || opt.text()) : 'Not selected';
            }

            function productLabel() {
                const opt = $product.find('option:selected');
                return opt.val() ? opt.text() : 'Not selected';
            }

            function setStepState(step, state) {
                const el = document.querySelector('.hier-step[data-step="' + step + '"]');
                if (!el) return;
                el.classList.remove('hier-step--active', 'hier-step--done');
                if (state) el.classList.add('hier-step--' + state);
            }

            function unlockPanel(id, unlock) {
                document.getElementById(id)?.classList.toggle('hier-panel--locked', !unlock);
            }

            function updateStepper() {
                const hasRecipient = !!$recipient.val();
                const hasProduct = !!$product.val();
                const imeiCount = selectedImeiIds.size;

                document.getElementById('step-label-recipient').textContent = recipientLabel();
                document.getElementById('step-label-product').textContent = productLabel();
                document.getElementById('step-label-imeis').textContent = imeiCount + ' selected';

                setStepState(1, hasRecipient ? 'done' : 'active');
                setStepState(2, hasProduct ? 'done' : (hasRecipient ? 'active' : ''));
                setStepState(3, imeiCount > 0 ? 'done' : (hasProduct ? 'active' : ''));

                unlockPanel('panel-product', hasRecipient);
                $product.prop('disabled', !hasRecipient);
                unlockPanel('panel-imeis', hasProduct);

                const summary = document.getElementById('assign-summary');
                if (imeiCount > 0 && hasRecipient && hasProduct) {
                    summary.classList.remove('hidden');
                    document.getElementById('summary-count').textContent = String(imeiCount);
                    document.getElementById('summary-recipient').textContent = recipientLabel();
                    document.getElementById('summary-product').textContent = productLabel();
                } else {
                    summary.classList.add('hidden');
                }

                $submit.disabled = !(hasRecipient && hasProduct && imeiCount > 0);
                const clearBtn = document.getElementById('imei-clear-all');
                if (clearBtn && imeiRows.length > 0) {
                    clearBtn.disabled = imeiCount === 0 && !($imeiScannerInput.value || '').trim();
                }
            }

            function renderImeiSummary() {
                const el = document.getElementById('imei-summary');
                if (!el) return;
                if (!imeiRows.length) { el.classList.add('hidden'); return; }
                el.classList.remove('hidden');
                el.innerHTML = '<span><strong>' + imeiRows.length + '</strong> available in your custody</span>';
            }

            function renderImeiList() {
                const q = ($imeiSearch.value || '').trim().toLowerCase();
                $imeiList.innerHTML = '';
                const visible = imeiRows.filter(function (row) {
                    if (!q) return true;
                    return (row.imei_number || row.text || '').toLowerCase().includes(q);
                });

                if (!imeiRows.length) {
                    $imeiList.innerHTML = '<p class="hier-imei-empty">No devices available for this model in your custody.</p>';
                    renderImeiSummary();
                    return;
                }
                if (!visible.length) {
                    $imeiList.innerHTML = '<p class="hier-imei-empty">No IMEIs match your search.</p>';
                    return;
                }

                visible.forEach(function (row) {
                    const label = document.createElement('label');
                    label.className = 'hier-imei-row';
                    const checked = selectedImeiIds.has(String(row.id));
                    const serial = row.imei_number || row.text || '';
                    const modelPart = row.model || '';
                    label.innerHTML =
                        '<input type="checkbox" value="' + escapeHtml(String(row.id)) + '"' + (checked ? ' checked' : '') + '>' +
                        '<div class="min-w-0 flex-1"><div class="hier-imei-row__serial">' + escapeHtml(serial) + '</div>' +
                        (modelPart ? '<div class="hier-imei-row__model">' + escapeHtml(modelPart) + '</div>' : '') + '</div>';
                    label.querySelector('input').addEventListener('change', function (e) {
                        if (e.target.checked) {
                            if (selectedImeiIds.size >= SCANNER_MAX) {
                                e.target.checked = false;
                                return;
                            }
                            selectedImeiIds.add(String(row.id));
                        } else {
                            selectedImeiIds.delete(String(row.id));
                        }
                        syncHiddenInputs();
                        updateStepper();
                    });
                    $imeiList.appendChild(label);
                });
                renderImeiSummary();
            }

            function updateScannerLineCount(uniqueCount, overLimit) {
                const el = document.getElementById('imei-scanner-line-count');
                if (!el) return;
                el.textContent = uniqueCount + ' / ' + SCANNER_MAX + ' unique lines';
                el.classList.toggle('hier-scanner-input-head__count--warn', overLimit || uniqueCount > SCANNER_MAX);
            }

            function updateScannerStats(stats, selectedCount) {
                const wrap = document.getElementById('imei-scanner-stats');
                const filters = document.getElementById('imei-scanner-filters');
                if (!wrap || !filters) return;
                if (!stats.total) { wrap.classList.add('hidden'); filters.classList.add('hidden'); return; }
                wrap.classList.remove('hidden');
                filters.classList.remove('hidden');
                document.getElementById('scanner-stat-total').textContent = stats.total + ' scanned';
                document.getElementById('scanner-stat-valid').textContent = stats.valid + ' valid';
                document.getElementById('scanner-stat-invalid').textContent = stats.invalid + ' invalid';
                document.getElementById('scanner-stat-selected').textContent = selectedCount + ' / ' + SCANNER_MAX + ' selected';
            }

            function applyScannerSelection() {
                const bundle = parseScannerLines();
                scannerParsedCache = bundle.parsed;
                scannerOverLimit = bundle.overLimit;
                const nextSelected = new Set();
                bundle.parsed.forEach(function (item) {
                    if (item.kind !== 'valid' || !item.row || nextSelected.size >= SCANNER_MAX) return;
                    nextSelected.add(String(item.row.id));
                });
                selectedImeiIds = nextSelected;
                syncHiddenInputs();
                updateStepper();
                updateScannerLineCount(bundle.uniqueCount, bundle.overLimit);
                updateScannerStats(bundle.stats, selectedImeiIds.size);
            }

            function renderScannerResults() {
                if (!scannerParsedCache.length) {
                    const bundle = parseScannerLines();
                    scannerParsedCache = bundle.parsed;
                    updateScannerLineCount(bundle.uniqueCount, bundle.overLimit);
                    updateScannerStats(bundle.stats, selectedImeiIds.size);
                }
                const parsed = scannerParsedCache;
                if (!parsed.length) {
                    $imeiScannerResults.innerHTML = '<p class="hier-scanner-empty">Scan or paste IMEIs to validate in bulk.</p>';
                    updateScannerStats({ total: 0, valid: 0, invalid: 0 }, 0);
                    return;
                }
                const visible = parsed.filter(function (item) {
                    if (scannerFilter === 'valid') return item.kind === 'valid';
                    if (scannerFilter === 'invalid') return item.kind === 'invalid' || item.kind === 'skipped';
                    return true;
                });
                if (!visible.length) {
                    $imeiScannerResults.innerHTML = '<p class="hier-scanner-empty">No IMEIs match this filter.</p>';
                    return;
                }
                const html = visible.map(function (item) {
                    const cls = item.kind === 'valid' ? 'hier-scanner-line--valid' : (item.kind === 'skipped' ? 'hier-scanner-line--skipped' : 'hier-scanner-line--invalid');
                    return '<div class="hier-scanner-line ' + cls + '">' +
                        '<span class="hier-scanner-line__num">' + (item.lineIndex || '') + '</span>' +
                        '<span class="hier-scanner-line__imei" title="' + escapeHtml(item.imei) + '">' + escapeHtml(item.imei) + '</span>' +
                        '<span class="hier-scanner-line__status" title="' + escapeHtml(item.status) + '">' + escapeHtml(item.status) + '</span></div>';
                }).join('');
                const limitNote = scannerOverLimit
                    ? '<p class="hier-scanner-limit">Only the first ' + SCANNER_MAX + ' unique IMEIs are checked and selected.</p>' : '';
                $imeiScannerResults.innerHTML = html + limitNote;
            }

            function queueScannerUpdate() {
                clearTimeout(scannerDebounceTimer);
                scannerDebounceTimer = setTimeout(function () {
                    scannerParsedCache = [];
                    applyScannerSelection();
                    renderScannerResults();
                }, 120);
            }

            function resetScannerPanel() {
                $imeiScannerInput.value = '';
                $imeiScannerInput.disabled = true;
                scannerParsedCache = [];
                scannerOverLimit = false;
                scannerFilter = 'all';
                updateScannerLineCount(0, false);
                updateScannerStats({ total: 0, valid: 0, invalid: 0 }, 0);
                document.querySelectorAll('.hier-scanner-filter').forEach(function (btn) {
                    btn.classList.toggle('hier-scanner-filter--active', btn.getAttribute('data-scanner-filter') === 'all');
                });
                $imeiScannerResults.innerHTML = '<p class="hier-scanner-empty">Scan or paste IMEIs to validate in bulk.</p>';
            }

            function setImeiInputsEnabled(enabled) {
                $imeiSearch.disabled = !enabled;
                $imeiScannerInput.disabled = !enabled;
                document.getElementById('imei-select-all').disabled = !enabled || !imeiRows.length;
                document.getElementById('imei-clear-all').disabled = !enabled || (selectedImeiIds.size === 0 && !($imeiScannerInput.value || '').trim());
            }

            function setImeiTab(tab) {
                activeImeiTab = tab === 'scanner' ? 'scanner' : 'list';
                document.querySelectorAll('.hier-imei-tab').forEach(function (btn) {
                    btn.classList.toggle('hier-imei-tab--active', btn.getAttribute('data-imei-tab') === activeImeiTab);
                });
                document.getElementById('imei-tab-list').classList.toggle('hidden', activeImeiTab !== 'list');
                document.getElementById('imei-tab-scanner').classList.toggle('hidden', activeImeiTab !== 'scanner');
                if (activeImeiTab === 'scanner' && selectedImeiIds.size > 0 && !($imeiScannerInput.value || '').trim()) {
                    const nums = imeiRows.filter(function (r) { return selectedImeiIds.has(String(r.id)); }).map(function (r) { return r.imei_number; }).filter(Boolean);
                    if (nums.length) {
                        $imeiScannerInput.value = nums.slice(0, SCANNER_MAX).join('\n');
                        scannerParsedCache = [];
                        applyScannerSelection();
                    }
                }
                if (activeImeiTab === 'list') renderImeiList();
                else renderScannerResults();
            }

            function setScannerFilter(filter) {
                scannerFilter = filter === 'valid' || filter === 'invalid' ? filter : 'all';
                document.querySelectorAll('.hier-scanner-filter').forEach(function (btn) {
                    btn.classList.toggle('hier-scanner-filter--active', btn.getAttribute('data-scanner-filter') === scannerFilter);
                });
                renderScannerResults();
            }

            function loadImeis(productId) {
                imeiRows = [];
                if (!productId || !$recipient.val()) {
                    selectedImeiIds.clear();
                    syncHiddenInputs();
                    renderImeiList();
                    resetScannerPanel();
                    setImeiInputsEnabled(false);
                    updateStepper();
                    return;
                }

                $imeiList.innerHTML = '<p class="hier-imei-empty">Loading IMEIs…</p>';
                resetScannerPanel();

                fetch(assignableUrl + '?product_id=' + encodeURIComponent(productId), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        imeiRows = (json && json.data) ? json.data : [];
                        buildImeiLookup();
                        if (oldImeiIds.length) {
                            selectedImeiIds = new Set(oldImeiIds.filter(function (id) {
                                return imeiRows.some(function (r) { return String(r.id) === String(id); });
                            }));
                        } else {
                            selectedImeiIds.clear();
                        }
                        syncHiddenInputs();
                        renderImeiList();
                        setImeiInputsEnabled(imeiRows.length > 0);
                        updateStepper();
                    })
                    .catch(function () {
                        $imeiList.innerHTML = '<p class="hier-imei-empty">Could not load IMEIs.</p>';
                    });
            }

            $recipient.select2({ width: '100%', placeholder: 'Select…' });
            $product.select2({ width: '100%' });

            $recipient.on('change', function () {
                if (this.value) unlockPanel('panel-product', true);
                loadImeis($product.val());
            });

            $product.on('change', function () { loadImeis(this.value); });

            $imeiSearch.addEventListener('input', renderImeiList);
            $imeiScannerInput.addEventListener('input', queueScannerUpdate);

            document.querySelectorAll('.hier-imei-tab').forEach(function (btn) {
                btn.addEventListener('click', function () { setImeiTab(btn.getAttribute('data-imei-tab')); });
            });
            document.querySelectorAll('.hier-scanner-filter').forEach(function (btn) {
                btn.addEventListener('click', function () { setScannerFilter(btn.getAttribute('data-scanner-filter')); });
            });

            document.getElementById('imei-select-all').addEventListener('click', function () {
                selectedImeiIds.clear();
                let count = 0;
                imeiRows.forEach(function (row) {
                    if (count >= SCANNER_MAX) return;
                    selectedImeiIds.add(String(row.id));
                    count += 1;
                });
                syncHiddenInputs();
                renderImeiList();
                if (activeImeiTab === 'scanner') {
                    $imeiScannerInput.value = imeiRows.slice(0, SCANNER_MAX).map(function (r) { return r.imei_number; }).join('\n');
                    scannerParsedCache = [];
                    applyScannerSelection();
                    renderScannerResults();
                }
                updateStepper();
            });

            document.getElementById('imei-clear-all').addEventListener('click', function () {
                selectedImeiIds.clear();
                $imeiScannerInput.value = '';
                scannerParsedCache = [];
                syncHiddenInputs();
                renderImeiList();
                resetScannerPanel();
                setImeiInputsEnabled(imeiRows.length > 0);
                updateStepper();
            });

            document.getElementById('hierarchy-device-form').addEventListener('submit', syncHiddenInputs);

            if ($recipient.val()) {
                unlockPanel('panel-product', true);
                $product.prop('disabled', false);
            }
            if ($product.val() && $recipient.val()) loadImeis($product.val());
            else updateStepper();
        })();
    </script>
@endpush
