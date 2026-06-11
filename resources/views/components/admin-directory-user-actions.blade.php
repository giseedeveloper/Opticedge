@props(['user', 'preserveQuery' => false])

@php
    $query = $preserveQuery ? request()->query() : [];
    $role = $user->role ?? 'customer';
    $isActive = ($user->status ?? 'active') === 'active';
    $viewUrl = match ($role) {
        'agent' => route('admin.agents.show', $user),
        'dealer' => route('admin.dealers.show', $user->id),
        default => route('admin.customers.show', $user),
    };
@endphp

<x-admin-user-actions>
    <a href="{{ $viewUrl }}" class="admin-prod-link text-sm whitespace-nowrap">View</a>

    @if($role === 'regional_manager' && $isActive)
        <a href="{{ route('admin.customers.regional-managers.assign-devices', ['regional_manager_id' => $user->id]) }}"
            class="admin-prod-link text-sm whitespace-nowrap">Assign device</a>
    @endif

    <x-admin-reset-password-form :user="$user" />

    @if($role !== 'admin')
        @if($isActive)
            <form method="POST" action="{{ route('admin.customers.deactivate', ['user' => $user->id] + $query) }}"
                class="w-full flex justify-end"
                onsubmit="return confirm('Deactivate this user? They will not be able to log in until reactivated.');">
                @csrf
                @method('PATCH')
                <button type="submit" class="admin-prod-link text-sm text-red-600 hover:text-red-700">Deactivate</button>
            </form>
        @else
            <form method="POST" action="{{ route('admin.customers.activate', ['user' => $user->id] + $query) }}"
                class="w-full flex justify-end"
                onsubmit="return confirm('Activate this user? They will be able to log in again.');">
                @csrf
                @method('PATCH')
                <button type="submit" class="admin-prod-link text-sm text-emerald-700 hover:text-emerald-800">Activate</button>
            </form>
        @endif
        <form method="POST" action="{{ route('admin.customers.destroy', ['user' => $user->id] + $query) }}"
            class="w-full flex justify-end"
            onsubmit="return confirm('Delete this user permanently? This cannot be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="admin-prod-link text-sm text-rose-700 hover:text-rose-800">Delete</button>
        </form>
    @endif
</x-admin-user-actions>
