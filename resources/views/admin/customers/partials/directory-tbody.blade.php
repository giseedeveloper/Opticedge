@forelse($customers as $user)
    <tr>
        <td>
            <div class="flex items-center gap-3">
                <span class="admin-prod-avatar" aria-hidden="true">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                <span class="font-semibold text-[#232f3e]">{{ $user->name }}</span>
            </div>
        </td>
        <td class="text-slate-600">{{ $user->email }}</td>
        @if($hasTeamLeaderColumn)
            <td class="text-slate-600">{{ $user->teamLeader?->name ?? '—' }}</td>
        @endif
        <td>
            @php
                $role = $user->role ?? 'customer';
                $roleClass = match ($role) {
                    'admin' => 'admin-prod-role-pill--admin',
                    'dealer' => 'admin-prod-role-pill--dealer',
                    'agent' => 'admin-prod-role-pill--agent',
                    'teamleader' => 'admin-prod-role-pill--teamleader',
                    'regional_manager' => 'admin-prod-role-pill--regional_manager',
                    default => 'admin-prod-role-pill--customer',
                };
                $roleLabel = match ($role) {
                    'regional_manager' => 'Regional manager',
                    'teamleader' => 'Team leader',
                    default => $role,
                };
            @endphp
            <span class="admin-prod-role-pill {{ $roleClass }}">{{ $roleLabel }}</span>
        </td>
        <td class="text-slate-600">{{ $user->listRegionName() ?? '—' }}</td>
        <td class="text-slate-600">{{ $user->listBranchName() ?? '—' }}</td>
        <td>
            @php
                $isActive = ($user->status ?? 'active') === 'active';
            @endphp
            <span
                class="admin-prod-user-status {{ $isActive ? 'admin-prod-user-status--active' : 'admin-prod-user-status--inactive' }}">
                {{ ucfirst($user->status ?? 'active') }}
            </span>
        </td>
        <td class="font-variant-numeric text-slate-600 text-sm">
            {{ $user->created_at->format('M j, Y') }}
        </td>
        <x-admin-directory-user-actions :user="$user" :preserve-query="true" />
    </tr>
@empty
    <tr>
        <td colspan="{{ $hasTeamLeaderColumn ? 9 : 8 }}" class="text-center text-slate-500 py-10">
            No users found.
        </td>
    </tr>
@endforelse
