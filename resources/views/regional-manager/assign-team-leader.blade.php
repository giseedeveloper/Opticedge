<x-regional-manager-layout title="Assign to team leader">
    @include('partials.hierarchy-device-form', [
        'title' => 'Assign to team leader',
        'subtitle' => 'Give devices you received from admin to a team leader in your region.',
        'backUrl' => route('regional-manager.dashboard'),
        'backLabel' => 'Regional overview',
        'formAction' => route('regional-manager.assign-team-leader.store'),
        'recipientLabel' => 'Team leader',
        'recipientName' => 'team_leader_id',
        'recipientOptions' => $teamLeaders,
        'productOptions' => $products,
        'assignableUrl' => route('regional-manager.assign-team-leader.assignable-imeis'),
        'submitLabel' => 'Assign to team leader',
        'imeiHelp' => 'Only devices currently in your custody (not already with a team leader or agent).',
    ])
</x-regional-manager-layout>
