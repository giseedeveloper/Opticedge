@php
    $isPassthrough = $isPassthrough ?? false;
    $listRoute = $isPassthrough ? 'admin.stock.passthrough' : 'admin.stock.purchases';
    $exportRoute = $isPassthrough ? 'admin.stock.passthrough.export-csv' : 'admin.stock.purchases.export-csv';
    $receiptsRoute = $isPassthrough ? 'admin.stock.passthrough.receipts' : 'admin.stock.purchases.receipts';
    $createRoute = $isPassthrough ? 'admin.stock.create-passthrough' : 'admin.stock.create-purchase';
    $editRoute = $isPassthrough ? 'admin.stock.edit-passthrough' : 'admin.stock.edit-purchase';
    $destroyRoute = $isPassthrough ? 'admin.stock.destroy-passthrough' : 'admin.stock.destroy-purchase';
    $showRoute = $isPassthrough ? 'admin.stock.passthrough.show' : 'admin.stock.purchase.show';
@endphp
<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Inventory</p>
                <h1 class="admin-prod-title">{{ $isPassthrough ? 'Passthrough' : 'Purchases' }}</h1>
                <p class="admin-prod-subtitle">{{ $isPassthrough ? 'Stock passthrough entries (no IMEI tracking), payments, and sell prices.' : 'Stock purchases, payments, and sell prices.' }}</p>
            </div>
            <div class="flex flex-wrap gap-2 justify-end shrink-0">
                <a href="{{ route($exportRoute, request()->query()) }}" class="admin-prod-btn-ghost inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 16.5V4.5m0 12 3.75-3.75M12 16.5l-3.75-3.75M3.75 19.5h16.5" />
                    </svg>
                    Export CSV
                </a>
                @if(! $isPassthrough)
                <form action="{{ route('admin.stock.update-product-prices') }}" method="POST"
                    onsubmit="return confirm('This will update all existing product prices to use sell_price from their latest purchase. Continue?');"
                    class="inline">
                    @csrf
                    <button type="submit" class="admin-prod-btn-ghost inline-flex items-center gap-2 text-blue-800 border-blue-200/60">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        Update product prices
                    </button>
                </form>
                @endif
                <a href="{{ route($receiptsRoute) }}" class="admin-prod-btn-ghost inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                    All receipts
                </a>
                <a href="{{ route($createRoute) }}" class="admin-prod-btn-primary inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    {{ $isPassthrough ? 'Add passthrough' : 'Add purchase' }}
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif

        <x-admin-page-dashboard label="Summary (current filter)" class="mt-2">
            <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <dt class="text-xs uppercase text-slate-500">{{ $isPassthrough ? 'Entries' : 'Purchases' }}</dt>
                    <dd class="text-lg font-semibold text-slate-900">{{ number_format($purchaseDashboard['count']) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-slate-500">Total purchase value</dt>
                    <dd class="text-lg font-semibold text-slate-900">{{ number_format($purchaseDashboard['total_value'], 2) }} TZS</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-slate-500">Pending to pay</dt>
                    <dd class="text-lg font-semibold text-amber-700">{{ number_format($purchaseDashboard['pending_amount'], 2) }} TZS</dd>
                </div>
            </dl>
        </x-admin-page-dashboard>

        <div class="mt-6 admin-clay-panel admin-prod-form-shell overflow-hidden">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Date filter</h2>
                <p class="admin-prod-form-hint">Presets or custom range.</p>
            </div>
            <div class="admin-prod-form-body space-y-4">
                <div class="admin-prod-filter-row">
                    <a href="{{ route($listRoute, ['preset' => 'this_week']) }}"
                        class="admin-prod-filter-tab {{ ($preset ?? '') === 'this_week' ? 'admin-prod-filter-tab--active' : '' }}">This week</a>
                    <a href="{{ route($listRoute, ['preset' => 'last_week']) }}"
                        class="admin-prod-filter-tab {{ ($preset ?? '') === 'last_week' ? 'admin-prod-filter-tab--active' : '' }}">Last week</a>
                    <a href="{{ route($listRoute, ['preset' => 'last_30_days']) }}"
                        class="admin-prod-filter-tab {{ ($preset ?? '') === 'last_30_days' ? 'admin-prod-filter-tab--active' : '' }}">Last 30 days</a>
                </div>
                <form method="GET" action="{{ route($listRoute) }}" class="flex flex-wrap gap-4 items-end">
                    <div>
                        <label for="date_from" class="admin-prod-label">From date</label>
                        <input type="date" name="date_from" id="date_from"
                            value="{{ old('date_from', $dateFrom ?? request('date_from')) }}" class="admin-prod-input w-auto min-w-[10rem]">
                    </div>
                    <div>
                        <label for="date_to" class="admin-prod-label">To date</label>
                        <input type="date" name="date_to" id="date_to"
                            value="{{ old('date_to', $dateTo ?? request('date_to')) }}" class="admin-prod-input w-auto min-w-[10rem]">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="admin-prod-btn-primary">Filter</button>
                        @if(($dateFrom ?? null) || ($dateTo ?? null) || request('date_from') || request('date_to') || ($preset ?? null))
                            <a href="{{ route('admin.stock.purchases') }}" class="admin-prod-btn-ghost">Clear</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="mt-6 admin-clay-panel overflow-x-auto min-w-0">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush min-w-0">
                <table class="min-w-[1200px]" data-no-datatable>
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Invoice</th>
                            <th scope="col" class="admin-prod-th">Date</th>
                            <th scope="col" class="admin-prod-th">Branch</th>
                            <th scope="col" class="admin-prod-th">Distributor</th>
                            <th scope="col" class="admin-prod-th">Product</th>
                            <th scope="col" class="admin-prod-th">Qty</th>
                            <th scope="col" class="admin-prod-th">Unit</th>
                            <th scope="col" class="admin-prod-th">Total</th>
                            <th scope="col" class="admin-prod-th">Paid date</th>
                            <th scope="col" class="admin-prod-th">Paid</th>
                            <th scope="col" class="admin-prod-th">Pending</th>
                            <th scope="col" class="admin-prod-th">Sell</th>
                            <th scope="col" class="admin-prod-th">Status</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $purchase)
                            @php
                                $totalVal = $purchase->total_amount ?? ($purchase->quantity * $purchase->unit_price);
                                $paidVal = (float) ($purchase->paid_amount ?? 0);
                                $pendingVal = max(0, $totalVal - $paidVal);
                            @endphp
                            <tr>
                                <td class="text-slate-800">{{ $purchase->name ?? '–' }}</td>
                                <td class="text-slate-600 text-sm">{{ $purchase->date }}</td>
                                <td class="text-slate-600">{{ $purchase->branch?->name ?? '–' }}</td>
                                <td class="text-slate-600">{{ $purchase->distributor_name ?? '-' }}</td>
                                <td class="font-medium text-[#232f3e]">
                                    @if(($purchase->lines ?? collect())->isNotEmpty())
                                        {{ $purchase->lines->map(fn ($l) => $l->product?->name)->filter()->unique()->implode(', ') }}
                                    @else
                                        {{ $purchase->product?->name ?? 'N/A' }}
                                    @endif
                                </td>
                                <td class="font-variant-numeric">{{ $purchase->quantity }}</td>
                                <td class="font-variant-numeric text-sm">{{ number_format($purchase->unit_price, 2) }}</td>
                                <td class="font-variant-numeric font-bold">{{ number_format($totalVal, 2) }}</td>
                                <td class="text-sm text-slate-600">{{ $purchase->paid_date ?? '-' }}</td>
                                <td class="font-variant-numeric text-sm">{{ number_format($paidVal, 2) }}</td>
                                <td class="font-variant-numeric font-medium">{{ number_format($pendingVal, 2) }}</td>
                                <td class="font-variant-numeric text-sm">
                                    @if(($purchase->lines ?? collect())->isNotEmpty())
                                        @php
                                            $sells = $purchase->lines->pluck('sell_price')->filter(fn ($s) => $s !== null)->unique();
                                        @endphp
                                        {{ $sells->isNotEmpty() ? $sells->map(fn ($s) => number_format((float) $s, 2))->implode(', ') : '–' }}
                                    @else
                                        {{ $purchase->sell_price !== null ? number_format($purchase->sell_price, 2) : '–' }}
                                    @endif
                                </td>
                                <td>
                                    <span
                                        class="admin-prod-dealer-status {{ $purchase->payment_status === 'paid' ? 'admin-prod-dealer-status--active' : ($purchase->payment_status === 'partial' ? 'admin-prod-dealer-status--pending' : 'admin-prod-dealer-status--suspended') }}">
                                        {{ $purchase->payment_status }}
                                    </span>
                                </td>
                                <td class="admin-prod-cell-actions">
                                    <div class="admin-prod-actions flex-wrap gap-2">
                                        <a href="{{ route($showRoute, $purchase->id) }}" class="text-slate-600 hover:text-[#fa8900]"
                                            title="View">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                                stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                        </a>
                                        <a href="{{ route($editRoute, $purchase->id) }}" class="text-slate-600 hover:text-[#fa8900]"
                                            title="Edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                                stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                            </svg>
                                        </a>
                                        <form action="{{ route($destroyRoute, $purchase->id) }}" method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this {{ $isPassthrough ? 'passthrough entry' : 'purchase' }}?');" class="inline">
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
                                <td colspan="14" class="text-center text-slate-500 py-10">{{ $isPassthrough ? 'No passthrough entries found.' : 'No purchases found.' }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.partials.table-pagination', ['paginator' => $purchases, 'label' => 'purchases'])
        </div>
    </div>
</x-admin-layout>
