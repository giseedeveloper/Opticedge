@php
    $managerTls = $hasManagerLink
        ? $teamLeadersByManager->get($manager->id, collect())
        : collect();
    $tlCount = $managerTls->count();
    $location = $manager->region?->name;
    $columnParams = [
        'branchColors' => $branchColors,
        'agentsByTeamLeader' => $agentsByTeamLeader,
        'hasTeamLeaderLink' => $hasTeamLeaderLink,
    ];
@endphp

<section class="oc-tree">
    <article class="oc-card oc-card--rm">
        <div class="oc-card__body">
            @include('admin.organization-tree.partials.person-avatar', ['user' => $manager, 'size' => 'lg'])
            <div class="oc-card__info">
                <p class="oc-card__name">{{ $manager->name }}</p>
                <p class="oc-card__role oc-card__role--rm">Regional Manager</p>
                @if($location)
                    <p class="oc-card__location">{{ $location }}</p>
                @endif
            </div>
        </div>
    </article>

    @if($tlCount === 0)
        <div class="oc-bridge"><div class="oc-line-v oc-rm-down"></div></div>
        <p class="oc-empty-slot">No team leaders assigned to this regional manager yet.</p>
    @else
        <div class="oc-bridge"><div class="oc-line-v oc-rm-down"></div></div>

        @if($tlCount > 1)
            <div class="oc-h-rail" style="width: {{ max(240, ($tlCount - 1) * 220 + 200) }}px;">
                @foreach($managerTls as $tl)
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
                'tl' => $managerTls->first(),
                'colorIndex' => 0,
            ]))
        @endif
    @endif
</section>
