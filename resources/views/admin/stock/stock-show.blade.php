<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <a href="{{ route('admin.stock.stocks') }}" class="admin-prod-back mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to stocks
        </a>

        <div class="admin-prod-toolbar !mb-4">
            <div>
                <p class="admin-prod-eyebrow">Stock</p>
                <h1 class="admin-prod-title">{{ $stock->name }}</h1>
                <p class="admin-prod-subtitle">Devices (model &amp; IMEI). Click a row to expand. Limit:
                    {{ number_format($stock->stock_limit) }}.</p>
            </div>
            <div class="flex flex-wrap gap-2 shrink-0">
                <a href="{{ route('admin.stock.stock-receipts', $stock->id) }}" class="admin-prod-btn-ghost inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                    View receipts
                </a>
                @if($atLimit)
                    <a href="{{ route('admin.stock.create-purchase', ['from_stock' => $stock->id]) }}"
                        class="admin-prod-btn-primary">Add via purchases</a>
                @endif
            </div>
        </div>

        @if($atLimit)
            <div class="admin-prod-alert admin-prod-alert--warning mb-4" role="status">
                Stock at limit. Add inventory via
                <a href="{{ route('admin.stock.create-purchase', ['from_stock' => $stock->id]) }}"
                    class="admin-prod-link font-semibold">Purchases</a>.
            </div>
        @endif

        <div class="admin-clay-panel overflow-hidden">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table data-no-datatable>
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th admin-prod-th--index" aria-label="Expand"></th>
                            <th scope="col" class="admin-prod-th admin-prod-th--index">#</th>
                            <th scope="col" class="admin-prod-th">Model</th>
                            <th scope="col" class="admin-prod-th">IMEI</th>
                            <th scope="col" class="admin-prod-th">Product / category</th>
                            <th scope="col" class="admin-prod-th">In stock / sold</th>
                        </tr>
                    </thead>
                    @forelse($items as $index => $item)
                        <tbody x-data="{ open: false }" class="border-b border-slate-100/80 last:border-0">
                            <tr class="cursor-pointer hover:bg-white/50" @click="open = !open" role="button" tabindex="0"
                                @keydown.enter.prevent="open = !open" @keydown.space.prevent="open = !open">
                                <td class="text-slate-400 select-none w-10" title="Click row for full IMEI details">
                                    <span x-text="open ? '▼' : '▶'" class="inline-block w-5 text-center text-xs"></span>
                                </td>
                                <td class="text-slate-500 text-sm">{{ ($items->firstItem() ?? 1) + $index }}</td>
                                <td class="font-medium text-[#232f3e]">{{ $item->model ?? '–' }}</td>
                                <td class="font-mono text-sm" @click.stop>
                                    <a href="{{ route('admin.stock.imei-item', $item) }}" class="text-[#232f3e] hover:underline">{{ $item->imei_number ?? '–' }}</a>
                                </td>
                                <td>
                                    {{ $item->product?->name ?? '–' }}
                                    @if($item->category)
                                        <span class="text-slate-400"> / {{ $item->category->name }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->sold_at)
                                        <span class="admin-prod-status admin-prod-status--sold">Sold</span>
                                    @else
                                        <span class="admin-prod-status admin-prod-status--ok">Available</span>
                                    @endif
                                </td>
                            </tr>
                            <tr x-show="open" x-cloak class="!border-b border-slate-200/80">
                                <td colspan="6" class="p-0">
                                    @include('admin.stock.partials.imei-full-info', ['item' => $item])
                                </td>
                            </tr>
                        </tbody>
                    @empty
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center text-slate-500 py-10">No devices in this stock yet.</td>
                            </tr>
                        </tbody>
                    @endforelse
                </table>
            </div>
            @include('admin.partials.table-pagination', ['paginator' => $items, 'label' => 'devices'])
        </div>
    </div>
</x-admin-layout>
