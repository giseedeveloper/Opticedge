<x-admin-layout>
    @include('admin.partials.catalog-styles')

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    @endpush

    <div class="admin-prod-page admin-prod-form-wide">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-8">
            <div>
                <p class="admin-prod-eyebrow">Sales hierarchy</p>
                <h1 class="admin-prod-title">Assign devices to regional manager</h1>
                <p class="admin-prod-subtitle">Select IMEIs from warehouse stock. Regional managers assign down to team leaders, then agents.</p>
            </div>
            <a href="{{ route('admin.agents.index') }}" class="admin-prod-back shrink-0">Back to agents</a>
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
                <p class="admin-prod-form-hint">Regional manager, product, and IMEIs.</p>
            </div>
            <form method="POST" action="{{ route('admin.agents.store-assignment') }}" class="admin-prod-form-body space-y-6">
                @csrf
                <div>
                    <label for="regional_manager_id" class="admin-prod-label">Regional manager</label>
                    <select id="regional_manager_id" name="regional_manager_id" class="admin-prod-select" required>
                        <option value="">Select regional manager</option>
                        @foreach($regionalManagers as $rm)
                            <option value="{{ $rm->id }}"
                                {{ old('regional_manager_id', request('regional_manager_id')) == $rm->id ? 'selected' : '' }}>
                                {{ $rm->name }} ({{ $rm->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('regional_manager_id')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="product_id" class="admin-prod-label">Product</label>
                    <select id="product_id" name="product_id" class="admin-prod-select" required>
                        <option value="">Select product</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->category->name ?? '—' }} – {{ $p->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                <div id="imei-wrap" class="hidden">
                    <label for="imei_select" class="admin-prod-label">IMEIs to assign</label>
                    <p class="text-xs text-slate-500 mt-0.5 mb-2">Unsold warehouse devices not yet in the hierarchy.</p>
                    <select id="imei_select" name="product_list_ids[]" multiple="multiple" class="w-full"></select>
                    @error('product_list_ids')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                    @error('product_list_ids.*')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
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
                const $product = jQuery('#product_id');
                const $imei = jQuery('#imei_select');
                const $imeiWrap = jQuery('#imei-wrap');

                function loadImeis(productId) {
                    if (!productId) {
                        $imeiWrap.addClass('hidden');
                        if ($imei.data('select2')) $imei.select2('destroy');
                        $imei.empty();
                        return;
                    }
                    $imeiWrap.removeClass('hidden');
                    fetch(assignableUrl + '?product_id=' + encodeURIComponent(productId), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    })
                        .then(r => r.json())
                        .then(json => {
                            const rows = (json && json.data) ? json.data : [];
                            if ($imei.data('select2')) $imei.select2('destroy');
                            $imei.empty();
                            rows.forEach(row => $imei.append(new Option(row.text, row.id, false, false)));
                            $imei.select2({ placeholder: 'Select one or more IMEIs', width: '100%', closeOnSelect: false });
                        });
                }

                $product.on('change', function () { loadImeis(this.value); });
                jQuery('#regional_manager_id').select2({ width: '100%' });
                $product.select2({ width: '100%' });
                document.addEventListener('DOMContentLoaded', function () {
                    if ($product.val()) loadImeis($product.val());
                });
            })();
        </script>
    @endpush
</x-admin-layout>
