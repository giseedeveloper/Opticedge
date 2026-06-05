<x-admin-layout>
    @include('admin.partials.catalog-styles')

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <style>
            .rm-assign-stepper {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 0.5rem;
            }
            @media (max-width: 640px) {
                .rm-assign-stepper { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            }
            .rm-assign-step {
                display: flex;
                align-items: flex-start;
                gap: 0.625rem;
                padding: 0.75rem 0.875rem;
                border-radius: 0.75rem;
                border: 1.5px solid #e2e8f0;
                background: #fff;
                transition: border-color 150ms ease, box-shadow 150ms ease;
            }
            .rm-assign-step--active {
                border-color: #f97316;
                box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12);
            }
            .rm-assign-step--done {
                border-color: #86efac;
                background: #f0fdf4;
            }
            .rm-assign-step__num {
                flex-shrink: 0;
                width: 1.625rem;
                height: 1.625rem;
                border-radius: 9999px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.6875rem;
                font-weight: 800;
                background: #f1f5f9;
                color: #64748b;
            }
            .rm-assign-step--active .rm-assign-step__num {
                background: linear-gradient(135deg, #fa8900, #e07800);
                color: #fff;
            }
            .rm-assign-step--done .rm-assign-step__num {
                background: #22c55e;
                color: #fff;
            }
            .rm-assign-step__label {
                font-size: 0.6875rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #94a3b8;
            }
            .rm-assign-step--active .rm-assign-step__label { color: #ea580c; }
            .rm-assign-step--done .rm-assign-step__label { color: #16a34a; }
            .rm-assign-step__value {
                font-size: 0.8125rem;
                font-weight: 600;
                color: #232f3e;
                margin-top: 0.125rem;
                line-height: 1.3;
            }
            .rm-assign-panel {
                border: 1px solid rgba(255,255,255,0.7);
                border-radius: 1rem;
                background: linear-gradient(145deg, #fff 0%, #f8fafc 100%);
                overflow: hidden;
            }
            .rm-assign-panel__head {
                padding: 1rem 1.25rem;
                border-bottom: 1px solid #e2e8f0;
                background: rgba(248, 250, 252, 0.8);
            }
            .rm-assign-panel__body {
                padding: 1.25rem;
            }
            .rm-assign-panel--locked {
                opacity: 0.55;
                pointer-events: none;
            }
            .rm-assign-helper {
                font-size: 0.75rem;
                color: #64748b;
                margin-top: 0.375rem;
            }
            .admin-prod-select2-wrap .select2-container--default .select2-selection--single {
                min-height: 42px;
                padding: 6px 10px;
                border-color: #cbd5e1;
                border-radius: 0.5rem;
            }
            .admin-prod-select2-wrap .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 28px;
                color: #232f3e;
            }
            .admin-prod-select2-wrap .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 40px;
            }
            .rm-imei-toolbar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.5rem;
                margin-bottom: 0.75rem;
            }
            .rm-imei-search {
                flex: 1;
                min-width: 12rem;
            }
            .rm-imei-list {
                max-height: 320px;
                overflow-y: auto;
                border: 1px solid #e2e8f0;
                border-radius: 0.625rem;
                background: #fff;
            }
            .rm-imei-row {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.625rem 1rem;
                border-bottom: 1px solid #f1f5f9;
                cursor: pointer;
                transition: background-color 120ms ease;
            }
            .rm-imei-row:last-child { border-bottom: none; }
            .rm-imei-row:hover { background: #f8fafc; }
            .rm-imei-row input[type="checkbox"] {
                accent-color: #f97316;
                width: 1rem;
                height: 1rem;
                flex-shrink: 0;
            }
            .rm-imei-row__serial {
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                font-size: 0.8125rem;
                font-weight: 600;
                color: #232f3e;
            }
            .rm-imei-row__model {
                font-size: 0.75rem;
                color: #64748b;
            }
            .rm-imei-row--blocked {
                opacity: 0.92;
                cursor: default;
            }
            .rm-imei-row--blocked:hover {
                background: #fff;
            }
            .rm-imei-status {
                margin-left: auto;
                flex-shrink: 0;
                font-size: 0.6875rem;
                font-weight: 700;
                padding: 0.2rem 0.5rem;
                border-radius: 9999px;
                white-space: nowrap;
            }
            .rm-imei-status--available {
                background: #dcfce7;
                color: #166534;
            }
            .rm-imei-status--distribution {
                background: #ffedd5;
                color: #c2410c;
            }
            .rm-imei-status--other {
                background: #f1f5f9;
                color: #475569;
            }
            .rm-imei-summary {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem 1rem;
                margin-bottom: 0.75rem;
                font-size: 0.75rem;
                color: #64748b;
            }
            .rm-imei-summary strong { color: #334155; }
            .rm-imei-empty {
                padding: 2rem 1rem;
                text-align: center;
                color: #94a3b8;
                font-size: 0.875rem;
            }
            .rm-assign-summary {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.75rem 1.25rem;
                padding: 0.875rem 1.25rem;
                border-radius: 0.625rem;
                background: #fff7ed;
                border: 1px solid #fed7aa;
                font-size: 0.8125rem;
                color: #9a3412;
            }
            .rm-assign-summary strong { color: #c2410c; }
        </style>
    @endpush

    <div class="admin-prod-page admin-prod-form-wide !pt-4 sm:!pt-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-6">
            <div>
                <p class="admin-prod-eyebrow">Device hierarchy</p>
                <h1 class="admin-prod-title">Assign devices to regional manager</h1>
                <p class="admin-prod-subtitle">Move warehouse IMEIs from a purchase to a regional manager. They can then distribute to team leaders and agents.</p>
            </div>
            <a href="{{ route('admin.customers.index', ['role' => 'regional_manager']) }}" class="admin-prod-back shrink-0">All users</a>
        </div>

        @if (session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="admin-prod-alert admin-prod-alert--error mb-4" role="alert">{{ session('error') }}</div>
        @endif

        <div class="rm-assign-stepper mb-6" id="rm-stepper">
            <div class="rm-assign-step rm-assign-step--active" data-step="1">
                <span class="rm-assign-step__num">1</span>
                <div>
                    <p class="rm-assign-step__label">Regional manager</p>
                    <p class="rm-assign-step__value" id="step-label-rm">Not selected</p>
                </div>
            </div>
            <div class="rm-assign-step" data-step="2">
                <span class="rm-assign-step__num">2</span>
                <div>
                    <p class="rm-assign-step__label">Purchase</p>
                    <p class="rm-assign-step__value" id="step-label-purchase">Not selected</p>
                </div>
            </div>
            <div class="rm-assign-step" data-step="3">
                <span class="rm-assign-step__num">3</span>
                <div>
                    <p class="rm-assign-step__label">Model</p>
                    <p class="rm-assign-step__value" id="step-label-model">Not selected</p>
                </div>
            </div>
            <div class="rm-assign-step" data-step="4">
                <span class="rm-assign-step__num">4</span>
                <div>
                    <p class="rm-assign-step__label">IMEIs</p>
                    <p class="rm-assign-step__value" id="step-label-imeis">0 selected</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.customers.regional-managers.assign-devices.store') }}" id="rm-assign-form">
            @csrf
            <input type="hidden" name="product_id" id="product_id" value="{{ old('product_id') }}">
            <div id="imei-hidden-inputs"></div>

            <div class="space-y-5 admin-prod-select2-wrap">
                {{-- Step 1 --}}
                <div class="rm-assign-panel admin-clay-panel !rounded-2xl overflow-hidden">
                    <div class="rm-assign-panel__head">
                        <h2 class="admin-prod-form-title text-base">1. Regional manager</h2>
                        <p class="admin-prod-form-hint !mt-0.5">Who receives custody of these devices.</p>
                    </div>
                    <div class="rm-assign-panel__body">
                        <label for="regional_manager_id" class="admin-prod-label">Regional manager</label>
                        <select id="regional_manager_id" name="regional_manager_id" class="admin-prod-select w-full" required>
                            <option value="">Choose regional manager…</option>
                            @foreach ($managers as $m)
                                <option value="{{ $m->id }}"
                                    {{ (string) old('regional_manager_id', $selectedManager) === (string) $m->id ? 'selected' : '' }}>
                                    {{ $m->name }} · {{ $m->email }}
                                </option>
                            @endforeach
                        </select>
                        @error('regional_manager_id')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Step 2 --}}
                <div class="rm-assign-panel admin-clay-panel !rounded-2xl overflow-hidden rm-assign-panel--locked" id="panel-purchase">
                    <div class="rm-assign-panel__head">
                        <h2 class="admin-prod-form-title text-base">2. Purchase</h2>
                        <p class="admin-prod-form-hint !mt-0.5">Stock purchase the IMEIs were registered on.</p>
                    </div>
                    <div class="rm-assign-panel__body">
                        <label for="purchase_id" class="admin-prod-label">Purchase / invoice</label>
                        <select id="purchase_id" name="purchase_id" class="admin-prod-select w-full" required disabled>
                            <option value="">Select regional manager first…</option>
                            @foreach ($purchases as $purchase)
                                @php
                                    $invoiceNo = $purchase->name ?? ('Purchase #'.$purchase->id);
                                    $label = 'Inv '.$invoiceNo;
                                    $models = collect();
                                    if (($purchase->lines ?? collect())->isNotEmpty()) {
                                        $models = $purchase->lines->map(fn ($line) => $line->product?->name)->filter()->unique();
                                    } elseif ($purchase->product) {
                                        $models = collect([$purchase->product->name]);
                                    }
                                    if ($models->isNotEmpty()) {
                                        $label .= ' — '.$models->implode(', ');
                                    }
                                    if ($purchase->date) {
                                        $label .= ' ('.\Carbon\Carbon::parse($purchase->date)->format('M j, Y').')';
                                    }
                                @endphp
                                <option value="{{ $purchase->id }}" data-label="{{ $label }}"
                                    {{ (string) old('purchase_id') === (string) $purchase->id ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('purchase_id')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                        <p class="rm-assign-helper">Purchases with a model on the invoice. IMEIs must be registered separately in Stock → Add product.</p>
                    </div>
                </div>

                {{-- Step 3 --}}
                <div class="rm-assign-panel admin-clay-panel !rounded-2xl overflow-hidden rm-assign-panel--locked" id="panel-model">
                    <div class="rm-assign-panel__head">
                        <h2 class="admin-prod-form-title text-base">3. Model</h2>
                        <p class="admin-prod-form-hint !mt-0.5">Catalog model on the selected purchase with devices available to assign.</p>
                    </div>
                    <div class="rm-assign-panel__body">
                        <label for="model_picker" class="admin-prod-label">Model</label>
                        <select id="model_picker" class="admin-prod-select w-full" required disabled>
                            <option value="">Select purchase first…</option>
                        </select>
                        @error('product_id')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                        <p class="rm-assign-helper" id="model-hint">Models from this purchase. Register IMEIs under Stock → Add product if none are available yet.</p>
                    </div>
                </div>

                {{-- Step 4 --}}
                <div class="rm-assign-panel admin-clay-panel !rounded-2xl overflow-hidden rm-assign-panel--locked" id="panel-imeis">
                    <div class="rm-assign-panel__head">
                        <h2 class="admin-prod-form-title text-base">4. IMEIs</h2>
                        <p class="admin-prod-form-hint !mt-0.5">Unsold devices in the admin warehouse — not already assigned in the hierarchy.</p>
                    </div>
                    <div class="rm-assign-panel__body">
                        <div class="rm-imei-toolbar">
                            <input type="search" id="imei-search" class="admin-prod-input rm-imei-search py-2 text-sm"
                                placeholder="Search IMEI…" disabled>
                            <button type="button" id="imei-select-all" class="admin-prod-btn-ghost text-xs py-2" disabled>Select all available</button>
                            <button type="button" id="imei-clear-all" class="admin-prod-btn-ghost text-xs py-2" disabled>Clear</button>
                        </div>
                        <div class="rm-imei-summary hidden" id="imei-summary"></div>
                        <div class="rm-imei-list" id="imei-list">
                            <p class="rm-imei-empty">Select a model to load IMEIs.</p>
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

            <div class="rm-assign-summary mt-6 hidden" id="assign-summary">
                Assigning <strong id="summary-count">0</strong> device(s) to <strong id="summary-rm">—</strong>
                from purchase <strong id="summary-purchase">—</strong>, model <strong id="summary-model">—</strong>.
            </div>

            <div class="admin-prod-form-footer mt-6 flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('admin.customers.index', ['role' => 'regional_manager']) }}" class="admin-prod-btn-ghost">Cancel</a>
                <button type="submit" class="admin-prod-btn-primary px-8" id="submit-btn" disabled>
                    Assign to regional manager
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            (function () {
                const MODELS_URL = @json(route('admin.customers.regional-managers.assignable-models', ['purchase' => '__ID__']));
                const IMEIS_URL = @json(route('admin.customers.regional-managers.assignable-imeis'));
                const oldProductId = @json(old('product_id'));
                const oldPurchaseId = @json(old('purchase_id'));
                const oldImeiIds = @json(array_map('strval', old('product_list_ids', [])));

                const $rm = jQuery('#regional_manager_id');
                const $purchase = jQuery('#purchase_id');
                const $model = jQuery('#model_picker');
                const $productHidden = jQuery('#product_id');
                const $imeiList = document.getElementById('imei-list');
                const $imeiSearch = document.getElementById('imei-search');
                const $hiddenInputs = document.getElementById('imei-hidden-inputs');
                const $submit = document.getElementById('submit-btn');

                let selectedImeiIds = new Set(oldImeiIds);
                let imeiRows = [];
                let imeiSummary = null;

                function statusClass(code, selectable) {
                    if (selectable) return 'rm-imei-status--available';
                    if (code === 'distribution') return 'rm-imei-status--distribution';
                    return 'rm-imei-status--other';
                }

                function renderImeiSummary() {
                    const el = document.getElementById('imei-summary');
                    if (!el || !imeiSummary) {
                        if (el) el.classList.add('hidden');
                        return;
                    }
                    el.classList.remove('hidden');
                    el.innerHTML =
                        '<span><strong>' + imeiSummary.available + '</strong> available</span>' +
                        (imeiSummary.in_distribution ? '<span><strong>' + imeiSummary.in_distribution + '</strong> in distribution</span>' : '') +
                        (imeiSummary.other ? '<span><strong>' + imeiSummary.other + '</strong> other (assigned / sold / pending)</span>' : '') +
                        '<span><strong>' + imeiSummary.total + '</strong> total on purchase</span>';
                }

                function escapeHtml(s) {
                    const d = document.createElement('div');
                    d.textContent = s;
                    return d.innerHTML;
                }

                function setStepState(step, state) {
                    const el = document.querySelector('.rm-assign-step[data-step="' + step + '"]');
                    if (!el) return;
                    el.classList.remove('rm-assign-step--active', 'rm-assign-step--done');
                    if (state) el.classList.add('rm-assign-step--' + state);
                }

                function unlockPanel(id, unlock) {
                    document.getElementById(id)?.classList.toggle('rm-assign-panel--locked', !unlock);
                }

                function rmLabel() {
                    const opt = $rm.find('option:selected');
                    if (!opt.val()) return 'Not selected';
                    return opt.text().split(' · ')[0] || opt.text();
                }

                function purchaseLabel() {
                    const opt = $purchase.find('option:selected');
                    if (!opt.val()) return 'Not selected';
                    return opt.data('label') || opt.text();
                }

                function modelLabel() {
                    const opt = $model.find('option:selected');
                    if (!opt.val()) return 'Not selected';
                    return opt.text();
                }

                function updateStepper() {
                    const hasRm = !!$rm.val();
                    const hasPurchase = !!$purchase.val();
                    const hasModel = !!$model.val();
                    const imeiCount = selectedImeiIds.size;

                    document.getElementById('step-label-rm').textContent = rmLabel();
                    document.getElementById('step-label-purchase').textContent = purchaseLabel();
                    document.getElementById('step-label-model').textContent = modelLabel();
                    document.getElementById('step-label-imeis').textContent = imeiCount + ' selected';

                    setStepState(1, hasRm ? 'done' : 'active');
                    setStepState(2, hasPurchase ? 'done' : (hasRm ? 'active' : ''));
                    setStepState(3, hasModel ? 'done' : (hasPurchase ? 'active' : ''));
                    setStepState(4, imeiCount > 0 ? 'done' : (hasModel ? 'active' : ''));

                    unlockPanel('panel-purchase', hasRm);
                    $purchase.prop('disabled', !hasRm);

                    unlockPanel('panel-model', hasPurchase);
                    unlockPanel('panel-imeis', hasModel);

                    const summary = document.getElementById('assign-summary');
                    if (imeiCount > 0 && hasRm && hasPurchase && hasModel) {
                        summary.classList.remove('hidden');
                        document.getElementById('summary-count').textContent = String(imeiCount);
                        document.getElementById('summary-rm').textContent = rmLabel();
                        document.getElementById('summary-purchase').textContent = purchaseLabel();
                        document.getElementById('summary-model').textContent = modelLabel();
                    } else {
                        summary.classList.add('hidden');
                    }

                    $submit.disabled = !(hasRm && hasPurchase && hasModel && imeiCount > 0);
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

                function renderImeiList() {
                    const q = ($imeiSearch.value || '').trim().toLowerCase();
                    $imeiList.innerHTML = '';

                    const visible = imeiRows.filter(function (row) {
                        if (!q) return true;
                        return (row.text || '').toLowerCase().includes(q);
                    });

                    if (!imeiRows.length) {
                        $imeiList.innerHTML = '<p class="rm-imei-empty">No IMEIs registered for this model on the selected purchase.</p>';
                        renderImeiSummary();
                        return;
                    }
                    if (!visible.length) {
                        $imeiList.innerHTML = '<p class="rm-imei-empty">No IMEIs match your search.</p>';
                        return;
                    }

                    visible.forEach(function (row) {
                        const selectable = row.selectable !== false;
                        const label = document.createElement('label');
                        label.className = 'rm-imei-row' + (selectable ? '' : ' rm-imei-row--blocked');
                        const checked = selectable && selectedImeiIds.has(String(row.id));
                        const serial = row.imei_number || row.text || '';
                        const modelPart = row.model || '';
                        const statusLabel = row.status_label || (selectable ? 'Available' : 'Unavailable');
                        const statusCode = row.status || (selectable ? 'available' : 'other');
                        label.innerHTML =
                            '<input type="checkbox" value="' + escapeHtml(String(row.id)) + '"' +
                            (selectable ? '' : ' disabled') +
                            (checked ? ' checked' : '') + '>' +
                            '<div class="min-w-0 flex-1"><div class="rm-imei-row__serial">' + escapeHtml(serial) + '</div>' +
                            (modelPart ? '<div class="rm-imei-row__model">' + escapeHtml(modelPart) + '</div>' : '') +
                            '</div>' +
                            '<span class="rm-imei-status ' + statusClass(statusCode, selectable) + '">' + escapeHtml(statusLabel) + '</span>';
                        const input = label.querySelector('input');
                        if (input) {
                            input.addEventListener('change', function (e) {
                                if (e.target.checked) selectedImeiIds.add(String(row.id));
                                else selectedImeiIds.delete(String(row.id));
                                syncHiddenInputs();
                                updateStepper();
                            });
                        }
                        $imeiList.appendChild(label);
                    });
                    renderImeiSummary();
                }

                function loadModels(purchaseId) {
                    $model.prop('disabled', true);
                    if ($model.data('select2')) $model.select2('destroy');
                    $model.empty().append(new Option('Loading models…', '', true, true));

                    if (!purchaseId) {
                        $model.empty().append(new Option('Select purchase first…', '', true, true));
                        $model.select2({ width: '100%' });
                        $productHidden.val('');
                        imeiRows = [];
                        selectedImeiIds.clear();
                        syncHiddenInputs();
                        renderImeiList();
                        updateStepper();
                        return;
                    }

                    fetch(MODELS_URL.replace('__ID__', encodeURIComponent(purchaseId)), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (json) {
                            const rows = (json && json.data) ? json.data : [];
                            if ($model.data('select2')) $model.select2('destroy');
                            $model.empty().append(new Option('Choose model…', '', true, false));
                            rows.forEach(function (row) {
                                const avail = row.available_imeis || 0;
                                const inDist = row.in_distribution || 0;
                                const total = row.total_registered || 0;
                                let suffix = ' (' + avail + ' available';
                                if (inDist > 0) suffix += ', ' + inDist + ' in distribution';
                                if (total > 0) suffix += ', ' + total + ' registered';
                                suffix += ')';
                                const label = row.label + (avail > 0
                                    ? suffix
                                    : (total > 0
                                        ? ' (' + (inDist > 0 ? inDist + ' in distribution, ' : '') + total + ' registered — none free to assign)'
                                        : ' (0 registered — add IMEIs in Stock → Add product)'));
                                const opt = new Option(label, row.product_id, false, false);
                                if (!row.selectable) {
                                    opt.disabled = true;
                                }
                                $model.append(opt);
                            });
                            const selectable = rows.filter(function (r) { return r.selectable; });
                            const assignable = rows.filter(function (r) { return r.assignable; });
                            $model.prop('disabled', rows.length === 0);
                            $model.select2({
                                width: '100%',
                                placeholder: rows.length
                                    ? (selectable.length ? 'Choose model…' : 'No models on this purchase')
                                    : 'No models on this purchase',
                            });

                            const hint = document.getElementById('model-hint');
                            if (hint) {
                                if (!rows.length) {
                                    hint.textContent = 'This purchase has no model on file. Edit the purchase or pick another invoice.';
                                } else if (!selectable.length) {
                                    hint.textContent = 'Model(s) are on the purchase but no IMEIs are registered yet. Open Stock → Add product, select this purchase, and register serial numbers.';
                                } else if (!assignable.length) {
                                    hint.textContent = 'IMEIs are registered but none are free to assign — check the list below for devices already in distribution or assigned elsewhere.';
                                } else {
                                    hint.textContent = assignable.length + ' model(s) with devices ready to assign.'
                                        + (assignable.length < selectable.length ? ' Select any model to see distribution / assigned IMEIs too.' : '');
                                }
                            }

                            if (oldProductId && String(oldPurchaseId) === String(purchaseId)) {
                                $model.val(String(oldProductId)).trigger('change');
                            }
                            updateStepper();
                        })
                        .catch(function () {
                            if ($model.data('select2')) $model.select2('destroy');
                            $model.empty().append(new Option('Could not load models', '', true, true));
                            $model.select2({ width: '100%' });
                        });
                }

                function loadImeis() {
                    const purchaseId = $purchase.val();
                    const productId = $model.val();
                    $productHidden.val(productId || '');

                    imeiRows = [];
                    if (!purchaseId || !productId) {
                        selectedImeiIds.clear();
                        syncHiddenInputs();
                        renderImeiList();
                        $imeiSearch.disabled = true;
                        document.getElementById('imei-select-all').disabled = true;
                        document.getElementById('imei-clear-all').disabled = true;
                        updateStepper();
                        return;
                    }

                    $imeiList.innerHTML = '<p class="rm-imei-empty">Loading IMEIs…</p>';

                    fetch(IMEIS_URL + '?purchase_id=' + encodeURIComponent(purchaseId) + '&product_id=' + encodeURIComponent(productId), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (json) {
                            imeiRows = (json && json.data) ? json.data : [];
                            imeiSummary = (json && json.summary) ? json.summary : null;
                            const selectableRows = imeiRows.filter(function (r) { return r.selectable !== false; });
                            if (oldImeiIds.length && String(oldPurchaseId) === String(purchaseId) && String(oldProductId) === String(productId)) {
                                selectedImeiIds = new Set(oldImeiIds.filter(function (id) {
                                    return selectableRows.some(function (r) { return String(r.id) === String(id); });
                                }));
                            } else {
                                selectedImeiIds.clear();
                            }
                            syncHiddenInputs();
                            renderImeiList();
                            $imeiSearch.disabled = imeiRows.length === 0;
                            document.getElementById('imei-select-all').disabled = selectableRows.length === 0;
                            document.getElementById('imei-clear-all').disabled = selectedImeiIds.size === 0;
                            updateStepper();
                        })
                        .catch(function () {
                            $imeiList.innerHTML = '<p class="rm-imei-empty">Could not load IMEIs.</p>';
                        });
                }

                $rm.select2({ width: '100%', placeholder: 'Choose regional manager…' });
                $purchase.select2({ width: '100%', placeholder: 'Choose purchase…' });
                $model.select2({ width: '100%' });

                $rm.on('change', function () {
                    updateStepper();
                });

                $purchase.on('change', function () {
                    loadModels(this.value);
                    imeiRows = [];
                    selectedImeiIds.clear();
                    syncHiddenInputs();
                    renderImeiList();
                    updateStepper();
                });

                $model.on('change', function () {
                    loadImeis();
                });

                $imeiSearch.addEventListener('input', renderImeiList);

                document.getElementById('imei-select-all').addEventListener('click', function () {
                    imeiRows.filter(function (row) { return row.selectable !== false; }).forEach(function (row) {
                        selectedImeiIds.add(String(row.id));
                    });
                    syncHiddenInputs();
                    renderImeiList();
                    updateStepper();
                });

                document.getElementById('imei-clear-all').addEventListener('click', function () {
                    selectedImeiIds.clear();
                    syncHiddenInputs();
                    renderImeiList();
                    updateStepper();
                });

                document.getElementById('rm-assign-form').addEventListener('submit', function () {
                    syncHiddenInputs();
                });

                if ($rm.val()) {
                    $purchase.prop('disabled', false);
                    unlockPanel('panel-purchase', true);
                }
                if (oldPurchaseId && $rm.val()) {
                    $purchase.val(String(oldPurchaseId)).trigger('change');
                } else {
                    updateStepper();
                }
            })();
        </script>
    @endpush
</x-admin-layout>
