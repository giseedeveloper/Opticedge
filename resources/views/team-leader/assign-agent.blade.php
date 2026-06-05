<x-team-leader-layout title="Assign to agent">
    @include('partials.hierarchy-device-form', [
        'title' => 'Assign to agent',
        'subtitle' => 'Give devices you received from your regional manager to an agent on your team.',
        'backUrl' => route('team-leader.dashboard'),
        'backLabel' => 'Team overview',
        'formAction' => route('team-leader.assign-agent.store'),
        'recipientLabel' => 'Agent',
        'recipientName' => 'agent_id',
        'recipientOptions' => $agents,
        'recipientSelected' => old('agent_id', $selectedAgent ?? null),
        'productOptions' => $products,
        'assignableUrl' => route('team-leader.assign-agent.assignable-imeis'),
        'submitLabel' => 'Assign to agent',
        'imeiHelp' => 'Only devices currently in your custody (not already with an agent).',
    ])
</x-team-leader-layout>
