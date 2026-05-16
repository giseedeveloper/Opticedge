<x-team-leader-layout title="Team inventory & IMEIs">
    <div class="admin-prod-page !pt-4 sm:!pt-6">
        <div class="admin-prod-toolbar !mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="admin-prod-eyebrow">Portal</p>
                <h1 class="admin-prod-title">Team inventory &amp; IMEIs</h1>
                <p class="admin-prod-subtitle">Every device assigned to your agents: IMEI, product, branch, status, and
                    assignment dates.</p>
            </div>
            <a href="{{ route('team-leader.dashboard') }}" class="admin-prod-btn-ghost shrink-0">Back to overview</a>
        </div>

        <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="admin-clay-panel p-4">
                <p class="text-xs font-bold uppercase text-slate-500">Total IMEIs</p>
                <p class="mt-1 text-2xl font-extrabold text-[#232f3e]">{{ $summary['total'] }}</p>
            </div>
            <div class="admin-clay-panel p-4">
                <p class="text-xs font-bold uppercase text-slate-500">In field (unsold)</p>
                <p class="mt-1 text-2xl font-extrabold text-[#232f3e]">{{ $summary['unsold'] }}</p>
            </div>
            <div class="admin-clay-panel p-4">
                <p class="text-xs font-bold uppercase text-slate-500">Sold</p>
                <p class="mt-1 text-2xl font-extrabold text-[#232f3e]">{{ $summary['sold'] }}</p>
            </div>
            <div class="admin-clay-panel p-4">
                <p class="text-xs font-bold uppercase text-slate-500">Pending admin</p>
                <p class="mt-1 text-2xl font-extrabold text-[#232f3e]">{{ $summary['pending'] }}</p>
                <p class="mt-0.5 text-[10px] text-slate-500">Unsold + pending sale record</p>
            </div>
        </div>

        <div class="mb-6 admin-clay-panel overflow-hidden p-4 sm:p-5">
            <form method="GET" action="{{ route('team-leader.team-inventory') }}"
                class="flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-end">
                <div class="min-w-[140px] flex-1">
                    <label for="filter_agent" class="admin-prod-label">Agent</label>
                    <select name="agent_id" id="filter_agent" class="admin-prod-input w-full">
                        <option value="">All agents</option>
                        @foreach ($agents as $a)
                            <option value="{{ $a->id }}" @selected((string) request('agent_id') === (string) $a->id)>{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-[140px] flex-1">
                    <label for="filter_product" class="admin-prod-label">Product</label>
                    <select name="product_id" id="filter_product" class="admin-prod-input w-full">
                        <option value="">All products</option>
                        @foreach ($productChoices as $p)
                            <option value="{{ $p->id }}" @selected((string) request('product_id') === (string) $p->id)>
                                {{ $p->name ?? 'Product #'.$p->id }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-[120px]">
                    <label for="filter_status" class="admin-prod-label">Status</label>
                    <select name="status" id="filter_status" class="admin-prod-input w-full">
                        <option value="all" @selected($filterStatus === 'all')>All</option>
                        <option value="unsold" @selected($filterStatus === 'unsold')>In field</option>
                        <option value="pending" @selected($filterStatus === 'pending')>Pending admin</option>
                        <option value="sold" @selected($filterStatus === 'sold')>Sold</option>
                    </select>
                </div>
                <div class="min-w-[160px] flex-[2]">
                    <label for="filter_q" class="admin-prod-label">IMEI search</label>
                    <input type="search" name="q" id="filter_q" value="{{ request('q') }}" placeholder="Digits or partial…"
                        class="admin-prod-input w-full">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="admin-prod-btn-primary">Apply</button>
                    <a href="{{ route('team-leader.team-inventory') }}" class="admin-prod-btn-ghost">Reset</a>
                </div>
            </form>
        </div>

        <div class="admin-clay-panel overflow-hidden">
            <div class="admin-prod-form-head border-b border-white/60">
                <h2 class="admin-prod-form-title">IMEI register</h2>
                <p class="admin-prod-form-hint">Read-only. Stock and assignments are managed in admin.</p>
            </div>
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table class="min-w-[1100px]">
                    <thead>
                        <tr>
                            <th class="admin-prod-th">IMEI</th>
                            <th class="admin-prod-th">Model / variant</th>
                            <th class="admin-prod-th">Product</th>
                            <th class="admin-prod-th">Category</th>
                            <th class="admin-prod-th">Branch</th>
                            <th class="admin-prod-th">Agent</th>
                            <th class="admin-prod-th">Assigned</th>
                            <th class="admin-prod-th">Status</th>
                            <th class="admin-prod-th">Sold at</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $assignment)
                            @php
                                $pl = $assignment->productListItem;
                            @endphp
                            <tr>
                                <td class="font-mono text-sm font-semibold text-[#232f3e]">{{ $pl?->imei_number ?? '—' }}
                                </td>
                                <td class="text-slate-600">{{ $pl?->model ?? '—' }}</td>
                                <td class="text-slate-700">{{ $pl?->product?->name ?? '—' }}</td>
                                <td class="text-slate-600">{{ $pl?->category?->name ?? '—' }}</td>
                                <td class="text-slate-600">{{ $pl?->branch?->name ?? '—' }}</td>
                                <td class="text-slate-800">
                                    <span class="font-medium">{{ $assignment->agent?->name ?? '—' }}</span>
                                    <span class="block text-xs text-slate-500">{{ $assignment->agent?->email }}</span>
                                </td>
                                <td class="text-sm text-slate-600 whitespace-nowrap">
                                    {{ $assignment->created_at?->format('M j, Y') ?? '—' }}</td>
                                <td>
                                    @if (!$pl)
                                        <span class="text-slate-400">—</span>
                                    @elseif ($pl->sold_at)
                                        <span class="admin-prod-user-status admin-prod-user-status--inactive">Sold</span>
                                    @elseif ($pl->pending_sale_id)
                                        <span
                                            class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-900">Pending</span>
                                    @else
                                        <span class="admin-prod-user-status admin-prod-user-status--active">In field</span>
                                    @endif
                                </td>
                                <td class="text-sm text-slate-600 whitespace-nowrap">
                                    {{ $pl?->sold_at?->format('M j, Y H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center text-slate-500">No IMEI assignments for your team
                                    yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($rows->hasPages())
                <div class="admin-prod-pagination border-t border-white/50 px-4 py-3">{{ $rows->links() }}</div>
            @endif
        </div>
    </div>
</x-team-leader-layout>
