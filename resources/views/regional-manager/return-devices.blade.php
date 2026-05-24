<x-regional-manager-layout title="Return to admin">
    @include('partials.hierarchy-device-return-form', [
        'title' => 'Return devices to admin',
        'subtitle' => 'Send devices back to admin stock. Devices still with team leaders or agents must be returned to you first.',
        'backUrl' => route('regional-manager.dashboard'),
        'backLabel' => 'Regional overview',
        'formAction' => route('regional-manager.return-devices.store'),
        'productOptions' => $products,
        'assignableUrl' => route('regional-manager.return-devices.assignable-imeis'),
        'submitLabel' => 'Return to admin',
        'imeiHelp' => 'Only devices in your custody (not with a team leader or agent).',
    ])
</x-regional-manager-layout>
