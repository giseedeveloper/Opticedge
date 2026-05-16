<x-admin-layout>
    @include('admin.partials.catalog-styles')

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <style>
            .admin-prod-select2-wrap .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 1.75rem;
                padding-left: 0.15rem;
            }

            .admin-prod-select2-wrap .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 2.5rem;
            }

            .assign-type-group {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0.75rem;
            }

            .assign-type-option {
                display: flex;
                gap: 0.75rem;
                align-items: flex-start;
                padding: 0.875rem 1rem;
                border-radius: 0.625rem;
                border: 1.5px solid #e2e8f0;
                background: #fff;
                cursor: pointer;
                transition: border-color 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
            }

            .assign-type-option input[type="radio"] {
                margin-top: 0.2rem;
                accent-color: #f97316;
            }

            .assign-type-option:hover {
                border-color: #fdba74;
            }

            .assign-type-option--active {
                border-color: #f97316;
                background: #fff7ed;
                box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12);
            }

            .assign-type-option__title {
                font-weight: 600;
                color: #0f172a;
                font-size: 0.9rem;
            }

            .assign-type-option__hint {
                font-size: 0.75rem;
                color: #64748b;
                margin-top: 0.15rem;
            }
        </style>
    @endpush

    <div class="admin-prod-page admin-prod-form-wide">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-8">
            <div>
                <p class="admin-prod-eyebrow">Sales team</p>
                <h1 class="admin-prod-title">Assign products to agent</h1>
                <p class="admin-prod-subtitle">Pick how to assign: by IMEI (lock specific devices) or by total (a quantity the agent can sell from the Given tab in the app, scanning any matching IMEI at sale time).</p>
            </div>
            <a href="{{ route('admin.agents.index') }}" class="admin-prod-back shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to agents
            </a>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="admin-prod-alert admin-prod-alert--error mb-4" role="alert">{{ session('error') }}</div>
        @endif

        <div class="admin-clay-panel admin-prod-form-shell overflow-hidden admin-prod-select2-wrap">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Assignment</h2>
                <p class="admin-prod-form-hint">Agent, product, and IMEIs.</p>
            </div>
            <form method="POST" action="{{ route('admin.agents.store-assignment') }}" class="admin-prod-form-body space-y-6"
                id="assign-form">
                @csrf
                <div>
                    <label for="agent_id" class="admin-prod-label">Agent</label>
                    <select id="agent_id" name="agent_id" class="admin-prod-select" required>
                        <option value="">Select agent</option>
                        @foreach($agents as $a)
                            <option value="{{ $a->id }}"
                                {{ old('agent_id', request('agent_id')) == $a->id ? 'selected' : '' }}>
                                {{ $a->name }} ({{ $a->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('agent_id')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                @php($assignType = old('assignment_type', 'imei'))
                <input type="hidden" id="assignment_type" name="assignment_type" value="{{ $assignType === 'total' ? 'total' : 'imei' }}">

                <div class="assign-type-group" id="assignment-type-group">
                    <button type="button" class="assign-type-option {{ $assignType === 'imei' ? 'assign-type-option--active' : '' }}" data-mode="imei">
                        <span>
                            <span class="assign-type-option__title">Assign by IMEI</span>
                            <span class="assign-type-option__hint">Agent + product + IMEIs (Select2).</span>
                        </span>
                    </button>
                </div>

                <div id="tab-imei" class="{{ $assignType === 'total' ? 'hidden' : '' }} space-y-6">
                    <div>
                        <label for="product_id" class="admin-prod-label">Product</label>
                        <select id="product_id" name="product_id" class="admin-prod-select">
                            <option value="">Select product</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->category->name ?? '—' }} – {{ $p->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div id="imei-wrap" class="hidden">
                    <label for="imei_select" class="admin-prod-label">IMEIs to assign</label>
                    <p class="text-xs text-slate-500 mt-0.5 mb-2">Only unsold devices from eligible purchases are listed (paid, partial, unpaid, or purchase still has IMEI slots left; matched by catalog product or linked purchase).</p>
                    <select id="imei_select" name="product_list_ids[]" multiple="multiple" class="w-full"></select>
                    @error('product_list_ids')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                        @error('product_list_ids.*')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div id="tab-total" class="{{ $assignType === 'total' ? '' : 'hidden' }} space-y-6">
                    <div>
                        <label for="purchase_select_ui" class="admin-prod-label">Purchase &amp; model</label>
                        <select id="purchase_select_ui" class="admin-prod-select">
                            <option value="">Select purchase</option>
                            @foreach($purchases as $purchase)
                                @if(($purchase->lines ?? collect())->isNotEmpty())
                                    @foreach($purchase->lines as $line)
                                        @php($lp = $line->product)
                                        @continue(!$lp)
                                        <option value="{{ $purchase->id }}:{{ $line->product_id }}"
                                            data-model="{{ $lp->name }}"
                                            {{ (string) old('purchase_id') === (string) $purchase->id && (string) old('product_id') === (string) $line->product_id ? 'selected' : '' }}>
                                            {{ $purchase->name ?? ('Purchase #' . $purchase->id) }} — {{ $lp->name }}
                                        </option>
                                    @endforeach
                                @else
                                    <option value="{{ $purchase->id }}:{{ $purchase->product_id }}"
                                        data-model="{{ $purchase->product?->name ?? '' }}"
                                        {{ (string) old('purchase_id') === (string) $purchase->id ? 'selected' : '' }}>
                                        {{ $purchase->name ?? ('Purchase #' . $purchase->id) }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        <input type="hidden" name="purchase_id" id="purchase_id_for_total" value="{{ old('purchase_id') }}">
                        @error('purchase_id')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="purchase_model" class="admin-prod-label">Model</label>
                        <input type="text" id="purchase_model" class="admin-prod-input" readonly placeholder="Auto from selected purchase">
                    </div>
                    <div>
                        <label for="quantity" class="admin-prod-label">Total</label>
                        <input type="number" id="quantity" name="quantity" min="1" step="1"
                            value="{{ old('quantity') }}"
                            placeholder="e.g. 10"
                            class="admin-prod-input">
                        @error('quantity')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <input type="hidden" id="product_id_total" name="product_id_total" value="">
                </div>
                @error('product_id')
                    <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                @enderror
                @error('assignment_type')
                    <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                @enderror
                <div class="admin-prod-form-footer !mt-0 !pt-0 !border-0 !shadow-none">
                    <a href="{{ route('admin.agents.index') }}" class="admin-prod-btn-ghost">Cancel</a>
                    <button type="submit" class="admin-prod-btn-primary px-8">Assign</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            (function () {
                const assignableUrl = @json(route('admin.assignable-imeis'));
                const $agent = jQuery('#agent_id');
                const $product = jQuery('#product_id');
                const $purchase = jQuery('#purchase_select_ui');
                const $purchaseModel = jQuery('#purchase_model');
                const $productTotal = jQuery('#product_id_total');
                const $purchaseIdHidden = jQuery('#purchase_id_for_total');
                const $imei = jQuery('#imei_select');
                const $imeiWrap = jQuery('#imei-wrap');
                const $modeOptions = jQuery('#assignment-type-group .assign-type-option');
                const $assignmentType = jQuery('#assignment_type');
                const $tabImei = jQuery('#tab-imei');
                const $tabTotal = jQuery('#tab-total');
                const $form = jQuery('#assign-form');

                function currentMode() {
                    return $assignmentType.val() === 'total' ? 'total' : 'imei';
                }

                function applyModeUI(mode) {
                    $modeOptions.each(function () {
                        const isActive = jQuery(this).data('mode') === mode;
                        jQuery(this).toggleClass('assign-type-option--active', isActive);
                    });

                    if (mode === 'total') {
                        $tabImei.addClass('hidden');
                        $tabTotal.removeClass('hidden');
                        $product.prop('disabled', true);
                        $imei.prop('disabled', true);
                        $purchase.prop('disabled', false);
                    } else {
                        $tabTotal.addClass('hidden');
                        $tabImei.removeClass('hidden');
                        $product.prop('disabled', false);
                        $purchase.prop('disabled', true);
                        $imei.prop('disabled', false);
                        if ($product.val()) {
                            $imeiWrap.removeClass('hidden');
                        }
                    }
                }

                function updatePurchaseDerivedFields() {
                    const raw = ($purchase.val() || '').toString();
                    const parts = raw.split(':');
                    const pid = parts[0] || '';
                    const prid = parts[1] || '';
                    $purchaseIdHidden.val(pid);
                    $productTotal.val(prid);
                    const selected = $purchase.find('option:selected');
                    const model = selected.data('model') || '';
                    $purchaseModel.val(model);
                }

                function loadImeis(productId) {
                    if (!productId) {
                        $imeiWrap.addClass('hidden');
                        if ($imei.data('select2')) {
                            $imei.select2('destroy');
                        }
                        $imei.empty();
                        return;
                    }
                    if (currentMode() === 'imei') {
                        $imeiWrap.removeClass('hidden');
                    }
                    fetch(assignableUrl + '?product_id=' + encodeURIComponent(productId), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (json) {
                            const rows = (json && json.data) ? json.data : [];
                            if ($imei.data('select2')) {
                                $imei.select2('destroy');
                            }
                            $imei.empty();
                            rows.forEach(function (row) {
                                const opt = new Option(row.text, row.id, false, false);
                                $imei.append(opt);
                            });
                            $imei.select2({
                                placeholder: 'Select one or more IMEIs',
                                width: '100%',
                                closeOnSelect: false,
                            });
                        })
                        .catch(function () {
                            if ($imei.data('select2')) {
                                $imei.select2('destroy');
                            }
                            $imei.empty();
                            $imei.select2({
                                placeholder: 'Could not load IMEIs',
                                width: '100%',
                            });
                        });
                }

                $product.on('change', function () {
                    loadImeis(this.value);
                });

                $modeOptions.on('click', function () {
                    const mode = jQuery(this).data('mode') === 'total' ? 'total' : 'imei';
                    $assignmentType.val(mode);
                    applyModeUI(mode);
                });

                $purchase.on('change', updatePurchaseDerivedFields);

                $agent.select2({ width: '100%' });
                $product.select2({ width: '100%' });
                $purchase.select2({ width: '100%' });

                $form.on('submit', function () {
                    const mode = currentMode();
                    if (mode === 'total') {
                        const pid = ($productTotal.val() || '').toString();
                        let hiddenProduct = jQuery('#product_id_hidden_total_submit');
                        if (!hiddenProduct.length) {
                            hiddenProduct = jQuery('<input>', {
                                type: 'hidden',
                                id: 'product_id_hidden_total_submit',
                                name: 'product_id',
                            });
                            $form.append(hiddenProduct);
                        }
                        hiddenProduct.val(pid);
                    } else {
                        jQuery('#product_id_hidden_total_submit').remove();
                    }
                });

                document.addEventListener('DOMContentLoaded', function () {
                    updatePurchaseDerivedFields();
                    applyModeUI(currentMode());
                    if ($product.val()) {
                        loadImeis($product.val());
                    }
                });
            })();
        </script>
    @endpush
</x-admin-layout>
