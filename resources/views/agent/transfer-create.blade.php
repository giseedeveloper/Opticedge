<x-account-layout>
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <style>
            .agent-transfer-select2 .select2-container--default .select2-selection--single,
            .agent-transfer-select2 .select2-container--default .select2-selection--multiple {
                min-height: 2.5rem;
                border-color: #e2e8f0;
            }
        </style>
    @endpush

    <div class="mb-6">
        <a href="{{ route('agent.dashboard') }}" class="text-sm text-slate-600 hover:text-slate-900">&larr; Back to dashboard</a>
        <h2 class="mt-2 text-2xl font-bold text-slate-900">Transfer devices to another agent</h2>
        <p class="mt-1 text-slate-600">Select the receiving agent and your devices (IMEIs). The receiving agent must accept before the transfer completes.</p>
    </div>

    @if(session('error'))
        <p class="mb-4 rounded-lg bg-red-50 px-4 py-2 text-sm text-red-800">{{ session('error') }}</p>
    @endif

    @if($products->isEmpty())
        <p class="rounded-lg border border-slate-200 bg-white p-6 text-slate-600">You have no assignable devices to transfer.</p>
    @else
        <div class="agent-transfer-select2 max-w-3xl rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('agent.transfer.store') }}" id="agent-transfer-form">
                @csrf
                <div class="space-y-5">
                    <div>
                        <label for="to_agent_id" class="block text-sm font-medium text-slate-700">Receiving agent</label>
                        <select id="to_agent_id" name="to_agent_id" class="mt-1 block w-full rounded-md border-slate-300" required>
                            <option value="">Select agent</option>
                            @foreach($agents as $a)
                                <option value="{{ $a->id }}" {{ old('to_agent_id') == $a->id ? 'selected' : '' }}>
                                    {{ $a->name }} ({{ $a->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('to_agent_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="product_id" class="block text-sm font-medium text-slate-700">Product</label>
                        <select id="product_id" name="product_id" class="mt-1 block w-full rounded-md border-slate-300" required>
                            <option value="">Select product</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->category->name ?? '—' }} – {{ $p->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div id="imei-wrap" class="hidden">
                        <label for="imei_select" class="block text-sm font-medium text-slate-700">IMEIs to transfer</label>
                        <p class="mt-0.5 text-xs text-slate-500">Devices already in a pending transfer are hidden.</p>
                        <select id="imei_select" name="product_list_ids[]" multiple="multiple" class="mt-1 w-full"></select>
                        @error('product_list_ids')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                        @error('product_list_ids.*')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="message" class="block text-sm font-medium text-slate-700">Note to recipient (optional)</label>
                        <textarea id="message" name="message" rows="2"
                            class="mt-1 block w-full rounded-md border-slate-300 text-sm">{{ old('message') }}</textarea>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="inline-flex items-center rounded-lg bg-[#fa8900] px-5 py-2 text-sm font-medium text-white hover:bg-[#e87b00]">
                            Submit transfer request
                        </button>
                        <a href="{{ route('agent.transfers.index') }}" class="rounded-lg border border-slate-300 px-5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">My requests</a>
                    </div>
                </div>
            </form>
        </div>
    @endif

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            (function () {
                const url = @json(route('agent.transferable-imeis'));
                const $product = jQuery('#product_id');
                const $imei = jQuery('#imei_select');
                const $wrap = jQuery('#imei-wrap');
                const $toAgent = jQuery('#to_agent_id');

                $toAgent.select2({ placeholder: 'Search agent', width: '100%' });
                $product.select2({ placeholder: 'Select product', width: '100%' });

                function loadImeis(productId) {
                    if (!productId) {
                        $wrap.addClass('hidden');
                        if ($imei.data('select2')) $imei.select2('destroy');
                        $imei.empty();
                        return;
                    }
                    $wrap.removeClass('hidden');
                    fetch(url + '?product_id=' + encodeURIComponent(productId), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (json) {
                            const rows = (json && json.data) ? json.data : [];
                            if ($imei.data('select2')) $imei.select2('destroy');
                            $imei.empty();
                            rows.forEach(function (row) {
                                $imei.append(new Option(row.text, row.id, false, false));
                            });
                            $imei.select2({ placeholder: 'Select one or more IMEIs', width: '100%', closeOnSelect: false });
                        })
                        .catch(function () {
                            if ($imei.data('select2')) $imei.select2('destroy');
                            $imei.empty();
                            $imei.select2({ placeholder: 'Could not load IMEIs', width: '100%' });
                        });
                }

                $product.on('change', function () { loadImeis(this.value); });
                document.addEventListener('DOMContentLoaded', function () {
                    if ($product.val()) loadImeis($product.val());
                });
            })();
        </script>
    @endpush
</x-account-layout>
