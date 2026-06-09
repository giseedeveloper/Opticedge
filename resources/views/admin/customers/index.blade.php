<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar !mb-0 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="admin-prod-eyebrow">Users</p>
                <h1 class="admin-prod-title">All users</h1>
            </div>
            @php
                $roleFilters = [
                    [
                        'role' => null,
                        'label' => 'All',
                        'href' => route('admin.customers.index'),
                        'add' => null,
                    ],
                    [
                        'role' => 'subadmin',
                        'label' => 'Admins',
                        'href' => route('admin.customers.index', ['role' => 'subadmin']),
                        'add' => ['label' => 'Add admin', 'route' => route('admin.subadmins.create')],
                    ],
                    [
                        'role' => 'agent',
                        'label' => 'Agents',
                        'href' => route('admin.customers.index', ['role' => 'agent']),
                        'add' => ['label' => 'Add agent', 'route' => route('admin.agents.create')],
                    ],
                    [
                        'role' => 'teamleader',
                        'label' => 'Team leaders',
                        'href' => route('admin.customers.index', ['role' => 'teamleader']),
                        'add' => ['label' => 'Add team leader', 'route' => route('admin.customers.team-leaders.create')],
                    ],
                    [
                        'role' => 'regional_manager',
                        'label' => 'Regional managers',
                        'href' => route('admin.customers.index', ['role' => 'regional_manager']),
                        'add' => ['label' => 'Add regional manager', 'route' => route('admin.customers.regional-managers.create')],
                        'assign' => ['label' => 'Assign devices', 'route' => route('admin.customers.regional-managers.assign-devices')],
                    ],
                ];
            @endphp
            <div class="admin-prod-filter-row shrink-0" role="tablist" aria-label="Filter by role">
                @foreach ($roleFilters as $filter)
                    @php
                        $isActive = request('role') === $filter['role'] || ($filter['role'] === null && ! request('role'));
                    @endphp
                    @if ($isActive && $filter['add'])
                        <div
                            x-data="{ open: true }"
                            class="admin-prod-filter-dropdown"
                            @keydown.escape.window="open = false">
                            <button
                                type="button"
                                class="admin-prod-filter-tab admin-prod-filter-tab--active admin-prod-filter-tab--menu"
                                :aria-expanded="open"
                                aria-haspopup="menu"
                                @click="open = !open">
                                <span>{{ $filter['label'] }}</span>
                                <svg class="h-4 w-4 shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div
                                x-show="open"
                                x-cloak
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 -translate-y-1"
                                @click.outside="open = false"
                                class="admin-prod-filter-menu"
                                role="menu">
                                <a href="{{ $filter['add']['route'] }}" class="admin-prod-filter-menu-item" role="menuitem"
                                    @click="open = false">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    {{ $filter['add']['label'] }}
                                </a>
                                @if (! empty($filter['assign']))
                                    <a href="{{ $filter['assign']['route'] }}" class="admin-prod-filter-menu-item" role="menuitem"
                                        @click="open = false">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                        {{ $filter['assign']['label'] }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @else
                        <a href="{{ $filter['href'] }}"
                            class="admin-prod-filter-tab {{ $isActive ? 'admin-prod-filter-tab--active' : '' }}"
                            @if ($isActive) aria-current="page" @endif>
                            {{ $filter['label'] }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="admin-prod-alert admin-prod-alert--error mb-4" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="admin-clay-panel overflow-hidden">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table class="min-w-[720px]">
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Name</th>
                            <th scope="col" class="admin-prod-th">Email</th>
                            <th scope="col" class="admin-prod-th">Role</th>
                            <th scope="col" class="admin-prod-th">Status</th>
                            <th scope="col" class="admin-prod-th">Joined</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $user)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="admin-prod-avatar" aria-hidden="true">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                        <span class="font-semibold text-[#232f3e]">{{ $user->name }}</span>
                                    </div>
                                </td>
                                <td class="text-slate-600">{{ $user->email }}</td>
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
                                <x-admin-user-actions>
                                    @if(($user->role ?? '') === 'regional_manager' && $isActive)
                                        <a href="{{ route('admin.customers.regional-managers.assign-devices', ['regional_manager_id' => $user->id]) }}"
                                            class="admin-prod-link text-sm whitespace-nowrap">Assign devices</a>
                                    @endif
                                    @if(($user->role ?? '') === 'agent' && $isActive)
                                        <a href="{{ route('admin.agents.show', $user) }}" class="admin-prod-link text-sm whitespace-nowrap">View &amp; assign</a>
                                    @endif
                                    <div class="admin-user-actions-collapse__section">
                                        <p class="admin-user-actions-collapse__label">Reset password</p>
                                        <form method="POST" action="{{ route('admin.users.reset-password', $user) }}"
                                            class="mt-1 flex flex-wrap items-center justify-end gap-2">
                                            @csrf
                                            <input type="password" name="password" required minlength="8"
                                                placeholder="New password" class="admin-prod-input w-36 py-1.5 text-sm">
                                            <input type="password" name="password_confirmation" required minlength="8"
                                                placeholder="Confirm" class="admin-prod-input w-32 py-1.5 text-sm">
                                            <button type="submit" class="admin-prod-link whitespace-nowrap text-sm">Save</button>
                                        </form>
                                    </div>
                                    @if(($user->role ?? '') !== 'admin')
                                        @if($isActive)
                                            <form method="POST" action="{{ route('admin.customers.deactivate', ['user' => $user->id] + request()->query()) }}"
                                                class="w-full flex justify-end"
                                                onsubmit="return confirm('Deactivate this user? They will not be able to log in until reactivated.');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="admin-prod-link text-sm text-red-600 hover:text-red-700">Deactivate</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.customers.activate', ['user' => $user->id] + request()->query()) }}"
                                                class="w-full flex justify-end"
                                                onsubmit="return confirm('Activate this user? They will be able to log in again.');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="admin-prod-link text-sm text-emerald-700 hover:text-emerald-800">Activate</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.customers.destroy', ['user' => $user->id] + request()->query()) }}"
                                            class="w-full flex justify-end"
                                            onsubmit="return confirm('Delete this user permanently? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="admin-prod-link text-sm text-rose-700 hover:text-rose-800">Delete</button>
                                        </form>
                                    @endif
                                </x-admin-user-actions>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-slate-500 py-10">
                                    No users found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
