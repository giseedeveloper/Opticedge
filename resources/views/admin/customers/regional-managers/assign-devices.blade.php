<x-admin-layout>
    @include('partials.hierarchy-device-form', [
        'title' => 'Assign devices to regional manager',
        'subtitle' => 'Give warehouse devices to a regional manager. They can then assign them to team leaders and agents.',
        'backUrl' => route('admin.customers.regional-managers.index'),
        'backLabel' => 'Regional managers',
        'formAction' => route('admin.customers.regional-managers.assign-devices.store'),
        'recipientLabel' => 'Regional manager',
        'recipientName' => 'regional_manager_id',
        'recipientOptions' => $managers,
        'recipientSelected' => old('regional_manager_id', $selectedManager),
        'productOptions' => $products,
        'assignableUrl' => route('admin.customers.regional-managers.assignable-imeis'),
        'submitLabel' => 'Assign to regional manager',
        'imeiHelp' => 'Only unsold devices in the admin warehouse (not already assigned in the hierarchy).',
    ])
</x-admin-layout>
