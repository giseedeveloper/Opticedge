@php
    $colors = $branchColors[$colorIndex % count($branchColors)];
    $tlAgents = $hasTeamLeaderLink
        ? $agentsByTeamLeader->get($tl->id, collect())
        : collect();
    $agentCount = $tlAgents->count();
    $previewLimit = 3;
    $previewAgents = $tlAgents->take($previewLimit);
    $remainingCount = max(0, $agentCount - $previewLimit);
    $location = $tl->branch?->name ?? $tl->region?->name;
@endphp

<div class="oc-col" x-data="{ agentsOpen: true }">
    <div class="oc-col-drop"></div>

    <article class="oc-card oc-card--tl" style="--oc-accent: {{ $colors['leader'] }};">
        <div class="oc-card__body">
            @include('admin.organization-tree.partials.person-avatar', ['user' => $tl, 'size' => 'lg'])
            <div class="oc-card__info">
                <p class="oc-card__name">{{ $tl->name }}</p>
                <p class="oc-card__role">Team Leader</p>
                @if($location)
                    <p class="oc-card__location">{{ $location }}</p>
                @endif
            </div>
        </div>
        <button type="button" class="oc-card__agents-bar" @click="agentsOpen = !agentsOpen"
            aria-expanded="true" :aria-expanded="agentsOpen ? 'true' : 'false'">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 opacity-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span>{{ $agentCount }} {{ Str::plural('Agent', $agentCount) }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-auto shrink-0 transition-transform duration-200"
                :class="agentsOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
    </article>

    <div class="oc-line-v oc-tl-down"></div>

    <div x-show="agentsOpen" class="oc-agents-panel">
        @if($agentCount === 0)
            <p class="oc-empty-slot">No agents assigned</p>
        @else
            <ul class="oc-agents-list">
                @foreach($previewAgents as $agent)
                    <li class="oc-agent-item">
                        @include('admin.organization-tree.partials.person-avatar', ['user' => $agent, 'size' => 'sm'])
                        <div class="oc-agent-item__info">
                            <p class="oc-agent-item__name">{{ $agent->name }}</p>
                            <p class="oc-agent-item__role">Agent</p>
                        </div>
                    </li>
                @endforeach
            </ul>
            @if($remainingCount > 0 || $agentCount > 0)
                <div class="oc-agents-footer">
                    @if($remainingCount > 0)
                        <span class="oc-agents-more">+{{ $remainingCount }} more {{ Str::plural('agent', $remainingCount) }}</span>
                    @endif
                    <a href="{{ route('admin.customers.team-leaders.show', $tl) }}" class="oc-agents-view-all"
                        style="color: {{ $colors['leader'] }};">View all</a>
                </div>
            @endif
        @endif
    </div>
</div>
