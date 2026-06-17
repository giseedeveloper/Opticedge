<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Agents</p>
                <h1 class="admin-prod-title">Agent sales</h1>
                <p class="admin-prod-subtitle">All sales by agents, including pending; set payment channel as needed.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a href="{{ route('admin.stock.agent-sales.export-csv', request()->query()) }}" class="admin-prod-btn-ghost inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 16.5V4.5m0 12 3.75-3.75M12 16.5l-3.75-3.75M3.75 19.5h16.5" />
                    </svg>
                    Export CSV
                </a>
                <a href="{{ route('admin.stock.create-agent-sale') }}"
                    class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Record manual sale</a>
            </div>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif
        @if(session('info'))
            <div class="admin-prod-alert admin-prod-alert--warning mb-4" role="status">{{ session('info') }}</div>
        @endif
        @if($errors->any())
            <div class="admin-prod-alert admin-prod-alert--warning mb-4" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <x-admin-page-dashboard label="Summary (current filter)" class="mt-2">
            <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <dt class="text-xs uppercase text-slate-500">Sales</dt>
                    <dd class="text-lg font-semibold text-slate-900">{{ number_format($agentSalesDashboard['count']) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-slate-500">Total selling</dt>
                    <dd class="text-lg font-semibold text-slate-900">{{ number_format($agentSalesDashboard['total_sell'], 0) }} TZS</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-slate-500">Total profit</dt>
                    <dd class="text-lg font-semibold text-green-700">{{ number_format($agentSalesDashboard['total_profit'], 0) }} TZS</dd>
                </div>
            </dl>
        </x-admin-page-dashboard>

        <div class="mt-6 admin-clay-panel admin-prod-form-shell overflow-hidden">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Date filter</h2>
            </div>
            <div class="admin-prod-form-body">
                <form method="GET" action="{{ route('admin.stock.agent-sales') }}" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label for="date_from" class="admin-prod-label">From date</label>
                        <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="admin-prod-input w-auto min-w-[10rem]">
                    </div>
                    <div>
                        <label for="date_to" class="admin-prod-label">To date</label>
                        <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="admin-prod-input w-auto min-w-[10rem]">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="admin-prod-btn-primary">Filter</button>
                        @if(request('date_from') || request('date_to'))
                            <a href="{{ route('admin.stock.agent-sales') }}" class="admin-prod-btn-ghost">Clear</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="mt-6 admin-clay-panel overflow-x-auto">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush min-w-0">
                <table class="min-w-[1200px]">
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Date</th>
                            <th scope="col" class="admin-prod-th">Customer</th>
                            <th scope="col" class="admin-prod-th">Seller</th>
                            <th scope="col" class="admin-prod-th">Product</th>
                            <th scope="col" class="admin-prod-th">Qty</th>
                            <th scope="col" class="admin-prod-th">Buy</th>
                            <th scope="col" class="admin-prod-th">Sell</th>
                            <th scope="col" class="admin-prod-th">Total buy</th>
                            <th scope="col" class="admin-prod-th">Total sell</th>
                            <th scope="col" class="admin-prod-th">Profit</th>
                            <th scope="col" class="admin-prod-th">Commission</th>
                            <th scope="col" class="admin-prod-th">Channel</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($agentSales as $sale)
                            <tr>
                                <td class="text-slate-600">{{ $sale->date }}</td>
                                <td class="font-semibold text-[#232f3e]">{{ $sale->customer_name ?? 'N/A' }}</td>
                                <td class="text-slate-600">{{ $sale->seller_name ?? $sale->teamLeader?->name ?? $sale->agent?->name ?? '-' }}</td>
                                <td class="text-slate-600 text-sm">
                                    {{ $sale->product ? (($sale->product->category?->name ?? '—') . ' – ' . $sale->product->name) : 'N/A' }}</td>
                                <td class="font-variant-numeric">{{ $sale->quantity_sold }}</td>
                                <td class="font-variant-numeric text-sm">{{ number_format($sale->purchase_price ?? 0, 0) }}</td>
                                <td class="font-variant-numeric text-sm">{{ number_format($sale->selling_price ?? 0, 0) }}</td>
                                <td class="font-variant-numeric text-sm">{{ number_format($sale->total_purchase_value ?? 0, 0) }}</td>
                                <td class="font-variant-numeric font-bold">{{ number_format($sale->total_selling_value ?? 0, 0) }}</td>
                                <td class="font-variant-numeric text-green-700">{{ number_format($sale->profit ?? 0, 0) }}</td>
                                <td class="admin-prod-cell-actions">
                                    <form action="{{ route('admin.stock.agent-sales-update-commission', $sale->id) }}" method="POST"
                                        class="inline-flex items-center gap-2 flex-wrap justify-end">
                                        @csrf
                                        @method('PATCH')
                                        <input type="number" name="commission_paid" value="{{ $sale->commission_paid ?? 0 }}" step="0.01" min="0"
                                            class="admin-prod-input w-32 py-1.5 text-sm">
                                        <button type="submit" class="admin-prod-link text-sm whitespace-nowrap">Save commission</button>
                                    </form>
                                </td>
                                <td>
                                    @if($sale->payment_option_id)
                                        <span class="text-slate-600 text-sm">{{ $sale->paymentOption?->name ?? '—' }}</span>
                                    @else
                                        <form action="{{ route('admin.stock.agent-sales-save-channel', $sale->id) }}" method="POST">
                                            @csrf
                                            <select name="payment_option_id" required onchange="this.form.submit()"
                                                class="admin-prod-select text-sm min-w-[8rem] py-1.5">
                                                <option value="">Channel…</option>
                                                @foreach($paymentOptions as $option)
                                                    <option value="{{ $option->id }}">{{ $option->name }}</option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @endif
                                </td>
                                <td class="admin-prod-cell-actions whitespace-nowrap">
                                    <a href="{{ route('admin.stock.agent-sale-invoice', $sale->id) }}" class="admin-prod-link text-xs whitespace-nowrap inline-flex">Download receipt</a>
                                    @if($sale->payment_option_id)
                                        <span class="text-slate-300 mx-1 inline-flex">|</span>
                                        <form action="{{ route('admin.stock.agent-sales-convert-to-credit', $sale->id) }}" method="POST" class="inline-block"
                                            onsubmit="return confirm('Convert this sale to agent credit? The sale amount will be removed from the current channel, moved to the default Watu channel, and a pending credit will be created.');">
                                            @csrf
                                            <button type="submit" class="admin-prod-link text-xs whitespace-nowrap">To agent credit</button>
                                        </form>
                                    @endif
                                    <span class="text-slate-300 mx-1 inline-flex">|</span>
                                    <form action="{{ route('admin.stock.destroy-agent-sale', $sale->id) }}" method="POST" class="inline-block"
                                        onsubmit="return confirm('Delete this agent sale record?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="admin-prod-link text-xs text-rose-600 whitespace-nowrap">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center text-slate-500 py-10">No agent sales found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
