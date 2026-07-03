<x-admin-layout>
    @include('admin.partials.catalog-styles')

    @php
        $branchColors = [
            ['leader' => '#1565c0', 'agent' => '#bbdefb', 'agentText' => '#0d47a1'],
            ['leader' => '#2e7d32', 'agent' => '#c5e1a5', 'agentText' => '#1b5e20'],
            ['leader' => '#ef6c00', 'agent' => '#ffe0b2', 'agentText' => '#e65100'],
            ['leader' => '#6a1b9a', 'agent' => '#e1bee7', 'agentText' => '#4a148c'],
            ['leader' => '#00838f', 'agent' => '#b2ebf2', 'agentText' => '#006064'],
            ['leader' => '#ad1457', 'agent' => '#f8bbd0', 'agentText' => '#880e4f'],
        ];

        $hasUnassigned = ($hasManagerLink && $unassignedTeamLeaders->isNotEmpty())
            || ($hasTeamLeaderLink && $unassignedAgents->isNotEmpty());
        $defaultTab = $regionalManagers->isNotEmpty()
            ? (string) $regionalManagers->first()->id
            : ($hasUnassigned ? 'unassigned' : '');
        $activeManagerId = request('manager', $defaultTab);
        if ($activeManagerId !== 'unassigned' && ! $regionalManagers->contains('id', (int) $activeManagerId)) {
            $activeManagerId = $defaultTab;
        }
    @endphp

    @push('styles')
        <style>
            .oc-wrap { overflow-x: auto; padding: 1.5rem 0.25rem 2rem; }
            .oc-tree { display: flex; flex-direction: column; align-items: center; min-width: min-content; }
            .oc-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(15, 23, 42, 0.08); overflow: hidden; min-width: 220px; max-width: 280px; }
            .oc-card--rm { border-top: 4px solid #c0392b; }
            .oc-card--tl { border-top: 4px solid var(--oc-accent, #1565c0); }
            .oc-card__body { display: flex; align-items: center; gap: 12px; padding: 14px 16px; }
            .oc-card__info { min-width: 0; text-align: left; }
            .oc-card__name { font-weight: 700; font-size: 0.9375rem; color: #1e293b; line-height: 1.25; margin: 0; }
            .oc-card__role { font-size: 0.75rem; font-weight: 600; color: var(--oc-accent, #64748b); margin: 2px 0 0; }
            .oc-card__role--rm { color: #c0392b; }
            .oc-card__location { font-size: 0.6875rem; color: #64748b; margin: 2px 0 0; }
            .oc-card__agents-bar { width: 100%; display: flex; align-items: center; gap: 8px; padding: 8px 14px; font-size: 0.75rem; font-weight: 600; color: #fff; background: var(--oc-accent, #1565c0); border: none; cursor: pointer; transition: opacity 0.15s; }
            .oc-card__agents-bar:hover { opacity: 0.92; }
            .oc-line-v { width: 2px; background: #cbd5e1; flex-shrink: 0; }
            .oc-rm-down { height: 32px; }
            .oc-bridge { display: flex; flex-direction: column; align-items: center; }
            .oc-h-rail { display: flex; justify-content: center; align-items: flex-start; position: relative; gap: 0; }
            .oc-h-rail::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: #cbd5e1; }
            .oc-col { display: flex; flex-direction: column; align-items: center; padding: 0 12px; flex-shrink: 0; }
            .oc-col-drop { height: 32px; width: 2px; background: #cbd5e1; }
            .oc-tl-down { height: 20px; }
            .oc-agents-panel { width: 100%; max-width: 220px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px; margin-top: 4px; }
            .oc-agents-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 10px; }
            .oc-agent-item { display: flex; align-items: center; gap: 10px; }
            .oc-agent-item__info { min-width: 0; }
            .oc-agent-item__name { font-size: 0.8125rem; font-weight: 600; color: #334155; margin: 0; line-height: 1.2; }
            .oc-agent-item__role { font-size: 0.6875rem; color: #64748b; margin: 1px 0 0; }
            .oc-agents-footer { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 10px; padding-top: 10px; border-top: 1px solid #e2e8f0; font-size: 0.6875rem; }
            .oc-agents-more { color: #64748b; }
            .oc-agents-view-all { font-weight: 700; text-decoration: none; }
            .oc-agents-view-all:hover { text-decoration: underline; }
            .oc-empty-slot { font-size: 0.8125rem; color: #94a3b8; font-style: italic; padding: 12px; text-align: center; }
            .oc-legend { display: flex; flex-wrap: wrap; justify-content: center; gap: 1.25rem 2rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0; font-size: 0.75rem; color: #64748b; }
            .oc-legend-item { display: inline-flex; align-items: center; gap: 0.5rem; }
            .oc-legend-dot { width: 10px; height: 10px; border-radius: 9999px; flex-shrink: 0; }
            .oc-legend-dot--rm { background: #c0392b; }
            .oc-legend-dot--tl { background: #1565c0; }
            .oc-legend-dot--agent { background: #94a3b8; }
            .oc-unassigned-agents { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-top: 12px; }
            .oc-unassigned-agent { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px dashed #cbd5e1; border-radius: 9999px; padding: 6px 14px 6px 6px; font-size: 0.8125rem; font-weight: 600; color: #475569; }
            .oc-tab-panel[hidden] { display: none !important; }
        </style>
    @endpush

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar !mb-0 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="admin-prod-eyebrow">Staff</p>
                <h1 class="admin-prod-title">Organization tree</h1>
                <p class="admin-prod-subtitle">Visual hierarchy by regional manager — team leaders and agents with profile photos.</p>
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

        <div class="mt-6 admin-clay-panel p-4 sm:p-6" x-data="{ activeTab: @js($activeManagerId) }">
            @if($regionalManagers->isEmpty() && ! $hasUnassigned)
                <p class="text-center text-slate-500 py-12">No regional managers, team leaders, or agents found yet.</p>
            @else
                @if($regionalManagers->isNotEmpty() || $hasUnassigned)
                    <div class="admin-prod-filter-row mb-6 overflow-x-auto pb-1" role="tablist" aria-label="Regional managers">
                        @foreach($regionalManagers as $manager)
                            <button type="button" role="tab"
                                class="admin-prod-filter-tab shrink-0"
                                :class="activeTab === @js((string) $manager->id) ? 'admin-prod-filter-tab--active' : ''"
                                :aria-selected="activeTab === @js((string) $manager->id) ? 'true' : 'false'"
                                @click="activeTab = @js((string) $manager->id)">
                                {{ $manager->name }}
                            </button>
                        @endforeach
                        @if($hasUnassigned)
                            <button type="button" role="tab"
                                class="admin-prod-filter-tab shrink-0"
                                :class="activeTab === 'unassigned' ? 'admin-prod-filter-tab--active' : ''"
                                :aria-selected="activeTab === 'unassigned' ? 'true' : 'false'"
                                @click="activeTab = 'unassigned'">
                                Unassigned
                            </button>
                        @endif
                    </div>
                @endif

                <div class="oc-wrap">
                    @foreach($regionalManagers as $manager)
                        <div class="oc-tab-panel" role="tabpanel"
                            x-show="activeTab === @js((string) $manager->id)"
                            x-cloak>
                            @include('admin.organization-tree.partials.manager-tree', [
                                'manager' => $manager,
                                'branchColors' => $branchColors,
                                'teamLeadersByManager' => $teamLeadersByManager,
                                'agentsByTeamLeader' => $agentsByTeamLeader,
                                'hasManagerLink' => $hasManagerLink,
                                'hasTeamLeaderLink' => $hasTeamLeaderLink,
                            ])
                        </div>
                    @endforeach

                    @if($hasUnassigned)
                        <div class="oc-tab-panel" role="tabpanel" x-show="activeTab === 'unassigned'" x-cloak>
                            @if($hasManagerLink && $unassignedTeamLeaders->isNotEmpty())
                                @php
                                    $tlCount = $unassignedTeamLeaders->count();
                                    $columnParams = [
                                        'branchColors' => $branchColors,
                                        'agentsByTeamLeader' => $agentsByTeamLeader,
                                        'hasTeamLeaderLink' => $hasTeamLeaderLink,
                                    ];
                                @endphp
                                <section class="oc-tree mb-10">
                                    <article class="oc-card oc-card--rm" style="border-top-color: #d97706;">
                                        <div class="oc-card__body" style="justify-content: center;">
                                            <div class="oc-card__info text-center">
                                                <p class="oc-card__name">Unassigned team leaders</p>
                                                <p class="oc-card__role" style="color: #d97706;">No regional manager</p>
                                            </div>
                                        </div>
                                    </article>
                                    <div class="oc-bridge"><div class="oc-line-v oc-rm-down"></div></div>
                                    @if($tlCount > 1)
                                        <div class="oc-h-rail" style="width: {{ max(240, ($tlCount - 1) * 220 + 200) }}px;">
                                            @foreach($unassignedTeamLeaders as $tl)
                                                <div style="flex: 1;">
                                                    @include('admin.organization-tree.partials.team-column', array_merge($columnParams, [
                                                        'tl' => $tl,
                                                        'colorIndex' => $loop->index,
                                                    ]))
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        @include('admin.organization-tree.partials.team-column', array_merge($columnParams, [
                                            'tl' => $unassignedTeamLeaders->first(),
                                            'colorIndex' => 0,
                                        ]))
                                    @endif
                                </section>
                            @endif

                            @if($hasTeamLeaderLink && $unassignedAgents->isNotEmpty())
                                <section class="oc-tree">
                                    <article class="oc-card oc-card--rm" style="border-top-color: #d97706;">
                                        <div class="oc-card__body" style="justify-content: center;">
                                            <div class="oc-card__info text-center">
                                                <p class="oc-card__name">Unassigned agents</p>
                                                <p class="oc-card__role" style="color: #d97706;">{{ $unassignedAgents->count() }} total</p>
                                            </div>
                                        </div>
                                    </article>
                                    <div class="oc-line-v oc-rm-down mx-auto"></div>
                                    <div class="oc-unassigned-agents">
                                        @foreach($unassignedAgents as $agent)
                                            <div class="oc-unassigned-agent">
                                                @include('admin.organization-tree.partials.person-avatar', ['user' => $agent, 'size' => 'sm'])
                                                <span>{{ $agent->name }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="oc-legend" aria-label="Role legend">
                    <span class="oc-legend-item"><span class="oc-legend-dot oc-legend-dot--rm"></span> Regional Manager</span>
                    <span class="oc-legend-item"><span class="oc-legend-dot oc-legend-dot--tl"></span> Team Leader</span>
                    <span class="oc-legend-item"><span class="oc-legend-dot oc-legend-dot--agent"></span> Agent</span>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
