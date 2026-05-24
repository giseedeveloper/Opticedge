<x-team-leader-layout title="Return to regional manager">
    @include('partials.hierarchy-device-return-form', [
        'title' => 'Return devices to regional manager',
        'subtitle' => 'Send devices back to your regional manager. Agents must return devices to you first.',
        'backUrl' => route('team-leader.dashboard'),
        'backLabel' => 'Team overview',
        'formAction' => route('team-leader.return-devices.store'),
        'productOptions' => $products,
        'assignableUrl' => route('team-leader.return-devices.assignable-imeis'),
        'submitLabel' => 'Return to regional manager',
        'imeiHelp' => 'Only devices in your custody (not with an agent).',
    ])
</x-team-leader-layout>
