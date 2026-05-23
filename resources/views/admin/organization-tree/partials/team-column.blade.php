@php
    $colors = $branchColors[$colorIndex % count($branchColors)];
    $tlAgents = $hasTeamLeaderLink
        ? $agentsByTeamLeader->get($tl->id, collect())
        : collect();
@endphp

<div class="oc-node oc-node--tl" style="background: {{ $colors['leader'] }};">
    {{ $tl->name }}
    @if($tl->branch?->name)
        <span class="oc-node-sub">{{ $tl->branch->name }}</span>
    @endif
</div>

<div class="oc-line-v oc-tl-down"></div>

@if($tlAgents->isEmpty())
    <p class="oc-empty-slot">No agents</p>
@else
    <div class="oc-agents">
        @foreach($tlAgents as $agent)
            <div class="oc-agent-row">
                <div class="oc-agent-arm"></div>
                <div class="oc-node oc-node--agent" style="background: {{ $colors['agent'] }}; color: {{ $colors['agentText'] }};">
                    {{ $agent->name }}
                </div>
            </div>
        @endforeach
    </div>
@endif
