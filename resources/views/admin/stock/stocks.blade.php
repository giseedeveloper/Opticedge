<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Inventory</p>
                <h1 class="admin-prod-title">Stocks</h1>
                <p class="admin-prod-subtitle">Stock buckets for the app: quantities, purchases, and status.</p>
            </div>
            <a href="{{ route('admin.stock.add-product') }}" class="admin-prod-btn-primary shrink-0">Add product (IMEI)</a>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success" role="status">{{ session('success') }}</div>
        @endif
        @if(session('info'))
            <div class="admin-prod-alert admin-prod-alert--warning" role="status">{{ session('info') }}</div>
        @endif

        <x-admin-page-dashboard label="Summary" class="mt-6">
            <dl class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div>
                    <dt class="text-xs uppercase text-slate-500">Rows</dt>
                    <dd class="text-lg font-semibold text-slate-900">{{ number_format($stockDashboard['rows']) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-slate-500">Total limit qty</dt>
                    <dd class="text-lg font-semibold text-slate-900">{{ number_format($stockDashboard['total_limit']) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-slate-500">Total added</dt>
                    <dd class="text-lg font-semibold text-slate-900">{{ number_format($stockDashboard['total_added']) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-slate-500">Complete</dt>
                    <dd class="text-lg font-semibold text-green-700">{{ number_format($stockDashboard['complete']) }}</dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-slate-500">Pending</dt>
                    <dd class="text-lg font-semibold text-amber-700">{{ number_format($stockDashboard['pending']) }}</dd>
                </div>
            </dl>

            @php
                $insights = $stockInsights ?? [
                    'inventory' => ['admin' => 0, 'regional_managers' => 0, 'team_leaders' => 0, 'agents' => 0, 'total' => 0],
                    'aging' => ['days7' => 0, 'days14' => 0],
                    'low_stock' => ['count' => 0, 'threshold' => 2],
                ];
            @endphp

            <div class="mt-5 grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="rounded-xl border border-slate-200/80 bg-white/70 p-4">
                    <div class="flex items-baseline justify-between gap-2 mb-3">
                        <h3 class="text-sm font-semibold text-slate-800">Inventory</h3>
                        <span class="text-xs text-slate-500">{{ number_format($insights['inventory']['total']) }} unsold</span>
                    </div>
                    <dl class="space-y-2">
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <dt class="text-slate-500">Admin</dt>
                            <dd class="font-semibold text-slate-900 tabular-nums">{{ number_format($insights['inventory']['admin']) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <dt class="text-slate-500">Regional managers</dt>
                            <dd class="font-semibold text-slate-900 tabular-nums">{{ number_format($insights['inventory']['regional_managers']) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <dt class="text-slate-500">Team leaders</dt>
                            <dd class="font-semibold text-slate-900 tabular-nums">{{ number_format($insights['inventory']['team_leaders']) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <dt class="text-slate-500">Agents</dt>
                            <dd class="font-semibold text-slate-900 tabular-nums">{{ number_format($insights['inventory']['agents']) }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-xl border border-slate-200/80 bg-white/70 p-4">
                    <div class="flex items-baseline justify-between gap-2 mb-3">
                        <h3 class="text-sm font-semibold text-slate-800">Aging stock</h3>
                        <span class="text-xs text-slate-500">Agents with stock, no sales</span>
                    </div>
                    <dl class="space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">7 days</dt>
                                <dd class="text-lg font-semibold text-slate-500 tabular-nums">{{ number_format($insights['aging']['days7']) }}</dd>
                            </div>
                            <a href="{{ route('admin.stock.agent-stock-alerts', ['filter' => 'aging7']) }}"
                                class="admin-prod-link text-xs shrink-0">View</a>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">14 days</dt>
                                <dd class="text-lg font-semibold text-red-600 tabular-nums">{{ number_format($insights['aging']['days14']) }}</dd>
                            </div>
                            <a href="{{ route('admin.stock.agent-stock-alerts', ['filter' => 'aging14']) }}"
                                class="admin-prod-link text-xs shrink-0">View</a>
                        </div>
                    </dl>
                </div>

                <div class="rounded-xl border border-slate-200/80 bg-white/70 p-4">
                    <div class="flex items-baseline justify-between gap-2 mb-3">
                        <h3 class="text-sm font-semibold text-slate-800">Low stock</h3>
                        <a href="{{ route('admin.stock.agent-stock-alerts', ['filter' => 'low']) }}"
                            class="admin-prod-link text-xs shrink-0">View</a>
                    </div>
                    <p class="text-xs text-slate-500 mb-2">Agents with unsold devices ≤ {{ $insights['low_stock']['threshold'] }}</p>
                    <p class="text-2xl font-semibold text-amber-700 tabular-nums">{{ number_format($insights['low_stock']['count']) }}</p>
                </div>
            </div>
        </x-admin-page-dashboard>

        <div class="mt-6 admin-clay-panel overflow-hidden">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Name</th>
                            <th scope="col" class="admin-prod-th">Stock quantity</th>
                            <th scope="col" class="admin-prod-th">Added</th>
                            <th scope="col" class="admin-prod-th">Status</th>
                            <th scope="col" class="admin-prod-th">Stock status</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stocks as $stock)
                            <tr>
                                <td class="font-semibold">
                                    @if($hasPurchases)
                                        <a href="{{ route('admin.stock.purchase.show', $stock->id) }}"
                                            class="admin-prod-link">{{ $stock->name }}</a>
                                    @else
                                        <a href="{{ route('admin.stock.stocks.show', $stock->id) }}"
                                            class="admin-prod-link">{{ $stock->name }}</a>
                                    @endif
                                    <div class="text-xs font-normal text-slate-500 mt-1">
                                        {{ number_format($stock->imei_count ?? 0) }} device(s) with IMEI
                                        <span class="text-slate-400">— expand row for assignment / sale details</span>
                                    </div>
                                </td>
                                <td class="font-variant-numeric text-slate-600">{{ number_format($stock->stock_quantity) }}</td>
                                <td class="font-variant-numeric text-slate-600">{{ number_format($stock->added) }}</td>
                                <td>
                                    @if($stock->status === 'complete')
                                        <span class="admin-prod-status admin-prod-status--ok">Complete</span>
                                    @else
                                        <span class="admin-prod-dealer-status admin-prod-dealer-status--pending">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    @if(($stock->stock_status ?? '') === 'in_stock')
                                        <span class="admin-prod-status admin-prod-status--ok">In stock</span>
                                    @elseif(($stock->stock_status ?? '') === 'pending')
                                        <span class="admin-prod-dealer-status admin-prod-dealer-status--pending">Pending</span>
                                    @else
                                        <span class="admin-prod-dealer-status admin-prod-dealer-status--inactive" style="background:#f1f5f9;color:#64748b;">Sold out</span>
                                    @endif
                                </td>
                                <td class="admin-prod-cell-actions">
                                    @if($hasPurchases)
                                        <form action="{{ route('admin.stock.destroy-purchase', $stock->id) }}" method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this purchase?');"
                                            class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="admin-prod-btn-inline admin-prod-link--danger text-xs">
                                                Delete
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('admin.stock.stock-receipts', $stock->id) }}"
                                            class="admin-prod-btn-primary text-xs py-1.5 px-3 inline-flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor" class="w-3.5 h-3.5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                            </svg>
                                            Receipts
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-slate-500 py-10">
                                    @if(isset($hasPurchases) && $hasPurchases)
                                        <p class="font-medium text-slate-700 mb-2">No stocks found, but you have
                                            {{ $purchasesCount }} purchase(s) in the system.</p>
                                        <p class="text-sm mt-2 text-slate-600">View
                                            <a href="{{ route('admin.stock.purchases') }}" class="admin-prod-link">Purchases</a>.
                                        </p>
                                        @if(isset($distributors) && $distributors->count() > 0)
                                            <p class="text-xs mt-2 text-slate-500">Your purchases are from:
                                                {{ $distributors->implode(', ') }}</p>
                                        @endif
                                    @else
                                        <p>No stocks found in the database.</p>
                                        <p class="text-xs mt-2 text-slate-400">This page shows Stock records, not Purchases.
                                            <a href="{{ route('admin.stock.purchases') }}" class="admin-prod-link">Purchases page</a>.
                                        </p>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
