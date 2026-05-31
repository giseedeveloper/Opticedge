<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar !mb-0 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="admin-prod-eyebrow">Staff</p>
                <h1 class="admin-prod-title">All staff</h1>
            </div>
            @php
                $addButton = match(request('role')) {
                    'agent'            => ['label' => 'Add agent',           'route' => route('admin.agents.create')],
                    'teamleader'       => ['label' => 'Add team leader',     'route' => route('admin.customers.team-leaders.create')],
                    'regional_manager' => ['label' => 'Add regional manager','route' => route('admin.customers.regional-managers.create')],
                    'subadmin'         => ['label' => 'Add leader',          'route' => route('admin.subadmins.create')],
                    default            => null,
                };
            @endphp
            @if($addButton)
                <a href="{{ $addButton['route'] }}"
                   class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 shrink-0">
                    {{ $addButton['label'] }}
                </a>
            @endif
            <div class="admin-prod-filter-row shrink-0" role="tablist" aria-label="Filter by role">
                <a href="{{ route('admin.customers.index') }}"
                    class="admin-prod-filter-tab {{ !request('role') ? 'admin-prod-filter-tab--active' : '' }}"
                    @if(!request('role')) aria-current="page" @endif>
                    All
                </a>
                <a href="{{ route('admin.customers.index', ['role' => 'agent']) }}"
                    class="admin-prod-filter-tab {{ request('role') == 'agent' ? 'admin-prod-filter-tab--active' : '' }}"
                    @if(request('role') == 'agent') aria-current="page" @endif>
                    Agents
                </a>
                <a href="{{ route('admin.customers.index', ['role' => 'teamleader']) }}"
                    class="admin-prod-filter-tab {{ request('role') == 'teamleader' ? 'admin-prod-filter-tab--active' : '' }}"
                    @if(request('role') == 'teamleader') aria-current="page" @endif>
                    Team leaders
                </a>
                <a href="{{ route('admin.customers.index', ['role' => 'regional_manager']) }}"
                    class="admin-prod-filter-tab {{ request('role') == 'regional_manager' ? 'admin-prod-filter-tab--active' : '' }}"
                    @if(request('role') == 'regional_manager') aria-current="page" @endif>
                    Regional managers
                </a>
                <a href="{{ route('admin.customers.index', ['role' => 'subadmin']) }}"
                    class="admin-prod-filter-tab {{ request('role') == 'subadmin' ? 'admin-prod-filter-tab--active' : '' }}"
                    @if(request('role') == 'subadmin') aria-current="page" @endif>
                    Leaders
                </a>
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
                                <td class="admin-prod-cell-actions">
                                    <div class="flex flex-col items-end gap-2 min-w-[260px]">
                                        <details class="w-full">
                                            <summary class="cursor-pointer text-xs font-semibold text-slate-600 hover:text-[#fa8900] list-none text-right">
                                                Reset password
                                            </summary>
                                            <form method="POST" action="{{ route('admin.users.reset-password', $user) }}"
                                                class="mt-2 flex flex-wrap items-center justify-end gap-2">
                                                @csrf
                                                <input type="password" name="password" required minlength="8"
                                                    placeholder="New password" class="admin-prod-input w-36 py-1.5 text-sm">
                                                <input type="password" name="password_confirmation" required minlength="8"
                                                    placeholder="Confirm" class="admin-prod-input w-32 py-1.5 text-sm">
                                                <button type="submit" class="admin-prod-link whitespace-nowrap text-sm">Save</button>
                                            </form>
                                        </details>
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
                                    </div>
                                </td>
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
            @if($customers->hasPages())
                <div class="admin-prod-pagination">
                    {{ $customers->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
