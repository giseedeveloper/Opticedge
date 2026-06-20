<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Dealers</p>
                <h1 class="admin-prod-title">Distribution sales</h1>
                <p class="admin-prod-subtitle">Sales to dealers (buy from purchases, sell from orders).</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a href="{{ route('admin.stock.distribution.export-csv', request()->query()) }}" class="admin-prod-btn-ghost inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 16.5V4.5m0 12 3.75-3.75M12 16.5l-3.75-3.75M3.75 19.5h16.5" />
                    </svg>
                    Export CSV
                </a>
                <a href="{{ route('admin.stock.create-distribution') }}"
                    class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Add</a>
            </div>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="admin-prod-alert admin-prod-alert--error mb-4" role="alert">{{ session('error') }}</div>
        @endif
        @if(session('info'))
            <div class="admin-prod-alert mb-4 border border-slate-200 bg-slate-50 text-slate-800" role="status">{{ session('info') }}</div>
        @endif

        <x-admin-page-dashboard label="Summary (current filter)" class="mt-2">
            <dl class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <dt class="text-xs uppercase text-slate-500">Records</dt>
                    <dd class="text-lg font-semibold text-slate-900">{{ number_format($distributionDashboard['count']) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-slate-500">Total sales</dt>
                    <dd class="text-lg font-semibold text-slate-900">{{ number_format($distributionDashboard['total_sell'], 0) }} TZS</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-slate-500">Total profit</dt>
                    <dd class="text-lg font-semibold text-green-700">{{ number_format($distributionDashboard['total_profit'], 0) }} TZS</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-slate-500">Pending</dt>
                    <dd class="text-lg font-semibold text-amber-700">{{ number_format($distributionDashboard['pending']) }}</dd>
                </div>
            </dl>
        </x-admin-page-dashboard>

        <div class="mt-6 admin-clay-panel admin-prod-form-shell overflow-hidden">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Date filter</h2>
            </div>
            <div class="admin-prod-form-body">
                <form method="GET" action="{{ route('admin.stock.distribution') }}" class="flex flex-wrap gap-4 items-end">
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
                            <a href="{{ route('admin.stock.distribution') }}" class="admin-prod-btn-ghost">Clear</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="mt-6 admin-clay-panel admin-prod-form-shell overflow-hidden">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Consolidated statement (outstanding)</h2>
                <p class="admin-prod-form-hint max-w-3xl">Pick a dealer and date range (e.g. Jan–Mar). Downloads one PDF with every distribution line in that period that still has a balance due, and the total owed.</p>
            </div>
            <div class="admin-prod-form-body">
                <form method="GET" action="{{ route('admin.stock.distribution-consolidated-invoice') }}" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label for="consolidated_dealer_id" class="admin-prod-label">Dealer</label>
                        <select name="dealer_id" id="consolidated_dealer_id" required class="admin-prod-select min-w-[14rem] py-2 text-sm">
                            <option value="">— Select dealer —</option>
                            @foreach($consolidatedDealers ?? [] as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}{{ $d->business_name ? ' · '.$d->business_name : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="consolidated_date_from" class="admin-prod-label">From</label>
                        <input type="date" name="date_from" id="consolidated_date_from" required
                            value="{{ old('date_from', now()->startOfMonth()->format('Y-m-d')) }}"
                            class="admin-prod-input w-auto min-w-[10rem] py-2 text-sm">
                    </div>
                    <div>
                        <label for="consolidated_date_to" class="admin-prod-label">To</label>
                        <input type="date" name="date_to" id="consolidated_date_to" required
                            value="{{ old('date_to', now()->format('Y-m-d')) }}"
                            class="admin-prod-input w-auto min-w-[10rem] py-2 text-sm">
                    </div>
                    <button type="submit" class="admin-prod-btn-primary text-sm py-2 px-4">Download PDF</button>
                </form>
            </div>
        </div>

        <div class="mt-6 admin-clay-panel overflow-x-auto min-w-0">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush min-w-0">
                <table class="min-w-[1380px]" data-no-datatable>
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Date</th>
                            <th scope="col" class="admin-prod-th">Dealer</th>
                            <th scope="col" class="admin-prod-th">Business name</th>
                            <th scope="col" class="admin-prod-th">Seller</th>
                            <th scope="col" class="admin-prod-th">Product</th>
                            <th scope="col" class="admin-prod-th">Qty</th>
                            <th scope="col" class="admin-prod-th">Buy</th>
                            <th scope="col" class="admin-prod-th">Sell</th>
                            <th scope="col" class="admin-prod-th">Total buy</th>
                            <th scope="col" class="admin-prod-th">Total sell</th>
                            <th scope="col" class="admin-prod-th">Paid</th>
                            <th scope="col" class="admin-prod-th">Pending</th>
                            <th scope="col" class="admin-prod-th">Comm.</th>
                            <th scope="col" class="admin-prod-th">Profit</th>
                            <th scope="col" class="admin-prod-th">Status</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($distributionSales as $sale)
                            @php
                                $totalSell = (float) ($sale->total_selling_value ?? 0);
                                $paidVal = (float) ($sale->paid_amount ?? 0);
                                $eps = 0.0001;
                                $paymentStatus = $paidVal >= $totalSell - $eps ? 'paid' : ($paidVal > $eps ? 'partial' : 'pending');
                            @endphp
                            <tr>
                                <td class="text-slate-600 text-sm">{{ $sale->date }}</td>
                                <td class="font-medium text-[#232f3e]">{{ $sale->dealer_name ?? $sale->dealer?->name ?? 'N/A' }}</td>
                                <td class="text-slate-600 text-sm">{{ $sale->dealer?->business_name ?? '—' }}</td>
                                <td class="text-slate-600">{{ $sale->seller_name ?? '-' }}</td>
                                <td class="text-slate-600 text-sm">
                                    {{ $sale->product ? ($sale->product->category?->name . ' - ' . $sale->product->name) : 'N/A' }}</td>
                                <td class="font-variant-numeric">{{ $sale->quantity_sold }}</td>
                                <td class="font-variant-numeric text-sm">{{ number_format($sale->purchase_price ?? 0, 0) }}</td>
                                <td class="font-variant-numeric text-sm">{{ number_format($sale->selling_price ?? 0, 0) }}</td>
                                <td class="font-variant-numeric text-sm">{{ number_format($sale->total_purchase_value ?? 0, 0) }}</td>
                                <td class="font-variant-numeric font-bold">{{ number_format($sale->total_selling_value ?? 0, 0) }}</td>
                                <td class="font-variant-numeric">{{ number_format($sale->paid_amount ?? 0, 0) }}</td>
                                <td class="font-variant-numeric font-medium">{{ number_format($sale->balance ?? 0, 0) }}</td>
                                <td class="font-variant-numeric text-sm">{{ number_format($sale->commission ?? 0, 0) }}</td>
                                <td class="font-variant-numeric text-green-700">{{ number_format($sale->profit ?? 0, 0) }}</td>
                                <td>
                                    <span
                                        class="admin-prod-dealer-status {{ $paymentStatus === 'paid' ? 'admin-prod-dealer-status--active' : ($paymentStatus === 'partial' ? 'admin-prod-dealer-status--pending' : 'admin-prod-dealer-status--suspended') }}">
                                        {{ $paymentStatus }}
                                    </span>
                                </td>
                                <td class="admin-prod-cell-actions">
                                    <div class="admin-prod-actions flex-wrap gap-2 justify-end">
                                        <a href="{{ route('admin.stock.edit-distribution', $sale->id) }}" class="text-slate-600 hover:text-[#fa8900]"
                                            title="Edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                                stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                            </svg>
                                        </a>
                                        <form action="{{ route('admin.stock.destroy-distribution', $sale->id) }}" method="POST"
                                            onsubmit="return confirm('Delete this distribution sale?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                                    stroke="currentColor" class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="16" class="text-center text-slate-500 py-10">No distribution sales found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.partials.table-pagination', ['paginator' => $distributionSales, 'label' => 'sales'])
        </div>
    </div>
</x-admin-layout>
