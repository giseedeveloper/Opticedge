<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar !mb-0 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="admin-prod-eyebrow">Staff</p>
                <h1 class="admin-prod-title">Team leaders</h1>
                <p class="admin-prod-subtitle">Leaders tied to a branch and regional manager. Use the button to create a new account.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a href="{{ route('admin.customers.team-leaders.create') }}"
                    class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Add team leader</a>
                <a href="{{ route('admin.customers.index', ['role' => 'teamleader']) }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50">All in directory</a>
            </div>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mt-6" role="status">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="admin-prod-alert admin-prod-alert--error mt-6" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="mt-6 admin-clay-panel overflow-hidden">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table class="min-w-[900px]">
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Name</th>
                            <th scope="col" class="admin-prod-th">Email</th>
                            <th scope="col" class="admin-prod-th">Phone</th>
                            <th scope="col" class="admin-prod-th">Region</th>
                            <th scope="col" class="admin-prod-th">Branch</th>
                            <th scope="col" class="admin-prod-th">Regional manager</th>
                            <th scope="col" class="admin-prod-th">Status</th>
                            <th scope="col" class="admin-prod-th">Joined</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teamLeaders as $user)
                            <tr>
                                <td class="font-semibold text-[#232f3e]">{{ $user->name }}</td>
                                <td class="text-slate-600">{{ $user->email }}</td>
                                <td class="text-slate-600">{{ $user->phone ?? '—' }}</td>
                                <td class="text-slate-600">{{ $user->region?->name ?? '—' }}</td>
                                <td class="text-slate-600">{{ $user->branch?->name ?? '—' }}</td>
                                <td class="text-slate-600">{{ $user->regionalManager?->name ?? '—' }}</td>
                                <td>
                                    @php $isActive = ($user->status ?? 'active') === 'active'; @endphp
                                    <span class="admin-prod-user-status {{ $isActive ? 'admin-prod-user-status--active' : 'admin-prod-user-status--inactive' }}">
                                        {{ ucfirst($user->status ?? 'active') }}
                                    </span>
                                </td>
                                <td class="font-variant-numeric text-slate-600 text-sm">{{ $user->created_at->format('M j, Y') }}</td>
                                <x-admin-user-actions>
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
                                    @if($isActive)
                                        <form method="POST" action="{{ route('admin.customers.deactivate', ['user' => $user->id]) }}"
                                            class="w-full flex justify-end"
                                            onsubmit="return confirm('Deactivate this team leader? They will not be able to log in until reactivated.');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="admin-prod-link text-sm text-red-600 hover:text-red-700">Deactivate</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.customers.activate', ['user' => $user->id]) }}"
                                            class="w-full flex justify-end"
                                            onsubmit="return confirm('Activate this team leader? They will be able to log in again.');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="admin-prod-link text-sm text-emerald-700 hover:text-emerald-800">Activate</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.customers.destroy', ['user' => $user->id]) }}"
                                        class="w-full flex justify-end"
                                        onsubmit="return confirm('Delete this team leader permanently? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="admin-prod-link text-sm text-rose-700 hover:text-rose-800">Delete</button>
                                    </form>
                                </x-admin-user-actions>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-slate-500 py-10">No team leaders yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
