<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Sales team</p>
                <h1 class="admin-prod-title">Agents</h1>
                <p class="admin-prod-subtitle">Manage agents and assign products for them to sell.</p>
            </div>
            <div class="flex flex-wrap gap-2 shrink-0">
                <a href="{{ route('admin.agents.create') }}" class="admin-prod-btn-ghost">Add agent</a>
                <a href="{{ route('admin.agents.assign-products') }}" class="admin-prod-btn-primary">Assign to regional manager</a>
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
                            <th scope="col" class="admin-prod-th">Branch</th>
                            @if(\Illuminate\Support\Facades\Schema::hasColumn('users', 'team_leader_id'))
                                <th scope="col" class="admin-prod-th">Team leader</th>
                            @endif
                            <th scope="col" class="admin-prod-th">Status</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($agents as $agent)
                            <tr>
                                <td class="font-semibold text-[#232f3e]">{{ $agent->name }}</td>
                                <td class="text-slate-600">{{ $agent->email }}</td>
                                <td class="text-slate-600">{{ $agent->phone ?? '—' }}</td>
                                <td class="text-slate-600">{{ $agent->branch?->name ?? '—' }}</td>
                                @if(\Illuminate\Support\Facades\Schema::hasColumn('users', 'team_leader_id'))
                                    <td class="text-slate-600">{{ $agent->teamLeader?->name ?? '—' }}</td>
                                @endif
                                <td>
                                    @php
                                        $active = ($agent->status ?? '') === 'active';
                                    @endphp
                                    <span
                                        class="admin-prod-user-status {{ $active ? 'admin-prod-user-status--active' : 'admin-prod-user-status--inactive' }}">
                                        {{ ucfirst($agent->status ?? 'N/A') }}
                                    </span>
                                </td>
                                <td class="admin-prod-cell-actions">
                                    <div class="flex flex-col items-end gap-2 min-w-[260px]">
                                        <a href="{{ route('admin.agents.show', $agent) }}" class="admin-prod-link">View &amp; assign</a>
                                        @if(\Illuminate\Support\Facades\Schema::hasColumn('users', 'team_leader_id'))
                                        <details class="w-full">
                                            <summary class="cursor-pointer text-xs font-semibold text-slate-600 hover:text-[#fa8900] list-none">
                                                Team leader
                                            </summary>
                                            <form method="POST" action="{{ route('admin.agents.update-team-leader', $agent) }}"
                                                class="mt-2 flex flex-wrap items-center justify-end gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <select name="team_leader_id" class="admin-prod-input w-44 py-1.5 text-sm">
                                                    <option value="">None</option>
                                                    @foreach($teamLeaders ?? [] as $tl)
                                                        <option value="{{ $tl->id }}" @selected($agent->team_leader_id == $tl->id)>
                                                            {{ $tl->name }}@if($tl->branch) ({{ $tl->branch->name }})@endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="admin-prod-link whitespace-nowrap text-sm">Save</button>
                                            </form>
                                        </details>
                                        @endif
                                        <details class="w-full">
                                            <summary class="cursor-pointer text-xs font-semibold text-slate-600 hover:text-[#fa8900] list-none">
                                                Transfer branch
                                            </summary>
                                            <form method="POST" action="{{ route('admin.agents.transfer-branch', $agent) }}"
                                                class="mt-2 flex flex-wrap items-center justify-end gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <select name="branch_id" class="admin-prod-input w-40 py-1.5 text-sm">
                                                    <option value="">No branch</option>
                                                    @foreach(\App\Models\Branch::orderBy('name')->get() as $branch)
                                                        <option value="{{ $branch->id }}" {{ $agent->branch_id == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="admin-prod-link whitespace-nowrap text-sm">Transfer</button>
                                            </form>
                                        </details>
                                        <details class="w-full">
                                            <summary class="cursor-pointer text-xs font-semibold text-slate-600 hover:text-[#fa8900] list-none">
                                                Reset password
                                            </summary>
                                            <form method="POST" action="{{ route('admin.users.reset-password', $agent) }}"
                                                class="mt-2 flex flex-wrap items-center justify-end gap-2">
                                                @csrf
                                                <input type="password" name="password" required minlength="8"
                                                    placeholder="New password" class="admin-prod-input w-36 py-1.5 text-sm">
                                                <input type="password" name="password_confirmation" required minlength="8"
                                                    placeholder="Confirm" class="admin-prod-input w-32 py-1.5 text-sm">
                                                <button type="submit" class="admin-prod-link whitespace-nowrap text-sm">Save</button>
                                            </form>
                                        </details>
                                        @if($active)
                                            <form method="POST" action="{{ route('admin.agents.deactivate', $agent) }}" class="w-full flex justify-end"
                                                onsubmit="return confirm('Deactivate this agent? They will not be able to log in until reactivated.');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="admin-prod-link text-sm text-red-600 hover:text-red-700">Deactivate</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.agents.activate', $agent) }}" class="w-full flex justify-end"
                                                onsubmit="return confirm('Activate this agent? They will be able to log in again.');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="admin-prod-link text-sm text-emerald-700 hover:text-emerald-800">Activate</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.agents.destroy', $agent) }}" class="w-full flex justify-end"
                                            onsubmit="return confirm('Delete this agent permanently? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="admin-prod-link text-sm text-rose-700 hover:text-rose-800">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ \Illuminate\Support\Facades\Schema::hasColumn('users', 'team_leader_id') ? 7 : 6 }}" class="text-center text-slate-500 py-10">
                                    No agents yet.
                                    <a href="{{ route('admin.agents.create') }}" class="admin-prod-link">Add an agent</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
