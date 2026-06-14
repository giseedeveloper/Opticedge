<x-account-layout>
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    @endpush

    <div class="mb-6">
        <a href="{{ route('agent.dashboard') }}" class="text-sm text-[#fa8900] hover:underline">← Agent dashboard</a>
        <h2 class="mt-2 text-2xl font-bold text-slate-900">Return devices to team leader</h2>
        <p class="mt-1 text-slate-600">Submit a return request. Your team leader must accept before devices leave your custody.</p>
        <p class="mt-2"><a href="{{ route('agent.return-requests') }}" class="text-sm text-[#fa8900] hover:underline">View my return requests →</a></p>
    </div>

    @if (session('success'))
        <p class="mb-4 rounded-lg bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('success') }}</p>
    @endif
    @if (session('error'))
        <p class="mb-4 rounded-lg bg-red-50 px-4 py-2 text-sm text-red-800">{{ session('error') }}</p>
    @endif

    <div class="max-w-3xl rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('agent.return-devices.store') }}" class="space-y-6">
            @csrf
            <div>
                <label for="product_id" class="block text-sm font-medium text-slate-700">Product</label>
                <select id="product_id" name="product_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" required>
                    <option value="">Select product</option>
                    @foreach ($products as $p)
                        <option value="{{ $p->id }}" {{ (string) old('product_id') === (string) $p->id ? 'selected' : '' }}>
                            {{ $p->category->name ?? '—' }} – {{ $p->name }}
                        </option>
                    @endforeach
                </select>
                @if ($products->isEmpty())
                    <p class="text-xs text-slate-500 mt-2">No models in your custody to return. You can only return devices assigned to you by your team leader.</p>
                @endif
            </div>
            <div id="imei-wrap" class="hidden">
                <label for="imei_select" class="block text-sm font-medium text-slate-700">IMEIs to return</label>
                <select id="imei_select" name="product_list_ids[]" multiple="multiple" class="mt-1 w-full"></select>
                @error('product_list_ids')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                class="inline-flex items-center rounded-lg bg-[#fa8900] px-4 py-2 text-sm font-medium text-white hover:bg-[#e87b00]">
                Return to team leader
            </button>
        </form>
    </div>

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            (function () {
                const url = @json(route('agent.return-devices.assignable-imeis'));
                const $product = jQuery('#product_id');
                const $imei = jQuery('#imei_select');
                const $wrap = jQuery('#imei-wrap');
                function load(pid) {
                    if (!pid) { $wrap.addClass('hidden'); if ($imei.data('select2')) $imei.select2('destroy'); $imei.empty(); return; }
                    $wrap.removeClass('hidden');
                    fetch(url + '?product_id=' + pid, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                        .then(r => r.json()).then(json => {
                            const rows = json.data || [];
                            if ($imei.data('select2')) $imei.select2('destroy');
                            $imei.empty();
                            rows.forEach(row => $imei.append(new Option(row.text, row.id, false, false)));
                            $imei.select2({ width: '100%', placeholder: 'Select devices', closeOnSelect: false });
                        });
                }
                $product.on('change', function () { load(this.value); });
                $product.select2({ width: '100%' });
                if ($product.val()) load($product.val());
            })();
        </script>
    @endpush
</x-account-layout>
