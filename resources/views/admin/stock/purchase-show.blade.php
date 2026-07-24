<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        @php
            $holder = $holder ?? '';
            $holderCounts = $holderCounts ?? [];
            $available = $available ?? $items->total();
            $holderFilters = [
                '' => ['label' => 'All', 'count' => $available],
                'admin' => ['label' => 'Admin', 'count' => $holderCounts['admin'] ?? 0],
                'regional_manager' => ['label' => 'RM', 'count' => $holderCounts['regional_manager'] ?? 0],
                'team_leader' => ['label' => 'TL', 'count' => $holderCounts['team_leader'] ?? 0],
                'agent' => ['label' => 'Agent', 'count' => $holderCounts['agent'] ?? 0],
            ];
        @endphp
        <a href="{{ route('admin.stock.stocks', $holder !== '' ? ['holder' => $holder] : []) }}" class="admin-prod-back mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to stocks
        </a>

        <div class="admin-prod-toolbar !mb-6">
            <div>
                <p class="admin-prod-eyebrow">Purchase</p>
                <h1 class="admin-prod-title">{{ $purchase->name ?? 'Purchase #' . $purchase->id }}</h1>
                <p class="admin-prod-subtitle">Model, category and IMEI. Click a row to expand details.</p>
                @if(($purchase->lines ?? collect())->isNotEmpty())
                    <div class="mt-3 text-sm text-slate-600 space-y-1">
                        @foreach($purchase->lines as $line)
                            @php $lp = $line->product; @endphp
                            <div>
                                <span class="font-medium text-slate-800">{{ $lp?->name ?? '—' }}</span>
                                <span class="text-slate-500">· qty {{ $line->quantity }}</span>
                                <span class="text-slate-500">· unit {{ number_format((float) $line->unit_price, 2) }}</span>
                                @if($line->sell_price !== null)
                                    <span class="text-slate-500">· sell {{ number_format((float) $line->sell_price, 2) }}</span>
                                @endif
                                <span class="text-slate-500">· slots left {{ (int) $line->limit_remaining }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
                @if(!empty($purchase->note))
                    <p class="mt-3 text-sm text-slate-700 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2"><span class="font-medium text-slate-900">Note:</span> {{ $purchase->note }}</p>
                @endif
            </div>
        </div>
        @if($errors->any())
            <div class="admin-prod-alert admin-prod-alert--warning mb-4" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="mb-4">
            <p class="admin-prod-eyebrow mb-2">Filter by current holder</p>
            <div class="flex flex-wrap gap-2">
                @foreach($holderFilters as $key => $meta)
                    @php $active = $holder === $key; @endphp
                    <a href="{{ route('admin.stock.purchase.show', array_merge(['id' => $purchase->id], $key === '' ? [] : ['holder' => $key])) }}"
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
                            <th scope="col" class="admin-prod-th">Category</th>
                            <th scope="col" class="admin-prod-th">IMEI</th>
                            <th scope="col" class="admin-prod-th">Held by</th>
                            <th scope="col" class="admin-prod-th">Status</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Action</th>
                        </tr>
                    </thead>
                    @forelse($items as $index => $item)
                        <tbody x-data="{ open: false }" class="border-b border-slate-100/80 last:border-0">
                            <tr class="cursor-pointer hover:bg-white/50" @click="open = !open" role="button" tabindex="0"
                                @keydown.enter.prevent="open = !open" @keydown.space.prevent="open = !open">
                                <td class="text-slate-400 select-none w-10">
                                    <span x-text="open ? '▼' : '▶'" class="inline-block w-5 text-center text-xs"></span>
                                </td>
                                <td class="text-slate-500 text-sm">{{ ($items->firstItem() ?? 1) + $index }}</td>
                                <td class="font-medium text-[#232f3e]">{{ $item->model ?? '–' }}</td>
                                <td>{{ $item->category?->name ?? '–' }}</td>
                                <td class="font-mono text-sm" @click.stop>
                                    <a href="{{ route('admin.stock.imei-item', $item) }}" class="text-[#232f3e] hover:underline">{{ $item->imei_number ?? '–' }}</a>
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
                                        <span class="admin-prod-status admin-prod-status--sold">
                                            {{ $item->agent_sale_id || $item->agent_credit_id ? 'Installed' : 'Sold' }}
                                        </span>
                                    @else
                                        <span class="admin-prod-status admin-prod-status--ok">Available</span>
                                    @endif
                                </td>
                                <td class="admin-prod-cell-actions" @click.stop>
                                    @if($item->sold_at || $item->agent_sale_id || $item->agent_credit_id || $item->pending_sale_id || $item->agentProductListAssignment)
                                        <span class="text-slate-400 text-xs">Locked</span>
                                    @else
                                        <form method="POST" action="{{ route('admin.stock.purchase.item.destroy', ['purchase' => $purchase->id, 'productListItem' => $item->id]) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="admin-prod-link text-xs text-rose-600"
                                                onclick="return confirm('Delete this IMEI from this purchase?');">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                            <tr x-show="open" x-cloak class="!border-b border-slate-200/80">
                                <td colspan="8" class="p-0">
                                    @include('admin.stock.partials.imei-full-info', ['item' => $item])
                                </td>
                            </tr>
                        </tbody>
                    @empty
                        <tbody>
                            <tr>
                                <td colspan="8" class="text-center text-slate-500 py-10">
                                    {{ $holder === '' ? 'No items for this purchase yet.' : 'No devices currently held at this level.' }}
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
