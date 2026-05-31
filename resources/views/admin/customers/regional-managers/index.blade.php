<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar !mb-0 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="admin-prod-eyebrow">Staff</p>
                <h1 class="admin-prod-title">Regional managers</h1>
                <p class="admin-prod-subtitle">People who oversee a region. They distribute devices to team leaders and agents.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a href="{{ route('admin.customers.regional-managers.create') }}"
                    class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Add regional manager</a>
                <a href="{{ route('admin.customers.index', ['role' => 'regional_manager']) }}"
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
                <table class="min-w-[720px]">
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Name</th>
                            <th scope="col" class="admin-prod-th">Email</th>
                            <th scope="col" class="admin-prod-th">Phone</th>
                            <th scope="col" class="admin-prod-th">Region</th>
                            <th scope="col" class="admin-prod-th">Status</th>
                            <th scope="col" class="admin-prod-th">Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($managers as $user)
                            <tr>
                                <td class="font-semibold text-[#232f3e]">{{ $user->name }}</td>
                                <td class="text-slate-600">{{ $user->email }}</td>
                                <td class="text-slate-600">{{ $user->phone ?? '—' }}</td>
                                <td class="text-slate-600">{{ $user->region?->name ?? '—' }}</td>
                                <td>
                                    @php $isActive = ($user->status ?? 'active') === 'active'; @endphp
                                    <span class="admin-prod-user-status {{ $isActive ? 'admin-prod-user-status--active' : 'admin-prod-user-status--inactive' }}">
                                        {{ ucfirst($user->status ?? 'active') }}
                                    </span>
                                </td>
                                <td class="font-variant-numeric text-slate-600 text-sm">{{ $user->created_at->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-slate-500 py-10">No regional managers yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($managers->hasPages())
                <div class="admin-prod-pagination">{{ $managers->links() }}</div>
            @endif
        </div>
    </div>
</x-admin-layout>
