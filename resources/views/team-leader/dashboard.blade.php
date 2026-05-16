<x-team-leader-layout title="Team overview">
    <div class="admin-prod-page !pt-4 sm:!pt-6">
        <div class="admin-prod-toolbar !mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="admin-prod-eyebrow">Team leader</p>
                <h1 class="admin-prod-title">Team overview</h1>
                <p class="admin-prod-subtitle">Your agents, quantity assignments, and every IMEI device assigned to your
                    team.</p>
            </div>
            <a href="{{ route('team-leader.team-inventory') }}" class="admin-prod-btn-primary shrink-0">Full IMEI register</a>
        </div>

        @if (session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-6" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="admin-prod-alert admin-prod-alert--error mb-6" role="alert">{{ session('error') }}</div>
        @endif

        <div class="mb-6 admin-clay-panel overflow-hidden">
            <div class="admin-prod-form-head border-b border-white/60">
                <h2 class="admin-prod-form-title">Your assignment</h2>
                <p class="admin-prod-form-hint">Branch, region, and reporting line.</p>
            </div>
            <div class="admin-prod-form-body">
                <p class="text-sm font-semibold text-[#232f3e]">{{ $leader->name }}</p>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="font-medium text-slate-500">Branch</dt>
                        <dd class="mt-0.5 text-slate-800">{{ $leader->branch?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Region</dt>
                        <dd class="mt-0.5 text-slate-800">{{ $leader->region?->name ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-slate-500">Regional manager</dt>
                        <dd class="mt-0.5 text-slate-800">{{ $leader->regionalManager?->name ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Agents</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#232f3e]">{{ $agents->count() }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $activeAgents }} active</p>
            </div>
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Qty assigned</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#232f3e]">{{ $totalAssigned }}</p>
                <p class="mt-1 text-xs text-slate-500">Catalog assignment rows</p>
            </div>
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Qty sold</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#232f3e]">{{ $totalSold }}</p>
                <p class="mt-1 text-xs text-slate-500">From assignment totals</p>
            </div>
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">IMEIs with team</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#232f3e]">{{ $totalImeiCount }}</p>
                <p class="mt-1 text-xs text-slate-500">Serial-level devices</p>
            </div>
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">IMEIs in field</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#232f3e]">{{ $unsoldImeiCount }}</p>
                <p class="mt-1 text-xs text-slate-500">Not yet sold</p>
            </div>
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">IMEIs sold</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#232f3e]">{{ $soldImeiCount }}</p>
                <p class="mt-1 text-xs text-slate-500">Recorded on device</p>
            </div>
        </div>

        @if ($pendingSaleImeiCount > 0)
            <div class="admin-prod-alert admin-prod-alert--warning mb-6" role="status">
                <strong>{{ $pendingSaleImeiCount }}</strong> device(s) have a pending sale waiting for admin (payment /
                processing).
            </div>
        @endif

        @if ($productImeiStats->isNotEmpty())
            <div class="mb-6 admin-clay-panel overflow-hidden">
                <div class="admin-prod-form-head border-b border-white/60">
                    <h2 class="admin-prod-form-title">Products on your team (by IMEI count)</h2>
                    <p class="admin-prod-form-hint">How many serialised units each product represents across all your
                        agents.</p>
                </div>
                <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                    <table class="min-w-[720px]">
                        <thead>
                            <tr>
                                <th class="admin-prod-th">Product</th>
                                <th class="admin-prod-th text-right">IMEIs total</th>
                                <th class="admin-prod-th text-right">In field</th>
                                <th class="admin-prod-th text-right">Sold</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($productImeiStats as $p)
                                <tr>
                                    <td class="font-semibold text-[#232f3e]">{{ $p->product_name ?: '—' }}</td>
                                    <td class="text-right font-variant-numeric text-slate-800">{{ (int) $p->imei_total }}
                                    </td>
                                    <td class="text-right font-variant-numeric text-slate-700">{{ (int) $p->imei_unsold }}
                                    </td>
                                    <td class="text-right font-variant-numeric text-slate-700">{{ (int) $p->imei_sold }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="admin-clay-panel overflow-hidden">
            <div class="admin-prod-form-head border-b border-white/60 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="admin-prod-form-title">Your agents</h2>
                    <p class="admin-prod-form-hint">Contact, branch, quantity assignments, and IMEI-level stock in the
                        field.</p>
                </div>
            </div>
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table class="min-w-[1100px]">
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Name</th>
                            <th scope="col" class="admin-prod-th">Email</th>
                            <th scope="col" class="admin-prod-th">Phone</th>
                            <th scope="col" class="admin-prod-th">Branch</th>
                            <th scope="col" class="admin-prod-th">Status</th>
                            <th scope="col" class="admin-prod-th text-right">Qty assigned</th>
                            <th scope="col" class="admin-prod-th text-right">Qty sold</th>
                            <th scope="col" class="admin-prod-th text-right">Qty left</th>
                            <th scope="col" class="admin-prod-th text-right">IMEIs</th>
                            <th scope="col" class="admin-prod-th text-right">IMEI unsold</th>
                            <th scope="col" class="admin-prod-th text-right">IMEI sold</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($agents as $agent)
                            @php
                                $row = $assignmentTotals->get($agent->id);
                                $assigned = (int) ($row->assigned ?? 0);
                                $sold = (int) ($row->sold ?? 0);
                                $remaining = max(0, $assigned - $sold);
                                $im = $agentImeiStats->get($agent->id);
                                $imTotal = (int) ($im->imei_total ?? 0);
                                $imUnsold = (int) ($im->imei_unsold ?? 0);
                                $imSold = (int) ($im->imei_sold ?? 0);
                            @endphp
                            <tr>
                                <td class="font-semibold text-[#232f3e]">{{ $agent->name }}</td>
                                <td class="text-slate-600">{{ $agent->email }}</td>
                                <td class="text-slate-600">{{ $agent->phone ?? '—' }}</td>
                                <td class="text-slate-600">{{ $agent->branch?->name ?? '—' }}</td>
                                <td>
                                    @php $isActive = ($agent->status ?? 'active') === 'active'; @endphp
                                    <span
                                        class="admin-prod-user-status {{ $isActive ? 'admin-prod-user-status--active' : 'admin-prod-user-status--inactive' }}">
                                        {{ ucfirst($agent->status ?? 'active') }}
                                    </span>
                                </td>
                                <td class="text-right font-variant-numeric text-slate-700">{{ $assigned }}</td>
                                <td class="text-right font-variant-numeric text-slate-700">{{ $sold }}</td>
                                <td class="text-right font-variant-numeric text-slate-700">{{ $remaining }}</td>
                                <td class="text-right font-variant-numeric text-slate-800">{{ $imTotal }}</td>
                                <td class="text-right font-variant-numeric text-slate-700">{{ $imUnsold }}</td>
                                <td class="text-right font-variant-numeric text-slate-700">{{ $imSold }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="py-12 text-center text-slate-500">
                                    No agents are assigned to you yet. When an administrator assigns agents to you as
                                    their team leader, they will appear here.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-team-leader-layout>
