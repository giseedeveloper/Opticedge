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

        @php
            $holderFilters = [
                '' => ['label' => 'All', 'count' => $available],
                'admin' => ['label' => 'Admin', 'count' => $holderCounts['admin'] ?? 0],
                'regional_manager' => ['label' => 'RM', 'count' => $holderCounts['regional_manager'] ?? 0],
                'team_leader' => ['label' => 'TL', 'count' => $holderCounts['team_leader'] ?? 0],
                'agent' => ['label' => 'Agent', 'count' => $holderCounts['agent'] ?? 0],
            ];
        @endphp
        <div class="mb-4">
            <p class="admin-prod-eyebrow mb-2">Filter by current holder</p>
            <div class="flex flex-wrap gap-2">
                @foreach($holderFilters as $key => $meta)
                    @php $active = $holder === $key; @endphp
                    <a href="{{ route('admin.stock.stocks.show', array_merge(['stock' => $stock->id], $key === '' ? [] : ['holder' => $key])) }}"
                        class="inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-sm font-semibold transition-colors
                            {{ $active ? 'border-[#fa8900] bg-[#fa8900] text-white' : 'border-slate-200 bg-white/70 text-slate-600 hover:border-[#fa8900] hover:text-[#fa8900]' }}">
                        {{ $meta['label'] }}
                        <span class="rounded-full px-2 py-0.5 text-xs {{ $active ? 'bg-white/25 text-white' : 'bg-slate-100 text-slate-500' }}">{{ number_format($meta['count']) }}</span>
                    </a>
                @endforeach
            </div>
        </div>

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
                            <th scope="col" class="admin-prod-th">Held by</th>
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
                                    @php
                                        $held = $item->currentHolder();
                                        $holderBadge = match ($held['role']) {
                                            'admin' => 'bg-slate-100 text-slate-700',
                                            'regional_manager' => 'bg-indigo-100 text-indigo-700',
                                            'team_leader' => 'bg-sky-100 text-sky-700',
                                            'agent' => 'bg-emerald-100 text-emerald-700',
                                            default => 'bg-slate-100 text-slate-400',
                                        };
                                        $holderRoleLabel = match ($held['role']) {
                                            'admin' => 'Admin',
                                            'regional_manager' => 'Regional manager',
                                            'team_leader' => 'Team leader',
                                            'agent' => 'Agent',
                                            default => null,
                                        };
                                    @endphp
                                    @if($held['role'])
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $holderBadge }}">{{ $holderRoleLabel }}</span>
                                        <span class="block text-xs text-slate-500 mt-0.5">{{ $held['label'] }}</span>
                                    @else
                                        <span class="text-slate-400 text-sm">—</span>
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
                                <td colspan="7" class="p-0">
                                    @include('admin.stock.partials.imei-full-info', ['item' => $item])
                                </td>
                            </tr>
                        </tbody>
                    @empty
                        <tbody>
                            <tr>
                                <td colspan="7" class="text-center text-slate-500 py-10">
                                    {{ $holder === '' ? 'No devices in this stock yet.' : 'No devices currently held at this level.' }}
                                </td>
                            </tr>
                        </tbody>
                    @endforelse
                </table>
            </div>
            @include('admin.partials.table-pagination', ['paginator' => $items, 'label' => 'devices'])
        </div>
    </div>
</x-admin-layout>
