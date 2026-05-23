<x-admin-layout>
    @include('admin.partials.catalog-styles')

    @push('styles')
        <style>
            .org-tree {
                --org-line: rgba(148, 163, 184, 0.55);
                --org-rm: #1e3a5f;
                --org-tl: #334155;
                --org-agent: #475569;
            }

            .org-tree-node {
                position: relative;
                margin-left: 1.25rem;
                padding-left: 1rem;
                border-left: 2px solid var(--org-line);
            }

            .org-tree-node::before {
                content: '';
                position: absolute;
                left: -2px;
                top: 1.15rem;
                width: 1rem;
                height: 2px;
                background: var(--org-line);
            }

            .org-tree-root > .org-tree-branch {
                margin-left: 0;
                padding-left: 0;
                border-left: none;
            }

            .org-tree-root > .org-tree-branch::before {
                display: none;
            }

            .org-card {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.5rem 0.75rem;
                padding: 0.65rem 0.85rem;
                border-radius: 0.75rem;
                background: rgba(255, 255, 255, 0.85);
                border: 1px solid rgba(203, 213, 225, 0.8);
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            }

            .org-card--rm {
                border-color: rgba(30, 58, 95, 0.25);
                background: linear-gradient(135deg, rgba(30, 58, 95, 0.08), rgba(255, 255, 255, 0.9));
            }

            .org-card--tl {
                border-color: rgba(51, 65, 85, 0.2);
            }

            .org-card--agent {
                padding: 0.5rem 0.75rem;
                font-size: 0.875rem;
            }

            .org-role-badge {
                display: inline-flex;
                align-items: center;
                padding: 0.15rem 0.5rem;
                border-radius: 9999px;
                font-size: 0.65rem;
                font-weight: 600;
                letter-spacing: 0.04em;
                text-transform: uppercase;
            }

            .org-role-badge--rm {
                background: rgba(30, 58, 95, 0.12);
                color: var(--org-rm);
            }

            .org-role-badge--tl {
                background: rgba(51, 65, 85, 0.1);
                color: var(--org-tl);
            }

            .org-role-badge--agent {
                background: rgba(71, 85, 105, 0.1);
                color: var(--org-agent);
            }

            .org-meta {
                font-size: 0.75rem;
                color: #64748b;
            }

            .org-empty-branch {
                margin-left: 1.25rem;
                padding: 0.5rem 0.75rem;
                font-size: 0.8125rem;
                color: #94a3b8;
                font-style: italic;
            }
        </style>
    @endpush

    <div class="admin-prod-page" x-data="{ expandAll: true }">
        <div class="admin-prod-toolbar !mb-0 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="admin-prod-eyebrow">Users & Dealers</p>
                <h1 class="admin-prod-title">Organization tree</h1>
                <p class="admin-prod-subtitle">Hierarchy from regional managers → team leaders → agents. Expand or collapse branches below.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <button type="button" @click="expandAll = true"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50">
                    Expand all
                </button>
                <button type="button" @click="expandAll = false"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50">
                    Collapse all
                </button>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            <div class="admin-clay-panel p-4 text-center">
                <p class="text-2xl font-bold text-[#232f3e]">{{ $stats['regional_managers'] }}</p>
                <p class="text-xs text-slate-500 mt-1 uppercase tracking-wide">Regional managers</p>
            </div>
            <div class="admin-clay-panel p-4 text-center">
                <p class="text-2xl font-bold text-[#232f3e]">{{ $stats['team_leaders'] }}</p>
                <p class="text-xs text-slate-500 mt-1 uppercase tracking-wide">Team leaders</p>
            </div>
            <div class="admin-clay-panel p-4 text-center">
                <p class="text-2xl font-bold text-[#232f3e]">{{ $stats['agents'] }}</p>
                <p class="text-xs text-slate-500 mt-1 uppercase tracking-wide">Agents</p>
            </div>
            @if($hasManagerLink && $stats['unassigned_team_leaders'] > 0)
                <div class="admin-clay-panel p-4 text-center border-amber-200/80">
                    <p class="text-2xl font-bold text-amber-800">{{ $stats['unassigned_team_leaders'] }}</p>
                    <p class="text-xs text-amber-700/80 mt-1 uppercase tracking-wide">Unassigned TLs</p>
                </div>
            @endif
            @if($hasTeamLeaderLink && $stats['unassigned_agents'] > 0)
                <div class="admin-clay-panel p-4 text-center border-amber-200/80">
                    <p class="text-2xl font-bold text-amber-800">{{ $stats['unassigned_agents'] }}</p>
                    <p class="text-xs text-amber-700/80 mt-1 uppercase tracking-wide">Unassigned agents</p>
                </div>
            @endif
        </div>

        <div class="mt-6 admin-clay-panel p-4 sm:p-6 org-tree">
            @if($regionalManagers->isEmpty() && $unassignedTeamLeaders->isEmpty() && $unassignedAgents->isEmpty())
                <p class="text-center text-slate-500 py-12">No regional managers, team leaders, or agents found yet.</p>
            @else
                <div class="org-tree-root space-y-4">
                    @foreach($regionalManagers as $manager)
                        @php
                            $managerTls = $hasManagerLink
                                ? $teamLeadersByManager->get($manager->id, collect())
                                : collect();
                        @endphp
                        <div class="org-tree-branch" x-data="{ open: true }" x-effect="open = expandAll">
                            <div class="org-card org-card--rm">
                                <button type="button" @click="open = !open"
                                    class="p-1 rounded hover:bg-slate-100 text-slate-500 shrink-0"
                                    :aria-expanded="open">
                                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-90': open }" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                                <span class="org-role-badge org-role-badge--rm">Regional manager</span>
                                <span class="font-semibold text-[#232f3e]">{{ $manager->name }}</span>
                                @if($manager->region?->name)
                                    <span class="org-meta">{{ $manager->region->name }}</span>
                                @endif
                                @if(($manager->status ?? 'active') !== 'active')
                                    <span class="org-meta text-amber-700">{{ ucfirst($manager->status) }}</span>
                                @endif
                                <span class="org-meta ml-auto">{{ $managerTls->count() }} team leader(s)</span>
                            </div>

                            <div x-show="open" x-cloak class="mt-2 space-y-2">
                                @forelse($managerTls as $tl)
                                    @php
                                        $tlAgents = $hasTeamLeaderLink
                                            ? $agentsByTeamLeader->get($tl->id, collect())
                                            : collect();
                                    @endphp
                                    <div class="org-tree-node" x-data="{ open: true }" x-effect="open = expandAll">
                                        <div class="org-card org-card--tl">
                                            <button type="button" @click="open = !open"
                                                class="p-1 rounded hover:bg-slate-100 text-slate-500 shrink-0"
                                                :aria-expanded="open">
                                                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-90': open }"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M9 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                            <span class="org-role-badge org-role-badge--tl">Team leader</span>
                                            <span class="font-medium text-slate-800">{{ $tl->name }}</span>
                                            @if($tl->branch?->name)
                                                <span class="org-meta">{{ $tl->branch->name }}</span>
                                            @endif
                                            @if(($tl->status ?? 'active') !== 'active')
                                                <span class="org-meta text-amber-700">{{ ucfirst($tl->status) }}</span>
                                            @endif
                                            <span class="org-meta ml-auto">{{ $tlAgents->count() }} agent(s)</span>
                                        </div>

                                        <div x-show="open" x-cloak class="mt-2 space-y-1.5">
                                            @forelse($tlAgents as $agent)
                                                <div class="org-tree-node">
                                                    <div class="org-card org-card--agent">
                                                        <span class="org-role-badge org-role-badge--agent">Agent</span>
                                                        <span class="text-slate-800">{{ $agent->name }}</span>
                                                        @if($agent->branch?->name)
                                                            <span class="org-meta">{{ $agent->branch->name }}</span>
                                                        @endif
                                                        @if(($agent->status ?? 'active') !== 'active')
                                                            <span class="org-meta text-amber-700">{{ ucfirst($agent->status) }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="org-empty-branch">No agents under this team leader.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                @empty
                                    <p class="org-empty-branch">No team leaders assigned to this regional manager.</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach

                    @if($hasManagerLink && $unassignedTeamLeaders->isNotEmpty())
                        <div class="org-tree-branch pt-4 border-t border-slate-200/80" x-data="{ open: true }"
                            x-effect="open = expandAll">
                            <div class="org-card border-amber-200/70 bg-amber-50/50">
                                <button type="button" @click="open = !open"
                                    class="p-1 rounded hover:bg-amber-100/80 text-amber-800 shrink-0">
                                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-90': open }"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                                <span class="font-semibold text-amber-900">Unassigned team leaders</span>
                                <span class="org-meta ml-auto">{{ $unassignedTeamLeaders->count() }}</span>
                            </div>
                            <div x-show="open" x-cloak class="mt-2 space-y-2">
                                @foreach($unassignedTeamLeaders as $tl)
                                    @php
                                        $tlAgents = $hasTeamLeaderLink
                                            ? $agentsByTeamLeader->get($tl->id, collect())
                                            : collect();
                                    @endphp
                                    <div class="org-tree-node" x-data="{ open: true }" x-effect="open = expandAll">
                                        <div class="org-card org-card--tl">
                                            <button type="button" @click="open = !open"
                                                class="p-1 rounded hover:bg-slate-100 text-slate-500 shrink-0">
                                                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-90': open }"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M9 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                            <span class="org-role-badge org-role-badge--tl">Team leader</span>
                                            <span class="font-medium text-slate-800">{{ $tl->name }}</span>
                                            <span class="org-meta ml-auto">{{ $tlAgents->count() }} agent(s)</span>
                                        </div>
                                        <div x-show="open" x-cloak class="mt-2 space-y-1.5">
                                            @foreach($tlAgents as $agent)
                                                <div class="org-tree-node">
                                                    <div class="org-card org-card--agent">
                                                        <span class="org-role-badge org-role-badge--agent">Agent</span>
                                                        <span class="text-slate-800">{{ $agent->name }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($hasTeamLeaderLink && $unassignedAgents->isNotEmpty())
                        <div class="org-tree-branch pt-4 border-t border-slate-200/80" x-data="{ open: true }"
                            x-effect="open = expandAll">
                            <div class="org-card border-amber-200/70 bg-amber-50/50">
                                <button type="button" @click="open = !open"
                                    class="p-1 rounded hover:bg-amber-100/80 text-amber-800 shrink-0">
                                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-90': open }"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                                <span class="font-semibold text-amber-900">Unassigned agents</span>
                                <span class="org-meta ml-auto">{{ $unassignedAgents->count() }}</span>
                            </div>
                            <div x-show="open" x-cloak class="mt-2 space-y-1.5">
                                @foreach($unassignedAgents as $agent)
                                    <div class="org-tree-node">
                                        <div class="org-card org-card--agent">
                                            <span class="org-role-badge org-role-badge--agent">Agent</span>
                                            <span class="text-slate-800">{{ $agent->name }}</span>
                                            @if($agent->branch?->name)
                                                <span class="org-meta">{{ $agent->branch->name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
