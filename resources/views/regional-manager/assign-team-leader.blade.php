<x-regional-manager-layout title="Assign to team leader">
    @include('partials.hierarchy-device-form', [
        'title' => 'Assign to team leader',
        'subtitle' => 'Send devices in your custody to a team leader as a transfer request. They must accept before devices appear in their inventory.',
        'backUrl' => route('regional-manager.dashboard'),
        'backLabel' => 'Regional overview',
        'formAction' => route('regional-manager.assign-team-leader.store'),
        'recipientLabel' => 'Team leader',
        'recipientName' => 'team_leader_id',
        'recipientOptions' => $teamLeaders,
        'recipientSelected' => old('team_leader_id', $selectedTeamLeader ?? null),
        'productOptions' => $products,
        'assignableUrl' => route('regional-manager.assign-team-leader.assignable-imeis'),
        'submitLabel' => 'Send transfer request',
        'imeiHelp' => 'Only devices currently in your custody (not already with a team leader or agent).',
    ])
</x-regional-manager-layout>
