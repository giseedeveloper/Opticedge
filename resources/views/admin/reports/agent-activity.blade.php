<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Sales reports</p>
                <h1 class="admin-prod-title">{{ $title }}</h1>
                <p class="admin-prod-subtitle">
                    @if($filter === 'active')
                        Agents who recorded at least one sale in the last {{ $days }} days.
                    @else
                        Agents with no recorded sales in the last {{ $days }} days.
                    @endif
                </p>
            </div>
            <a href="{{ route('admin.reports.index', array_filter(['branch_id' => $branchId])) }}" class="admin-prod-back shrink-0">Back to Reports</a>
        </div>

        <div class="mb-4 flex flex-wrap gap-2">
            <a href="{{ route('admin.reports.agent-activity', array_filter(['filter' => 'active', 'branch_id' => $branchId])) }}"
                class="{{ $filter === 'active' ? 'admin-prod-btn-primary' : 'admin-prod-btn-ghost' }} text-xs py-1.5 px-3">
                Active ({{ $days }} days)
            </a>
            <a href="{{ route('admin.reports.agent-activity', array_filter(['filter' => 'inactive', 'branch_id' => $branchId])) }}"
                class="{{ $filter === 'inactive' ? 'admin-prod-btn-primary' : 'admin-prod-btn-ghost' }} text-xs py-1.5 px-3">
                Non-active ({{ $days }} days)
            </a>
        </div>

        @if($reportBranchOptions->isNotEmpty())
            <form method="GET" action="{{ route('admin.reports.agent-activity') }}" class="flex flex-wrap items-end gap-3 mb-4">
                <input type="hidden" name="filter" value="{{ $filter }}">
                <div>
                    <label for="branch_id" class="admin-prod-label !mb-1">Branch</label>
                    <select name="branch_id" id="branch_id" class="admin-prod-select text-sm min-w-[200px] py-2" onchange="this.form.submit()">
                        <option value="">All branches</option>
                        @foreach($reportBranchOptions as $b)
                            <option value="{{ $b->id }}" @selected((string) $branchId === (string) $b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        @endif

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
                                <td class="font-semibold tabular-nums text-slate-800">{{ number_format($agent->unsold_stock) }}</td>
                                <td>
                                    @if($agent->last_sale_at)
                                        <div class="text-slate-700">{{ \Carbon\Carbon::parse($agent->last_sale_at)->format('Y-m-d') }}</div>
                                        <div class="text-xs {{ ($agent->days_since_sale ?? 0) >= $days ? 'text-slate-500' : 'text-green-700' }}">
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
