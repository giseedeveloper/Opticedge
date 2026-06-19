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
            .dist-imei-tabs {
                display: flex;
                gap: 0.375rem;
                margin-bottom: 0.875rem;
                padding: 0.25rem;
                border-radius: 0.625rem;
                background: #f1f5f9;
                width: fit-content;
            }
            .dist-imei-tab {
                border: none;
                border-radius: 0.5rem;
                padding: 0.4375rem 0.875rem;
                font-size: 0.75rem;
                font-weight: 700;
                color: #64748b;
                background: transparent;
                cursor: pointer;
                transition: background-color 120ms ease, color 120ms ease, box-shadow 120ms ease;
            }
            .dist-imei-tab:hover { color: #334155; }
            .dist-imei-tab--active {
                background: #fff;
                color: #ea580c;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
            }
            .dist-imei-tab-panel.hidden { display: none; }
            .dist-imei-toolbar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.5rem;
                margin-bottom: 0.75rem;
            }
            .dist-imei-search {
                flex: 1;
                min-width: 12rem;
            }
            .dist-imei-list {
                max-height: min(28rem, 52vh);
                overflow-y: auto;
                border: 1px solid #e2e8f0;
                border-radius: 0.625rem;
                background: #fff;
            }
            .dist-imei-row {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.625rem 1rem;
                border-bottom: 1px solid #f1f5f9;
                cursor: pointer;
                transition: background-color 120ms ease;
            }
            .dist-imei-row:last-child { border-bottom: none; }
            .dist-imei-row:hover { background: #f8fafc; }
            .dist-imei-row input[type="checkbox"] {
                accent-color: #f97316;
                width: 1rem;
                height: 1rem;
                flex-shrink: 0;
            }
            .dist-imei-row__serial {
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                font-size: 0.8125rem;
                font-weight: 600;
                color: #232f3e;
            }
            .dist-imei-row__model {
                font-size: 0.75rem;
                color: #64748b;
            }
            .dist-imei-row--blocked {
                opacity: 0.92;
                cursor: default;
            }
            .dist-imei-row--blocked:hover { background: #fff; }
            .dist-imei-status {
                margin-left: auto;
                flex-shrink: 0;
                font-size: 0.6875rem;
                font-weight: 700;
                padding: 0.2rem 0.5rem;
                border-radius: 9999px;
                white-space: nowrap;
            }
            .dist-imei-status--available { background: #dcfce7; color: #166534; }
            .dist-imei-status--distribution { background: #ffedd5; color: #c2410c; }
            .dist-imei-status--other { background: #f1f5f9; color: #475569; }
            .dist-imei-summary {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem 1rem;
                margin-bottom: 0.75rem;
                font-size: 0.75rem;
                color: #64748b;
            }
            .dist-imei-summary strong { color: #334155; }
            .dist-imei-empty {
                padding: 2rem 1rem;
                text-align: center;
                color: #94a3b8;
                font-size: 0.875rem;
            }
            .dist-scanner-layout {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr);
                gap: 1rem;
                align-items: stretch;
            }
            @media (max-width: 900px) {
                .dist-scanner-layout { grid-template-columns: 1fr; }
            }
            .dist-scanner-input-col,
            .dist-scanner-results-col {
                display: flex;
                flex-direction: column;
                min-height: 0;
            }
            .dist-scanner-input-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                margin-bottom: 0.5rem;
            }
            .dist-scanner-input-head__label {
                font-size: 0.75rem;
                font-weight: 700;
                color: #334155;
            }
            .dist-scanner-input-head__count {
                font-size: 0.6875rem;
                font-weight: 700;
                color: #64748b;
                font-variant-numeric: tabular-nums;
            }
            .dist-scanner-input-head__count--warn { color: #dc2626; }
            .dist-imei-scanner-input {
                width: 100%;
                min-height: 11rem;
                max-height: 22rem;
                resize: vertical;
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                font-size: 0.8125rem;
                line-height: 1.45;
            }
            .dist-scanner-results-col {
                border: 1px solid #e2e8f0;
                border-radius: 0.75rem;
                background: #f8fafc;
                overflow: hidden;
            }
            .dist-scanner-stats {
                display: flex;
                flex-wrap: wrap;
                gap: 0.375rem;
                padding: 0.625rem 0.75rem;
                border-bottom: 1px solid #e2e8f0;
                background: #fff;
            }
            .dist-scanner-stats.hidden { display: none; }
            .dist-scanner-stat {
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
                padding: 0.2rem 0.55rem;
                border-radius: 9999px;
                font-size: 0.6875rem;
                font-weight: 700;
                font-variant-numeric: tabular-nums;
            }
            .dist-scanner-stat--total { background: #f1f5f9; color: #475569; }
            .dist-scanner-stat--valid { background: #dcfce7; color: #166534; }
            .dist-scanner-stat--invalid { background: #fee2e2; color: #b91c1c; }
            .dist-scanner-stat--selected { background: #ffedd5; color: #c2410c; }
            .dist-scanner-filters {
                display: flex;
                flex-wrap: wrap;
                gap: 0.375rem;
                padding: 0.5rem 0.75rem;
                border-bottom: 1px solid #e2e8f0;
                background: #fff;
            }
            .dist-scanner-filters.hidden { display: none; }
            .dist-scanner-filter {
                border: 1px solid #e2e8f0;
                border-radius: 9999px;
                padding: 0.2rem 0.625rem;
                font-size: 0.6875rem;
                font-weight: 700;
                color: #64748b;
                background: #fff;
                cursor: pointer;
            }
            .dist-scanner-filter--active {
                border-color: #fdba74;
                background: #fff7ed;
                color: #c2410c;
            }
            .dist-imei-scanner-results {
                flex: 1;
                min-height: 16rem;
                max-height: min(28rem, 52vh);
                overflow-y: auto;
                overflow-x: hidden;
                background: #fff;
                contain: content;
            }
            .dist-imei-scanner-line {
                display: grid;
                grid-template-columns: 2.25rem minmax(0, 1fr) auto;
                align-items: center;
                gap: 0.5rem 0.75rem;
                padding: 0.375rem 0.75rem;
                border-bottom: 1px solid #f1f5f9;
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                font-size: 0.75rem;
            }
            .dist-imei-scanner-line:last-child { border-bottom: none; }
            .dist-imei-scanner-line__num {
                font-size: 0.625rem;
                font-weight: 700;
                color: #94a3b8;
                text-align: right;
                font-variant-numeric: tabular-nums;
            }
            .dist-imei-scanner-line__imei {
                min-width: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .dist-imei-scanner-line--valid { background: #f0fdf4; color: #166534; }
            .dist-imei-scanner-line--invalid { background: #fef2f2; color: #b91c1c; }
            .dist-imei-scanner-line--skipped { background: #fffbeb; color: #b45309; }
            .dist-imei-scanner-line__status {
                flex-shrink: 0;
                max-width: 11rem;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                font-size: 0.625rem;
                font-weight: 700;
                padding: 0.15rem 0.4rem;
                border-radius: 9999px;
                text-align: right;
            }
            .dist-imei-scanner-line--valid .dist-imei-scanner-line__status { background: #dcfce7; color: #166534; }
            .dist-imei-scanner-line--invalid .dist-imei-scanner-line__status { background: #fee2e2; color: #b91c1c; }
            .dist-imei-scanner-line--skipped .dist-imei-scanner-line__status { background: #fef3c7; color: #b45309; }
            .dist-imei-scanner-empty,
            .dist-imei-scanner-limit {
                padding: 1.5rem 1rem;
                text-align: center;
                color: #94a3b8;
                font-size: 0.8125rem;
            }
            .dist-imei-scanner-limit {
                color: #b45309;
                background: #fffbeb;
                border-top: 1px solid #fde68a;
                font-weight: 600;
            }
            .dist-sale-summary {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.75rem 1.25rem;
                padding: 0.875rem 1rem;
                border-radius: 0.625rem;
                background: #fff7ed;
                border: 1px solid #fed7aa;
                font-size: 0.8125rem;
                color: #9a3412;
            }
            .dist-sale-summary strong { color: #c2410c; }
            .dist-sale-summary.hidden { display: none; }
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
                <p class="admin-prod-subtitle">Pick IMEIs from one or more purchases — each line keeps that purchase’s buy and sell prices.</p>
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
                    <label for="purchase_id" class="admin-prod-label">Purchase <span class="text-slate-400 font-normal">(for next selection)</span></label>
                    <select id="purchase_id" class="admin-prod-select">
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
                    @error('lines.*.purchase_id')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                    <p class="helper-text">Choose a purchase, add models and IMEIs to the sale, then switch purchase to add more lines from another invoice.</p>
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
                            <p class="helper-text mt-2" id="product_picker_hint">Select a purchase above — only models on that purchase appear here. Pick a model to choose IMEIs below.</p>
                        </div>

                        <div id="dist-imei-panel" class="hidden p-4 border-b border-slate-200/60 bg-slate-50/30">
                            <p class="helper-text mb-3">Unsold devices on this purchase — not already assigned in the hierarchy or on another line in this sale.</p>
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                <div class="dist-imei-tabs !mb-0" role="tablist" aria-label="IMEI selection mode">
                                    <button type="button" class="dist-imei-tab dist-imei-tab--active" data-dist-imei-tab="list" role="tab" aria-selected="true">List</button>
                                    <button type="button" class="dist-imei-tab" data-dist-imei-tab="scanner" role="tab" aria-selected="false">Scanner</button>
                                </div>
                                <button type="button" id="dist-imei-clear-all" class="admin-prod-btn-ghost text-xs py-2" disabled>Clear</button>
                            </div>

                            <div id="dist-imei-tab-list" class="dist-imei-tab-panel" role="tabpanel">
                                <div class="dist-imei-toolbar">
                                    <input type="search" id="dist-imei-search" class="admin-prod-input dist-imei-search py-2 text-sm"
                                        placeholder="Search IMEI…" disabled>
                                    <button type="button" id="dist-imei-select-all" class="admin-prod-btn-ghost text-xs py-2" disabled>Select all available</button>
                                </div>
                                <div class="dist-imei-summary hidden" id="dist-imei-summary"></div>
                                <div class="dist-imei-list" id="dist-imei-list">
                                    <p class="dist-imei-empty">Select a model to load IMEIs.</p>
                                </div>
                            </div>

                            <div id="dist-imei-tab-scanner" class="dist-imei-tab-panel hidden" role="tabpanel">
                                <div class="dist-scanner-layout">
                                    <div class="dist-scanner-input-col">
                                        <div class="dist-scanner-input-head">
                                            <span class="dist-scanner-input-head__label">Paste or scan IMEIs</span>
                                            <span class="dist-scanner-input-head__count" id="dist-imei-scanner-line-count">0 / 500 unique lines</span>
                                        </div>
                                        <textarea id="dist-imei-scanner-input" class="admin-prod-input dist-imei-scanner-input py-2 px-3"
                                            placeholder="One IMEI per line — paste up to 500 at once…" disabled spellcheck="false"></textarea>
                                        <p class="helper-text">Each line is one IMEI. Green = available on this purchase and model. Red = not found or not available. Only the first 500 unique lines are processed.</p>
                                    </div>
                                    <div class="dist-scanner-results-col">
                                        <div class="dist-scanner-stats hidden" id="dist-imei-scanner-stats">
                                            <span class="dist-scanner-stat dist-scanner-stat--total" id="dist-scanner-stat-total">0 scanned</span>
                                            <span class="dist-scanner-stat dist-scanner-stat--valid" id="dist-scanner-stat-valid">0 valid</span>
                                            <span class="dist-scanner-stat dist-scanner-stat--invalid" id="dist-scanner-stat-invalid">0 invalid</span>
                                            <span class="dist-scanner-stat dist-scanner-stat--selected" id="dist-scanner-stat-selected">0 selected</span>
                                        </div>
                                        <div class="dist-scanner-filters hidden" id="dist-imei-scanner-filters">
                                            <button type="button" class="dist-scanner-filter dist-scanner-filter--active" data-scanner-filter="all">All</button>
                                            <button type="button" class="dist-scanner-filter" data-scanner-filter="valid">Valid</button>
                                            <button type="button" class="dist-scanner-filter" data-scanner-filter="invalid">Invalid</button>
                                        </div>
                                        <div class="dist-imei-scanner-results" id="dist-imei-scanner-results">
                                            <p class="dist-imei-scanner-empty">Scan or paste IMEIs to validate in bulk.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="dist-sale-summary hidden mt-4" id="dist-sale-summary">
                                Adding <strong id="dist-summary-count">0</strong> device(s) for model <strong id="dist-summary-model">—</strong> to this sale.
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <button type="button" id="dist-add-line-btn" class="admin-prod-btn-primary text-sm py-2 px-5" disabled>Add to sale</button>
                                <button type="button" id="dist-cancel-imei-btn" class="admin-prod-btn-ghost text-sm py-2">Cancel</button>
                            </div>
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
                    <p class="text-xs text-slate-600 mt-2">Sum of each line: selected IMEIs × unit sell price from that line’s purchase.</p>
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
            const PURCHASE_LABELS = @json(
                $purchases->mapWithKeys(fn ($p) => [
                    (string) $p->id => 'Inv no. ' . ($p->name ?? ('Purchase #' . $p->id)),
                ])
            );
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

            const imeiPanel = document.getElementById('dist-imei-panel');
            const imeiListEl = document.getElementById('dist-imei-list');
            const imeiSearchEl = document.getElementById('dist-imei-search');
            const imeiScannerInput = document.getElementById('dist-imei-scanner-input');
            const imeiScannerResults = document.getElementById('dist-imei-scanner-results');
            const addLineBtn = document.getElementById('dist-add-line-btn');

            let activeProductId = null;
            let activePurchaseId = null;
            let editingLineRow = null;
            let selectedImeiIds = new Set();
            let imeiRows = [];
            let imeiSummary = null;
            let activeImeiTab = 'list';
            let imeiLookup = new Map();
            const SCANNER_MAX = 500;
            let scannerFilter = 'all';
            let scannerParsedCache = [];
            let scannerDebounceTimer = null;
            let scannerOverLimit = false;

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

            function lineKey(productId, purchaseId) {
                return String(productId) + ':' + String(purchaseId);
            }

            function purchaseLabel(purchaseId) {
                const idStr = String(purchaseId || '');
                return PURCHASE_LABELS[idStr] || ('Purchase #' + idStr);
            }

            function selectedLineKeys(excludeRow) {
                return [...tbody.querySelectorAll('tr[data-line-row]')]
                    .filter(tr => tr !== excludeRow)
                    .map(tr => lineKey(tr.getAttribute('data-product-id'), tr.getAttribute('data-purchase-id')));
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
                    tr.querySelector('.line-purchase-id').name = 'lines[' + idx + '][purchase_id]';
                    tr.querySelectorAll('.line-imei-id').forEach(inp => {
                        inp.name = 'lines[' + idx + '][product_list_ids][]';
                    });
                    const countEl = tr.querySelector('.line-imei-count');
                    const n = lineQty(tr);
                    if (countEl) countEl.textContent = n + ' device' + (n === 1 ? '' : 's');
                });
                noLinesHint.style.display = rows.length ? 'none' : 'block';
            }

            function getRegisterModelPricing() {
                if (!registerModelSelect) {
                    return { sell: 0, buy: 0 };
                }
                const opt = registerModelSelect.options[registerModelSelect.selectedIndex];
                if (!opt || !opt.value) {
                    return { sell: 0, buy: 0 };
                }
                return {
                    sell: parseFloat(opt.dataset.sellPrice || '0') || 0,
                    buy: parseFloat(opt.dataset.unitPrice || '0') || 0,
                };
            }

            function ensureProductMeta(productId, purchaseId, meta) {
                const idStr = String(productId);
                const purchaseIdStr = String(purchaseId || getPurchaseId() || '');
                const sell = parseFloat(meta.sell_price || meta.sell || 0) || 0;
                const buy = parseFloat(meta.unit_price || meta.buy || 0) || 0;
                const payload = {
                    id: parseInt(idStr, 10),
                    label: meta.label || ('Model #' + idStr),
                    unit_price: buy,
                    sell_price: sell,
                    suggest: sell,
                    buy_price: buy,
                };
                if (purchaseIdStr) {
                    PRODUCT_META[lineKey(idStr, purchaseIdStr)] = payload;
                }
                PRODUCT_META[idStr] = payload;
            }

            function autoAddRegisteredItemsToSale(productId, purchaseId, items) {
                const key = lineKey(productId, purchaseId);
                let existingRow = null;
                tbody.querySelectorAll('tr[data-line-row]').forEach(function (tr) {
                    if (lineKey(tr.getAttribute('data-product-id'), tr.getAttribute('data-purchase-id')) === key) {
                        existingRow = tr;
                    }
                });

                let merged = items.slice();
                if (existingRow) {
                    const existing = [...existingRow.querySelectorAll('.line-imei-id')].map(function (inp) {
                        return { id: inp.value, text: inp.dataset.label || inp.value };
                    });
                    const seen = new Set(existing.map(function (i) { return String(i.id); }));
                    items.forEach(function (item) {
                        if (!seen.has(String(item.id))) {
                            existing.push(item);
                            seen.add(String(item.id));
                        }
                    });
                    merged = existing;
                }

                addLine(productId, purchaseId, merged, existingRow);
            }

            function recalcGrandTotal() {
                let saleSum = 0;
                let saleDeviceCount = 0;
                tbody.querySelectorAll('tr[data-line-row]').forEach(tr => {
                    const lt = lineTotalRow(tr);
                    saleSum += lt;
                    saleDeviceCount += lineQty(tr);
                    const cell = tr.querySelector('.line-line-total');
                    if (cell) cell.textContent = formatCurrency(lt) + ' TZS';
                });

                let displaySum = saleSum;
                const pendingCount = registerImeiTa ? countParsedImeis(registerImeiTa.value) : 0;
                const registerPricing = getRegisterModelPricing();
                if (pendingCount > 0 && registerPricing.sell > 0) {
                    displaySum += pendingCount * registerPricing.sell;
                }

                totalDisplay.textContent = formatCurrency(displaySum) + ' TZS';
                totalHidden.value = saleSum;

                const paid = parseMoney(paidInput);
                if (displaySum <= 0) {
                    paymentStatus.textContent = 'Optional — partial payments are split across lines by each line’s share of the total.';
                    paymentStatus.style.color = '#64748b';
                } else if (paid <= 0) {
                    paymentStatus.textContent = 'No upfront payment — record later from Edit sale.';
                    paymentStatus.style.color = '#64748b';
                } else if (Math.abs(paid - saleSum) < 0.01) {
                    paymentStatus.textContent = '✓ Matches grand total (split across ' + tbody.querySelectorAll('tr[data-line-row]').length + ' line(s))';
                    paymentStatus.style.color = '#10b981';
                } else if (paid > saleSum * 1.01) {
                    paymentStatus.textContent = '⚠️ Paid exceeds grand total';
                    paymentStatus.style.color = '#ef4444';
                } else {
                    paymentStatus.textContent = 'Partial payment • Remaining ' + formatCurrency(saleSum - paid) + ' TZS (allocated proportionally per line)';
                    paymentStatus.style.color = '#f59e0b';
                }

                submitBtn.disabled = saleSum <= 0 || saleDeviceCount === 0 || document.getElementById('dealer_id').value === '';
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

            function addLine(productId, purchaseId, imeis, existingRow) {
                const idStr = String(productId);
                const purchaseIdStr = String(purchaseId);
                if (!imeis || !imeis.length || !purchaseIdStr) return;

                const meta = PRODUCT_META[lineKey(idStr, purchaseIdStr)] || PRODUCT_META[idStr];
                if (!meta) return;

                const rowKey = lineKey(idStr, purchaseIdStr);
                if (!existingRow && selectedLineKeys(null).includes(rowKey)) {
                    alert('This model on this purchase is already on the sale. Use “Change IMEIs” on that row, or remove it first.');
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
                    tr.setAttribute('data-purchase-id', purchaseIdStr);
                    tr.setAttribute('data-sell-price', String(sellPrice));
                    tr.setAttribute('data-buy-price', String(buyPrice));

                    tr.innerHTML =
                        '<td class="px-4 py-3 align-top">' +
                            '<div class="font-medium text-[#232f3e]">' + escapeHtml(meta.label) + '</div>' +
                            '<div class="text-xs text-slate-500 mt-0.5">' + escapeHtml(purchaseLabel(purchaseIdStr)) + '</div>' +
                            '<div class="text-xs text-slate-500 mt-0.5"><span class="line-imei-count">' + imeis.length + ' device' + (imeis.length === 1 ? '' : 's') + '</span> · ' +
                            '<button type="button" class="text-[#fa8900] font-semibold hover:underline change-imeis">Change IMEIs</button></div>' +
                            '<input type="hidden" class="line-product-id" name="lines[' + idx + '][product_id]" value="' + idStr + '">' +
                            '<input type="hidden" class="line-purchase-id" name="lines[' + idx + '][purchase_id]" value="' + purchaseIdStr + '">' +
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
                        if (tr === editingLineRow) {
                            resetImeiPanel();
                            if (window.jQuery) jQuery('#product_picker').val(null).trigger('change');
                        }
                        tr.remove();
                        renumberLines();
                        recalcGrandTotal();
                    });
                    tr.querySelector('.change-imeis').addEventListener('click', function () {
                        onModelPicked(idStr, purchaseIdStr, tr);
                    });
                } else {
                    const oldWrap = tr.querySelector('.line-imei-inputs');
                    if (oldWrap) oldWrap.remove();
                    tr.querySelector('td').appendChild(buildImeiInputsContainer(imeis));
                }

                renumberLines();
                recalcGrandTotal();
            }

            function normalizeImei(value) {
                return String(value || '').trim().replace(/\s+/g, '');
            }

            function buildImeiLookup() {
                imeiLookup = new Map();
                imeiRows.forEach(function (row) {
                    const key = normalizeImei(row.imei_number || row.text || '');
                    if (key && !imeiLookup.has(key)) {
                        imeiLookup.set(key, row);
                    }
                });
            }

            function isRowSelectable(row) {
                if (row.selectable === false) return false;
                const used = usedImeiIds(editingLineRow);
                return !used.has(String(row.id));
            }

            function listableImeiRows() {
                return imeiRows.filter(function (row) { return isRowSelectable(row); });
            }

            function renderImeiSummary() {
                const el = document.getElementById('dist-imei-summary');
                const listableCount = listableImeiRows().length;
                if (!el) return;
                if (!imeiSummary && listableCount === 0) {
                    el.classList.add('hidden');
                    return;
                }
                el.classList.remove('hidden');
                el.innerHTML = '<span><strong>' + listableCount + '</strong> available to add</span>' +
                    (imeiSummary && imeiSummary.total > listableCount
                        ? '<span><strong>' + (imeiSummary.total - listableCount) + '</strong> hidden (assigned / sold / on another line)</span>'
                        : '');
            }

            function renderImeiList() {
                const q = (imeiSearchEl.value || '').trim().toLowerCase();
                imeiListEl.innerHTML = '';

                const listable = listableImeiRows();
                const visible = listable.filter(function (row) {
                    if (!q) return true;
                    return (row.text || row.imei_number || '').toLowerCase().includes(q);
                });

                if (!imeiRows.length) {
                    imeiListEl.innerHTML = '<p class="dist-imei-empty">No IMEIs registered for this model on the selected purchase.</p>';
                    renderImeiSummary();
                    return;
                }
                if (!listable.length) {
                    imeiListEl.innerHTML = '<p class="dist-imei-empty">No available IMEIs for this model — all registered devices are assigned, sold, or already on this sale.</p>';
                    renderImeiSummary();
                    return;
                }
                if (!visible.length) {
                    imeiListEl.innerHTML = '<p class="dist-imei-empty">No IMEIs match your search.</p>';
                    return;
                }

                visible.forEach(function (row) {
                    const label = document.createElement('label');
                    label.className = 'dist-imei-row';
                    const checked = selectedImeiIds.has(String(row.id));
                    const serial = row.imei_number || row.text || '';
                    const modelPart = row.model || '';
                    label.innerHTML =
                        '<input type="checkbox" value="' + escapeHtml(String(row.id)) + '"' +
                        (checked ? ' checked' : '') + '>' +
                        '<div class="min-w-0 flex-1"><div class="dist-imei-row__serial">' + escapeHtml(serial) + '</div>' +
                        (modelPart ? '<div class="dist-imei-row__model">' + escapeHtml(modelPart) + '</div>' : '') +
                        '</div>' +
                        '<span class="dist-imei-status dist-imei-status--available">Available</span>';
                    const input = label.querySelector('input');
                    if (input) {
                        input.addEventListener('change', function (e) {
                            if (e.target.checked) {
                                if (selectedImeiIds.size >= SCANNER_MAX) return;
                                selectedImeiIds.add(String(row.id));
                            } else {
                                selectedImeiIds.delete(String(row.id));
                            }
                            updateImeiPanelSummary();
                            if (activeImeiTab === 'scanner') {
                                updateScannerStatsFromCache();
                            }
                        });
                    }
                    imeiListEl.appendChild(label);
                });
                renderImeiSummary();
            }

            function updateImeiPanelSummary() {
                const summary = document.getElementById('dist-sale-summary');
                const count = selectedImeiIds.size;
                const meta = activeProductId
                    ? (PRODUCT_META[lineKey(activeProductId, activePurchaseId || getPurchaseId())] || PRODUCT_META[String(activeProductId)])
                    : null;
                if (!summary) return;
                if (count > 0 && meta) {
                    summary.classList.remove('hidden');
                    document.getElementById('dist-summary-count').textContent = String(count);
                    document.getElementById('dist-summary-model').textContent = meta.label || '—';
                } else {
                    summary.classList.add('hidden');
                }
                addLineBtn.disabled = count === 0;
            }

            function setImeiInputsEnabled(enabled) {
                imeiSearchEl.disabled = !enabled;
                imeiScannerInput.disabled = !enabled;
                document.getElementById('dist-imei-select-all').disabled = !enabled || listableImeiRows().length === 0;
                const clearBtn = document.getElementById('dist-imei-clear-all');
                if (clearBtn) {
                    clearBtn.disabled = !enabled || (selectedImeiIds.size === 0 && !(imeiScannerInput.value || '').trim());
                }
            }

            function evaluateScannerLine(rawLine) {
                const imei = normalizeImei(rawLine);
                if (!imei) return null;

                const row = imeiLookup.get(imei);
                if (!row) {
                    return { imei: imei, valid: false, status: 'Not on this purchase / model' };
                }

                if (!isRowSelectable(row)) {
                    const status = row.selectable === false
                        ? (row.status_label || 'Not available')
                        : 'On another line in this sale';
                    return { imei: imei, valid: false, row: row, status: status };
                }

                return { imei: imei, valid: true, row: row, status: 'Available' };
            }

            function parseScannerLines() {
                const lines = (imeiScannerInput.value || '').split(/\r?\n/);
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

                    parsed.push(Object.assign({}, result, {
                        kind: result.valid ? 'valid' : 'invalid',
                        lineIndex: lineIndex + 1,
                    }));
                });

                return {
                    parsed: parsed,
                    overLimit: overLimit,
                    uniqueCount: uniqueCount,
                    stats: {
                        total: parsed.length,
                        valid: parsed.filter(function (item) { return item.kind === 'valid'; }).length,
                        invalid: parsed.filter(function (item) { return item.kind === 'invalid' || item.kind === 'skipped'; }).length,
                    },
                };
            }

            function updateScannerLineCount(uniqueCount, overLimit) {
                const el = document.getElementById('dist-imei-scanner-line-count');
                if (!el) return;
                el.textContent = uniqueCount + ' / ' + SCANNER_MAX + ' unique lines';
                el.classList.toggle('dist-scanner-input-head__count--warn', overLimit || uniqueCount > SCANNER_MAX);
            }

            function updateScannerStats(stats, selectedCount) {
                const wrap = document.getElementById('dist-imei-scanner-stats');
                const filters = document.getElementById('dist-imei-scanner-filters');
                if (!wrap || !filters) return;

                if (!stats.total) {
                    wrap.classList.add('hidden');
                    filters.classList.add('hidden');
                    return;
                }

                wrap.classList.remove('hidden');
                filters.classList.remove('hidden');
                document.getElementById('dist-scanner-stat-total').textContent = stats.total + ' scanned';
                document.getElementById('dist-scanner-stat-valid').textContent = stats.valid + ' valid';
                document.getElementById('dist-scanner-stat-invalid').textContent = stats.invalid + ' invalid';
                document.getElementById('dist-scanner-stat-selected').textContent = selectedCount + ' / ' + SCANNER_MAX + ' selected';
            }

            function updateScannerStatsFromCache() {
                if (!scannerParsedCache.length) {
                    const bundle = parseScannerLines();
                    updateScannerStats(bundle.stats, selectedImeiIds.size);
                    return;
                }
                const stats = {
                    total: scannerParsedCache.length,
                    valid: scannerParsedCache.filter(function (item) { return item.kind === 'valid'; }).length,
                    invalid: scannerParsedCache.filter(function (item) { return item.kind === 'invalid' || item.kind === 'skipped'; }).length,
                };
                updateScannerStats(stats, selectedImeiIds.size);
            }

            function setScannerFilter(filter) {
                scannerFilter = filter === 'valid' || filter === 'invalid' ? filter : 'all';
                document.querySelectorAll('.dist-scanner-filter').forEach(function (btn) {
                    btn.classList.toggle('dist-scanner-filter--active', btn.getAttribute('data-scanner-filter') === scannerFilter);
                });
                renderScannerResults();
            }

            function setImeiTab(tab) {
                activeImeiTab = tab === 'scanner' ? 'scanner' : 'list';
                document.querySelectorAll('.dist-imei-tab').forEach(function (btn) {
                    const isActive = btn.getAttribute('data-dist-imei-tab') === activeImeiTab;
                    btn.classList.toggle('dist-imei-tab--active', isActive);
                    btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });
                document.getElementById('dist-imei-tab-list').classList.toggle('hidden', activeImeiTab !== 'list');
                document.getElementById('dist-imei-tab-scanner').classList.toggle('hidden', activeImeiTab !== 'scanner');
                if (activeImeiTab === 'scanner') {
                    syncScannerFromSelection();
                } else {
                    renderImeiList();
                }
            }

            function syncScannerFromSelection() {
                if (!imeiRows.length || selectedImeiIds.size === 0) return;

                const selectedNumbers = imeiRows
                    .filter(function (row) { return selectedImeiIds.has(String(row.id)); })
                    .map(function (row) { return row.imei_number || ''; })
                    .filter(Boolean);

                if (!selectedNumbers.length) return;

                const currentLines = (imeiScannerInput.value || '')
                    .split(/\r?\n/)
                    .map(normalizeImei)
                    .filter(Boolean);

                if (currentLines.length === 0) {
                    imeiScannerInput.value = selectedNumbers.slice(0, SCANNER_MAX).join('\n');
                    scannerParsedCache = [];
                    applyScannerSelection();
                    renderScannerResults();
                }
            }

            function applyScannerSelection() {
                const bundle = parseScannerLines();
                scannerParsedCache = bundle.parsed;
                scannerOverLimit = bundle.overLimit;
                const nextSelected = new Set();

                bundle.parsed.forEach(function (item) {
                    if (item.kind !== 'valid' || !item.row) return;
                    if (nextSelected.size >= SCANNER_MAX) return;
                    nextSelected.add(String(item.row.id));
                });

                selectedImeiIds = nextSelected;
                updateImeiPanelSummary();
                updateScannerLineCount(bundle.uniqueCount, bundle.overLimit);
                updateScannerStats(bundle.stats, selectedImeiIds.size);
                const clearBtn = document.getElementById('dist-imei-clear-all');
                if (clearBtn) {
                    clearBtn.disabled = selectedImeiIds.size === 0 && !(imeiScannerInput.value || '').trim();
                }
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
                    imeiScannerResults.innerHTML = '<p class="dist-imei-scanner-empty">Scan or paste IMEIs to validate in bulk.</p>';
                    updateScannerStats({ total: 0, valid: 0, invalid: 0 }, 0);
                    return;
                }

                const visible = parsed.filter(function (item) {
                    if (scannerFilter === 'valid') return item.kind === 'valid';
                    if (scannerFilter === 'invalid') return item.kind === 'invalid' || item.kind === 'skipped';
                    return true;
                });

                if (!visible.length) {
                    imeiScannerResults.innerHTML = '<p class="dist-imei-scanner-empty">No IMEIs match this filter.</p>';
                    return;
                }

                const html = visible.map(function (item) {
                    const cls = item.kind === 'valid'
                        ? 'dist-imei-scanner-line--valid'
                        : (item.kind === 'skipped' ? 'dist-imei-scanner-line--skipped' : 'dist-imei-scanner-line--invalid');
                    return '<div class="dist-imei-scanner-line ' + cls + '">' +
                        '<span class="dist-imei-scanner-line__num">' + (item.lineIndex || '') + '</span>' +
                        '<span class="dist-imei-scanner-line__imei" title="' + escapeHtml(item.imei) + '">' + escapeHtml(item.imei) + '</span>' +
                        '<span class="dist-imei-scanner-line__status" title="' + escapeHtml(item.status) + '">' + escapeHtml(item.status) + '</span>' +
                        '</div>';
                }).join('');

                const limitNote = scannerOverLimit
                    ? '<p class="dist-imei-scanner-limit">Only the first ' + SCANNER_MAX + ' unique IMEIs are checked and selected. Remove extra lines to continue.</p>'
                    : '';

                imeiScannerResults.innerHTML = html + limitNote;
            }

            function queueScannerUpdate() {
                if (scannerDebounceTimer) clearTimeout(scannerDebounceTimer);
                scannerDebounceTimer = setTimeout(function () {
                    scannerParsedCache = [];
                    applyScannerSelection();
                    renderScannerResults();
                }, 120);
            }

            function resetScannerPanel() {
                imeiScannerInput.value = '';
                imeiScannerInput.disabled = true;
                scannerParsedCache = [];
                scannerOverLimit = false;
                scannerFilter = 'all';
                updateScannerLineCount(0, false);
                updateScannerStats({ total: 0, valid: 0, invalid: 0 }, 0);
                document.querySelectorAll('.dist-scanner-filter').forEach(function (btn) {
                    btn.classList.toggle('dist-scanner-filter--active', btn.getAttribute('data-scanner-filter') === 'all');
                });
                imeiScannerResults.innerHTML = '<p class="dist-imei-scanner-empty">Scan or paste IMEIs to validate in bulk.</p>';
            }

            function resetImeiPanel() {
                activeProductId = null;
                activePurchaseId = null;
                editingLineRow = null;
                selectedImeiIds.clear();
                imeiRows = [];
                imeiSummary = null;
                imeiPanel.classList.add('hidden');
                imeiListEl.innerHTML = '<p class="dist-imei-empty">Select a model to load IMEIs.</p>';
                imeiSearchEl.value = '';
                resetScannerPanel();
                setImeiInputsEnabled(false);
                updateImeiPanelSummary();
            }

            function loadImeisForModel(productId, purchaseId) {
                if (!purchaseId || !productId) {
                    resetImeiPanel();
                    return;
                }

                imeiListEl.innerHTML = '<p class="dist-imei-empty">Loading IMEIs…</p>';
                resetScannerPanel();

                fetch(ASSIGNABLE_IMEIS_URL + '?product_id=' + encodeURIComponent(productId) + '&purchase_id=' + encodeURIComponent(purchaseId), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        imeiRows = (json && json.data) ? json.data : [];
                        imeiSummary = (json && json.summary) ? json.summary : null;
                        buildImeiLookup();

                        if (editingLineRow) {
                            selectedImeiIds = new Set(
                                [...editingLineRow.querySelectorAll('.line-imei-id')].map(function (i) { return i.value; })
                            );
                        } else {
                            selectedImeiIds.clear();
                        }

                        renderImeiList();
                        imeiScannerInput.disabled = imeiRows.length === 0;
                        setImeiInputsEnabled(imeiRows.length > 0);
                        if (activeImeiTab === 'scanner' && selectedImeiIds.size > 0) {
                            syncScannerFromSelection();
                            renderScannerResults();
                        }
                        updateImeiPanelSummary();
                    })
                    .catch(function () {
                        imeiListEl.innerHTML = '<p class="dist-imei-empty">Could not load IMEIs.</p>';
                    });
            }

            function onModelPicked(productId, purchaseId, editingRow) {
                const idStr = String(productId);
                const purchaseIdStr = String(purchaseId || getPurchaseId());
                const meta = PRODUCT_META[lineKey(idStr, purchaseIdStr)] || PRODUCT_META[idStr];
                if (!meta) return;

                if (!purchaseIdStr) {
                    alert('Select a purchase first.');
                    return;
                }

                const rowKey = lineKey(idStr, purchaseIdStr);
                if (!editingRow && selectedLineKeys(null).includes(rowKey)) {
                    alert('This model on this purchase is already on the sale. Use “Change IMEIs” on that row, or remove it first.');
                    if (window.jQuery) jQuery('#product_picker').val(null).trigger('change');
                    return;
                }

                activeProductId = idStr;
                activePurchaseId = purchaseIdStr;
                editingLineRow = editingRow || null;
                imeiPanel.classList.remove('hidden');

                if (window.jQuery && editingRow) {
                    jQuery('#product_picker').val(idStr).trigger('change.select2');
                }

                loadImeisForModel(idStr, purchaseIdStr);
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
                        onModelPicked(id, getPurchaseId(), null);
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
                            const available = row.available_imeis || 0;
                            const registered = row.total_registered || 0;
                            const metaPayload = {
                                id: row.product_id,
                                label: row.label,
                                unit_price: row.unit_price || 0,
                                sell_price: row.sell_price || row.suggest || 0,
                                suggest: row.sell_price || row.suggest || 0,
                                available_imeis: available,
                            };
                            PRODUCT_META[lineKey(id, purchaseId)] = metaPayload;
                            PRODUCT_META[id] = metaPayload;
                            const opt = new Option(row.picker_label || row.label, id, false, false);
                            jQuery(opt).attr('data-suggest', row.suggest || 0);
                            $pick.append(opt);
                        });
                        $pick.prop('disabled', rows.length === 0);
                        if (hint) {
                            if (!rows.length) {
                                hint.textContent = 'No models on this purchase. Add models via the purchase or register IMEIs in the Register IMEIs tab.';
                            } else {
                                const withImeis = rows.filter(function (r) { return (r.available_imeis || 0) > 0; }).length;
                                const noImeis = rows.length - withImeis;
                                hint.textContent = rows.length + ' model(s) on this purchase — '
                                    + (withImeis > 0 ? withImeis + ' ready to sell' : 'none ready to sell')
                                    + (noImeis > 0 ? ', ' + noImeis + ' need IMEIs registered first' : '')
                                    + '. Pick a model to choose IMEIs below.';
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
                resetImeiPanel();
            }

            document.querySelectorAll('.dist-imei-tab').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    setImeiTab(btn.getAttribute('data-dist-imei-tab'));
                });
            });

            imeiSearchEl.addEventListener('input', renderImeiList);
            imeiScannerInput.addEventListener('input', queueScannerUpdate);

            document.querySelectorAll('.dist-scanner-filter').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    setScannerFilter(btn.getAttribute('data-scanner-filter'));
                });
            });

            document.getElementById('dist-imei-select-all').addEventListener('click', function () {
                selectedImeiIds.clear();
                let count = 0;
                imeiRows.filter(function (row) { return isRowSelectable(row); }).forEach(function (row) {
                    if (count >= SCANNER_MAX) return;
                    selectedImeiIds.add(String(row.id));
                    count += 1;
                });
                renderImeiList();
                if (activeImeiTab === 'scanner') {
                    syncScannerFromSelection();
                    renderScannerResults();
                }
                updateImeiPanelSummary();
            });

            document.getElementById('dist-imei-clear-all').addEventListener('click', function () {
                selectedImeiIds.clear();
                imeiScannerInput.value = '';
                scannerParsedCache = [];
                renderImeiList();
                resetScannerPanel();
                setImeiInputsEnabled(imeiRows.length > 0);
                updateImeiPanelSummary();
            });

            addLineBtn.addEventListener('click', function () {
                const purchaseId = getPurchaseId();
                if (!activeProductId || !purchaseId || selectedImeiIds.size === 0) {
                    alert('Select a purchase and at least one IMEI.');
                    return;
                }
                const picked = imeiRows
                    .filter(function (r) { return selectedImeiIds.has(String(r.id)); })
                    .map(function (r) { return { id: r.id, text: r.text || r.imei_number }; });
                addLine(activeProductId, purchaseId, picked, editingLineRow);
                resetImeiPanel();
                if (window.jQuery) jQuery('#product_picker').val(null).trigger('change');
            });

            document.getElementById('dist-cancel-imei-btn').addEventListener('click', function () {
                resetImeiPanel();
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
                recalcGrandTotal();
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
                        opt.dataset.unitPrice = String(m.unit_price || 0);
                        opt.dataset.sellPrice = String(m.sell_price || 0);
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
                        unit_price: m.unit_price || 0,
                        sell_price: m.sell_price || 0,
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
                recalcGrandTotal();
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

            registerModelSelect.addEventListener('change', function () {
                updateRegisterModelSlotsLabel();
                recalcGrandTotal();
            });
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

                        const productId = String(res.json.catalog_product_id || catalogProductId);
                        const selectedLabel = registerModelSelect.options[registerModelSelect.selectedIndex]
                            ? registerModelSelect.options[registerModelSelect.selectedIndex].text
                            : '';
                        ensureProductMeta(productId, getPurchaseId(), {
                            label: selectedLabel,
                            unit_price: res.json.unit_price,
                            sell_price: res.json.sell_price,
                        });

                        if (res.json.items && res.json.items.length) {
                            autoAddRegisteredItemsToSale(productId, getPurchaseId(), res.json.items);
                        }

                        switchDistTab('sale');
                        if (res.json.models) {
                            applyPurchaseRegistrationRows(res.json.models);
                        } else {
                            loadPurchaseRegistrationMeta();
                        }
                        syncProductPickerForPurchase();
                        recalcGrandTotal();
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
                resetImeiPanel();
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
