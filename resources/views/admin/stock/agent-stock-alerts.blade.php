<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Inventory</p>
                <h1 class="admin-prod-title">{{ $title }}</h1>
                <p class="admin-prod-subtitle">
                    @if($filter === 'aging7')
                        Agents holding unsold stock who have not recorded a sale in the last 7 days.
                    @elseif($filter === 'aging14')
                        Agents holding unsold stock who have not recorded a sale in the last 14 days.
                    @else
                        Agents currently holding 1–{{ $threshold }} unsold device(s).
                    @endif
                </p>
            </div>
            <a href="{{ route('admin.stock.stocks') }}" class="admin-prod-back shrink-0">Back to Stocks</a>
        </div>

        <div class="mb-4 flex flex-wrap gap-2">
            <a href="{{ route('admin.stock.agent-stock-alerts', ['filter' => 'aging7']) }}"
                class="{{ $filter === 'aging7' ? 'admin-prod-btn-primary' : 'admin-prod-btn-ghost' }} text-xs py-1.5 px-3">
                Aging 7 days
            </a>
            <a href="{{ route('admin.stock.agent-stock-alerts', ['filter' => 'aging14']) }}"
                class="{{ $filter === 'aging14' ? 'admin-prod-btn-primary' : 'admin-prod-btn-ghost' }} text-xs py-1.5 px-3">
                Aging 14 days
            </a>
            <a href="{{ route('admin.stock.agent-stock-alerts', ['filter' => 'low']) }}"
                class="{{ $filter === 'low' ? 'admin-prod-btn-primary' : 'admin-prod-btn-ghost' }} text-xs py-1.5 px-3">
                Low stock
            </a>
        </div>

        <div class="admin-clay-panel overflow-hidden">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table class="min-w-[900px]" data-no-datatable>
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Agent</th>
                            <th scope="col" class="admin-prod-th">Phone</th>
                            <th scope="col" class="admin-prod-th">Branch</th>
                            <th scope="col" class="admin-prod-th">Team leader</th>
                            <th scope="col" class="admin-prod-th">Unsold stock</th>
                            <th scope="col" class="admin-prod-th">Last sale</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($agents as $agent)
                            <tr>
                                <td>
                                    <div class="font-semibold text-[#232f3e]">{{ $agent->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $agent->email }}</div>
                                </td>
                                <td class="text-slate-600">{{ $agent->phone ?? '—' }}</td>
                                <td class="text-slate-600">{{ $agent->branch ?? '—' }}</td>
                                <td class="text-slate-600">{{ $agent->team_leader ?? '—' }}</td>
                                <td class="font-semibold tabular-nums {{ $agent->unsold_stock <= 2 ? 'text-amber-700' : 'text-slate-800' }}">
                                    {{ number_format($agent->unsold_stock) }}
                                </td>
                                <td>
                                    @if($agent->last_sale_at)
                                        <div class="text-slate-700">{{ \Carbon\Carbon::parse($agent->last_sale_at)->format('Y-m-d') }}</div>
                                        <div class="text-xs {{ ($agent->days_since_sale ?? 0) >= 14 ? 'text-red-600' : 'text-slate-500' }}">
                                            {{ $agent->days_since_sale }} day(s) ago
                                        </div>
                                    @else
                                        <span class="text-slate-500">Never</span>
                                    @endif
                                </td>
                                <td class="admin-prod-cell-actions">
                                    <a href="{{ route('admin.agents.show', $agent->id) }}" class="admin-prod-link text-xs">Open agent</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-slate-500 py-10">No agents match this filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
