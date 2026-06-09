<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar !mb-0 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="admin-prod-eyebrow">Partners</p>
                <h1 class="admin-prod-title">Dealers</h1>
                <p class="admin-prod-subtitle">Review applications, approve accounts, and suspend when needed.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a href="{{ route('admin.dealers.create') }}"
                    class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Add dealer</a>
                <a href="{{ route('admin.stock.create-distribution') }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50">Add distribution sale</a>
            </div>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mt-6" role="status">
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="admin-prod-alert admin-prod-alert--error mt-6" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="mt-6 admin-clay-panel overflow-hidden">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table class="min-w-[980px]">
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Name</th>
                            <th scope="col" class="admin-prod-th">Business Name</th>
                            <th scope="col" class="admin-prod-th">Email</th>
                            <th scope="col" class="admin-prod-th">Phone</th>
                            <th scope="col" class="admin-prod-th">Ability</th>
                            <th scope="col" class="admin-prod-th">Status</th>
                            <th scope="col" class="admin-prod-th">Registered</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dealers as $dealer)
                            <tr>
                                <td class="font-semibold">
                                    <a href="{{ route('admin.dealers.show', $dealer->id) }}"
                                        class="admin-prod-link text-[#232f3e] hover:text-[#c2410c]">{{ $dealer->name }}</a>
                                </td>
                                <td class="text-slate-600">{{ $dealer->business_name ?? '—' }}</td>
                                <td class="text-slate-600">{{ $dealer->email }}</td>
                                <td class="text-slate-600">{{ $dealer->phone ?? '—' }}</td>
                                <td class="text-slate-600">{{ ($dealer->ability ?? 'fullaccess') === 'view' ? 'View only' : 'Full access' }}</td>
                                <td>
                                    @php
                                        $st = $dealer->status;
                                        $stClass =
                                            $st === 'active'
                                                ? 'admin-prod-dealer-status--active'
                                                : ($st === 'pending'
                                                    ? 'admin-prod-dealer-status--pending'
                                                    : 'admin-prod-dealer-status--suspended');
                                    @endphp
                                    <span class="admin-prod-dealer-status {{ $stClass }}">{{ ucfirst($st) }}</span>
                                </td>
                                <td class="text-slate-600 text-sm font-variant-numeric">
                                    {{ $dealer->created_at->format('M j, Y') }}
                                </td>
                                <x-admin-user-actions>
                                    <div class="admin-prod-actions flex-wrap justify-end gap-x-3 gap-y-1">
                                        <a href="{{ route('admin.dealers.show', $dealer->id) }}" class="admin-prod-link">View</a>
                                        @if($dealer->status === 'pending')
                                            <form action="{{ route('admin.dealers.approve', $dealer->id) }}" method="POST"
                                                class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="admin-prod-btn-inline admin-prod-link--success">
                                                    Approve
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.dealers.reject', $dealer->id) }}" method="POST"
                                                class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="admin-prod-btn-inline admin-prod-link--danger">
                                                    Reject
                                                </button>
                                            </form>
                                        @else
                                            @if($dealer->status === 'active')
                                                <form action="{{ route('admin.dealers.reject', $dealer->id) }}" method="POST"
                                                    class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="admin-prod-btn-inline admin-prod-link--danger">
                                                        Suspend
                                                    </button>
                                                </form>
                                            @elseif($dealer->status === 'suspended')
                                                <form action="{{ route('admin.dealers.approve', $dealer->id) }}" method="POST"
                                                    class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="admin-prod-btn-inline admin-prod-link--success">
                                                        Re-activate
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="admin-user-actions-collapse__section">
                                        <p class="admin-user-actions-collapse__label">Reset password</p>
                                        <form method="POST" action="{{ route('admin.users.reset-password', $dealer) }}"
                                            class="mt-1 flex flex-wrap items-center justify-end gap-2">
                                            @csrf
                                            <input type="password" name="password" required minlength="8"
                                                placeholder="New password" class="admin-prod-input w-36 py-1.5 text-sm">
                                            <input type="password" name="password_confirmation" required minlength="8"
                                                placeholder="Confirm" class="admin-prod-input w-32 py-1.5 text-sm">
                                            <button type="submit" class="admin-prod-link whitespace-nowrap text-sm">Save</button>
                                        </form>
                                    </div>
                                    <form action="{{ route('admin.dealers.destroy', $dealer->id) }}" method="POST"
                                        class="w-full flex justify-end"
                                        onsubmit="return confirm('Delete this dealer permanently? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="admin-prod-link text-sm text-rose-700 hover:text-rose-800">
                                            Delete
                                        </button>
                                    </form>
                                </x-admin-user-actions>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-slate-500 py-10">No dealers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
