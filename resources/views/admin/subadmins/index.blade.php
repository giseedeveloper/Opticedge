<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Administration</p>
                <h1 class="admin-prod-title">Leaders</h1>
                <p class="admin-prod-subtitle">Manage leader accounts and assigned roles.</p>
            </div>
            <div class="flex flex-wrap gap-2 shrink-0">
                <a href="{{ route('admin.subadmins.create') }}" class="admin-prod-btn-ghost">Add leader</a>
            </div>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="admin-prod-alert admin-prod-alert--error mb-4" role="alert">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="admin-prod-alert admin-prod-alert--error mb-4" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="admin-clay-panel overflow-hidden">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table class="min-w-[980px]">
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Name</th>
                            <th scope="col" class="admin-prod-th">Email</th>
                            <th scope="col" class="admin-prod-th">Phone</th>
                            <th scope="col" class="admin-prod-th">Role</th>
                            <th scope="col" class="admin-prod-th">Status</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subadmins as $subadmin)
                            <tr>
                                <td class="font-semibold text-[#232f3e]">{{ $subadmin->name }}</td>
                                <td class="text-slate-600">{{ $subadmin->email }}</td>
                                <td class="text-slate-600">{{ $subadmin->phone ?? '—' }}</td>
                                <td class="text-slate-600">{{ $subadmin->subadminRole?->name ?? '—' }}</td>
                                <td>
                                    @php
                                        $active = ($subadmin->status ?? '') === 'active';
                                    @endphp
                                    <span class="admin-prod-user-status {{ $active ? 'admin-prod-user-status--active' : 'admin-prod-user-status--inactive' }}">
                                        {{ ucfirst($subadmin->status ?? 'N/A') }}
                                    </span>
                                </td>
                                <x-admin-user-actions>
                                    <x-admin-reset-password-form :user="$subadmin" />
                                    @if($active)
                                        <form method="POST" action="{{ route('admin.subadmins.deactivate', $subadmin) }}" class="w-full flex justify-end"
                                            onsubmit="return confirm('Deactivate this leader? They will not be able to log in until reactivated.');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="admin-prod-link text-sm text-red-600 hover:text-red-700">Deactivate</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.subadmins.activate', $subadmin) }}" class="w-full flex justify-end"
                                            onsubmit="return confirm('Activate this leader? They will be able to log in again.');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="admin-prod-link text-sm text-emerald-700 hover:text-emerald-800">Activate</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.subadmins.destroy', $subadmin) }}" class="w-full flex justify-end"
                                        onsubmit="return confirm('Delete this leader permanently? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="admin-prod-link text-sm text-rose-700 hover:text-rose-800">Delete</button>
                                    </form>
                                </x-admin-user-actions>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-slate-500 py-10">
                                    No leaders yet.
                                    <a href="{{ route('admin.subadmins.create') }}" class="admin-prod-link">Add a leader</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
