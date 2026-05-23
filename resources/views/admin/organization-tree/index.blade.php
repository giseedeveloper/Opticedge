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
    @endphp

    @push('styles')
        <style>
            .oc-wrap { overflow-x: auto; padding: 1.5rem 0.5rem 2rem; }
            .oc-section { display: flex; flex-direction: column; align-items: center; min-width: min-content; margin-bottom: 3rem; }
            .oc-section + .oc-section { padding-top: 2.5rem; border-top: 1px dashed rgba(148, 163, 184, 0.45); }
            .oc-node { border-radius: 10px; padding: 12px 22px; font-weight: 700; font-size: 0.9rem; text-align: center; line-height: 1.25; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.1); white-space: nowrap; }
            .oc-node--rm { background: #c0392b; color: #fff; min-width: 160px; padding: 14px 28px; font-size: 1rem; }
            .oc-node--rm-muted { background: #d97706; color: #fff; }
            .oc-node--tl { color: #fff; min-width: 130px; }
            .oc-node--agent { font-size: 0.8125rem; font-weight: 600; min-width: 110px; padding: 9px 16px; }
            .oc-node-sub { display: block; font-size: 0.65rem; font-weight: 500; opacity: 0.88; margin-top: 3px; }
            .oc-line-v { width: 2px; background: #cbd5e1; flex-shrink: 0; }
            .oc-rm-down { height: 28px; }
            .oc-bridge { display: flex; flex-direction: column; align-items: center; }
            .oc-h-rail { display: flex; justify-content: center; align-items: flex-start; position: relative; }
            .oc-h-rail::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: #cbd5e1; }
            .oc-col { display: flex; flex-direction: column; align-items: center; padding: 0 14px; flex-shrink: 0; }
            .oc-col-drop { height: 28px; width: 2px; background: #cbd5e1; }
            .oc-tl-down { height: 22px; }
            .oc-agents { display: flex; flex-direction: column; align-items: flex-start; position: relative; margin-left: 50%; padding-left: 1px; transform: translateX(-1px); }
            .oc-agents::before { content: ''; position: absolute; top: 0; left: 0; width: 2px; background: #cbd5e1; bottom: 14px; }
            .oc-agent-row { display: flex; align-items: center; margin: 6px 0; position: relative; z-index: 1; }
            .oc-agent-arm { width: 22px; height: 2px; background: #cbd5e1; }
            .oc-empty-slot { font-size: 0.75rem; color: #94a3b8; font-style: italic; padding: 8px 12px; }
            .oc-unassigned-agents { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-top: 8px; }
            .oc-unassigned-agents .oc-node--agent { background: #f1f5f9; color: #475569; border: 1px dashed #cbd5e1; }
        </style>
    @endpush

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar !mb-0 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="admin-prod-eyebrow">Users & Dealers</p>
                <h1 class="admin-prod-title">Organization tree</h1>
                <p class="admin-prod-subtitle">Visual hierarchy: regional managers → team leaders → agents.</p>
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

        <div class="mt-6 admin-clay-panel p-4 sm:p-6">
            @if($regionalManagers->isEmpty() && $unassignedTeamLeaders->isEmpty() && $unassignedAgents->isEmpty())
                <p class="text-center text-slate-500 py-12">No regional managers, team leaders, or agents found yet.</p>
            @else
                <div class="oc-wrap">
                    @foreach($regionalManagers as $manager)
                        @php
                            $managerTls = $hasManagerLink
                                ? $teamLeadersByManager->get($manager->id, collect())
                                : collect();
                            $tlCount = $managerTls->count();
                            $columnParams = [
                                'branchColors' => $branchColors,
                                'agentsByTeamLeader' => $agentsByTeamLeader,
                                'hasTeamLeaderLink' => $hasTeamLeaderLink,
                            ];
                        @endphp

                        <section class="oc-section">
                            <div class="oc-node oc-node--rm">
                                {{ $manager->name }}
                                @if($manager->region?->name)
                                    <span class="oc-node-sub">{{ $manager->region->name }}</span>
                                @endif
                            </div>

                            @if($tlCount === 0)
                                <div class="oc-line-v oc-rm-down"></div>
                                <p class="oc-empty-slot">No team leaders assigned</p>
                            @else
                                <div class="oc-bridge">
                                    <div class="oc-line-v oc-rm-down"></div>
                                </div>

                                @if($tlCount > 1)
                                    <div class="oc-h-rail" style="width: {{ max(180, ($tlCount - 1) * 158 + 130) }}px;">
                                        @foreach($managerTls as $tl)
                                            <div class="oc-col" style="flex: 1;">
                                                <div class="oc-col-drop"></div>
                                                @include('admin.organization-tree.partials.team-column', array_merge($columnParams, [
                                                    'tl' => $tl,
                                                    'colorIndex' => $loop->index,
                                                ]))
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="oc-col">
                                        @include('admin.organization-tree.partials.team-column', array_merge($columnParams, [
                                            'tl' => $managerTls->first(),
                                            'colorIndex' => 0,
                                        ]))
                                    </div>
                                @endif
                            @endif
                        </section>
                    @endforeach

                    @if($hasManagerLink && $unassignedTeamLeaders->isNotEmpty())
                        @php
                            $tlCount = $unassignedTeamLeaders->count();
                            $columnParams = [
                                'branchColors' => $branchColors,
                                'agentsByTeamLeader' => $agentsByTeamLeader,
                                'hasTeamLeaderLink' => $hasTeamLeaderLink,
                            ];
                        @endphp
                        <section class="oc-section">
                            <div class="oc-node oc-node--rm oc-node--rm-muted">Unassigned team leaders</div>
                            <div class="oc-bridge"><div class="oc-line-v oc-rm-down"></div></div>
                            @if($tlCount > 1)
                                <div class="oc-h-rail" style="width: {{ max(180, ($tlCount - 1) * 158 + 130) }}px;">
                                    @foreach($unassignedTeamLeaders as $tl)
                                        <div class="oc-col" style="flex: 1;">
                                            <div class="oc-col-drop"></div>
                                            @include('admin.organization-tree.partials.team-column', array_merge($columnParams, [
                                                'tl' => $tl,
                                                'colorIndex' => $loop->index,
                                            ]))
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="oc-col">
                                    @include('admin.organization-tree.partials.team-column', array_merge($columnParams, [
                                        'tl' => $unassignedTeamLeaders->first(),
                                        'colorIndex' => 0,
                                    ]))
                                </div>
                            @endif
                        </section>
                    @endif

                    @if($hasTeamLeaderLink && $unassignedAgents->isNotEmpty())
                        <section class="oc-section">
                            <div class="oc-node oc-node--rm oc-node--rm-muted">
                                Unassigned agents
                                <span class="oc-node-sub">{{ $unassignedAgents->count() }} total</span>
                            </div>
                            <div class="oc-line-v oc-rm-down"></div>
                            <div class="oc-unassigned-agents">
                                @foreach($unassignedAgents as $agent)
                                    <div class="oc-node oc-node--agent">
                                        {{ $agent->name }}
                                        @if($agent->branch?->name)
                                            <span class="oc-node-sub">{{ $agent->branch->name }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
