<x-regional-manager-layout title="Regional overview">
    <div class="admin-prod-page !pt-4 sm:!pt-6">
        <div class="admin-prod-toolbar !mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="admin-prod-eyebrow">Regional manager</p>
                <h1 class="admin-prod-title">Regional overview</h1>
                <p class="admin-prod-subtitle">Team leaders and agents under your line, quantity assignments, IMEI devices,
                    and active dealers and customers tagged to your region.</p>
            </div>
            <a href="{{ route('regional-manager.region-inventory') }}" class="admin-prod-btn-primary shrink-0">Full IMEI register</a>
        </div>

        @if (session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-6" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="admin-prod-alert admin-prod-alert--error mb-6" role="alert">{{ session('error') }}</div>
        @endif

        <div class="mb-6 admin-clay-panel overflow-hidden">
            <div class="admin-prod-form-head border-b border-white/60">
                <h2 class="admin-prod-form-title">Your territory</h2>
                <p class="admin-prod-form-hint">Profile, geography, and how the hierarchy is wired in admin.</p>
            </div>
            <div class="admin-prod-form-body">
                <p class="text-sm font-semibold text-[#232f3e]">{{ $manager->name }}</p>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="font-medium text-slate-500">Branch</dt>
                        <dd class="mt-0.5 text-slate-800">{{ $manager->branch?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Region</dt>
                        <dd class="mt-0.5 text-slate-800">{{ $manager->region?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-slate-500">Branches with field teams</dt>
                        <dd class="mt-0.5 text-slate-800">{{ $branchesRepresented }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-slate-500">Scope</dt>
                        <dd class="mt-0.5 text-slate-700">Operational data aggregates every <strong>agent</strong> whose team
                            leader lists <strong>you</strong> as regional manager. Dealer and customer counts use users
                            with the same <strong>region</strong> as your account.</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Team leaders</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#232f3e]">{{ $teamLeaders->count() }}</p>
                <p class="mt-1 text-xs text-slate-500">Reporting to you</p>
            </div>
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Agents</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#232f3e]">{{ $agents->count() }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $activeAgents }} active</p>
            </div>
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Qty assigned</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#232f3e]">{{ $totalAssigned }}</p>
                <p class="mt-1 text-xs text-slate-500">All teams</p>
            </div>
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Qty sold</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#232f3e]">{{ $totalSold }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $totalRemaining }} remaining</p>
            </div>
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">IMEIs total</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#232f3e]">{{ $totalImeiCount }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $unsoldImeiCount }} in field</p>
            </div>
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">IMEIs sold</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#232f3e]">{{ $soldImeiCount }}</p>
                <p class="mt-1 text-xs text-slate-500">Device records</p>
            </div>
        </div>

        @if ($manager->region_id)
            <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="admin-clay-panel p-5">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Active dealers (region)</p>
                    <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#232f3e]">{{ $dealersInRegion }}</p>
                </div>
                <div class="admin-clay-panel p-5">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Active customers (region)</p>
                    <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#232f3e]">{{ $customersInRegion }}</p>
                </div>
            </div>
        @endif

        @if ($pendingSaleImeiCount > 0)
            <div class="admin-prod-alert admin-prod-alert--warning mb-6" role="status">
                <strong>{{ $pendingSaleImeiCount }}</strong> device(s) across your teams have a pending sale waiting for admin
                (payment / processing).
            </div>
        @endif

        @if ($teamLeaders->isEmpty())
            <div class="admin-prod-alert mb-6" role="status">
                No team leaders are assigned to you yet. In admin, open <strong>Customers → Team leaders</strong> and set
                each team leader’s regional manager to your account so their agents appear here.
            </div>
        @endif

        @if ($productImeiStats->isNotEmpty())
            <div class="mb-6 admin-clay-panel overflow-hidden">
                <div class="admin-prod-form-head border-b border-white/60">
                    <h2 class="admin-prod-form-title">Products across your teams (IMEI counts)</h2>
                    <p class="admin-prod-form-hint">Serial-level units rolled up every agent reporting through your team
                        leaders.</p>
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
                                    <td class="text-right font-variant-numeric text-slate-700">{{ (int) $p->imei_sold }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="mb-6 admin-clay-panel overflow-hidden">
            <div class="admin-prod-form-head border-b border-white/60">
                <h2 class="admin-prod-form-title">Team leaders under you</h2>
                <p class="admin-prod-form-hint">Per-leader rollup of agents, quantity assignments, and IMEI totals.</p>
            </div>
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table class="min-w-[1000px]">
                    <thead>
                        <tr>
                            <th class="admin-prod-th">Name</th>
                            <th class="admin-prod-th">Email</th>
                            <th class="admin-prod-th">Branch</th>
                            <th class="admin-prod-th text-right">Agents</th>
                            <th class="admin-prod-th text-right">Active agents</th>
                            <th class="admin-prod-th text-right">Qty assigned</th>
                            <th class="admin-prod-th text-right">Qty sold</th>
                            <th class="admin-prod-th text-right">Qty left</th>
                            <th class="admin-prod-th text-right">IMEIs</th>
                            <th class="admin-prod-th text-right">IMEI unsold</th>
                            <th class="admin-prod-th text-right">IMEI sold</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($teamLeaderRollups as $r)
                            @php $tl = $r->team_leader; @endphp
                            <tr>
                                <td class="font-semibold text-[#232f3e]">{{ $tl->name }}</td>
                                <td class="text-slate-600">{{ $tl->email }}</td>
                                <td class="text-slate-600">{{ $tl->branch?->name ?? '—' }}</td>
                                <td class="text-right font-variant-numeric text-slate-800">{{ $r->agent_count }}</td>
                                <td class="text-right font-variant-numeric text-slate-700">{{ $r->active_agent_count }}</td>
                                <td class="text-right font-variant-numeric text-slate-700">{{ $r->qty_assigned }}</td>
                                <td class="text-right font-variant-numeric text-slate-700">{{ $r->qty_sold }}</td>
                                <td class="text-right font-variant-numeric text-slate-700">{{ $r->qty_remaining }}</td>
                                <td class="text-right font-variant-numeric text-slate-800">{{ $r->imei_total }}</td>
                                <td class="text-right font-variant-numeric text-slate-700">{{ $r->imei_unsold }}</td>
                                <td class="text-right font-variant-numeric text-slate-700">{{ $r->imei_sold }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="py-12 text-center text-slate-500">No team leaders linked to your
                                    account.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-clay-panel overflow-hidden">
            <div class="admin-prod-form-head border-b border-white/60">
                <h2 class="admin-prod-form-title">All agents in your hierarchy</h2>
                <p class="admin-prod-form-hint">Every field agent rolling up through your team leaders.</p>
            </div>
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table class="min-w-[1180px]">
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Name</th>
                            <th scope="col" class="admin-prod-th">Team leader</th>
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
                                <td class="text-slate-700">{{ $agent->teamLeader?->name ?? '—' }}</td>
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
                                <td colspan="12" class="py-12 text-center text-slate-500">
                                    No agents yet. Assign team leaders to you first, then ensure each leader has agents in
                                    admin.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-regional-manager-layout>
