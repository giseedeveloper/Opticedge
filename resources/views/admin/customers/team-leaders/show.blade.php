<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        @php
            $fromCustomers = request('from') === 'customers';
            $backUrl = $fromCustomers
                ? route('admin.customers.index', request()->only('role'))
                : route('admin.customers.team-leaders.index');
            $backLabel = $fromCustomers ? 'Back to all users' : 'Back to team leaders';
            $returnQuery = request()->only(['from', 'role']);
        @endphp
        <a href="{{ $backUrl }}" class="admin-prod-back inline-flex mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            {{ $backLabel }}
        </a>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="admin-prod-alert admin-prod-alert--error mb-4" role="alert">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="admin-prod-alert admin-prod-alert--error mb-4" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="admin-prod-toolbar !mb-6">
            <div>
                <p class="admin-prod-eyebrow">Team leader</p>
                <h1 class="admin-prod-title">{{ $teamLeader->name }}</h1>
                <p class="admin-prod-subtitle">
                    {{ $teamLeader->email }}
                    @if($teamLeader->phone)
                        <span class="text-slate-400">·</span> {{ $teamLeader->phone }}
                    @endif
                    @if($teamLeader->region)
                        <span class="text-slate-400">·</span> {{ $teamLeader->region->name }}
                    @endif
                    @if($teamLeader->branch)
                        <span class="text-slate-400">·</span> {{ $teamLeader->branch->name }}
                    @endif
                    @if($teamLeader->regionalManager)
                        <span class="text-slate-400">·</span> RM: {{ $teamLeader->regionalManager->name }}
                    @endif
                </p>
            </div>
        </div>

        <div class="admin-clay-panel admin-prod-form-shell overflow-hidden mb-6">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Update team leader information</h2>
                <p class="admin-prod-form-hint">Edit name, contact, region, branch, or regional manager. Leave password blank to keep the current one. The regional manager must belong to the selected region.</p>
            </div>
            @if($regions->isEmpty() || $branches->isEmpty() || $regionalManagers->isEmpty())
                <div class="admin-prod-form-body">
                    <p class="text-sm text-slate-600">
                        @if($regions->isEmpty())
                            No regions found. Run migrations and seed regions first.
                        @elseif($branches->isEmpty())
                            No branches found. Add a branch under Stock → Branches first.
                        @else
                            Create at least one regional manager before editing hierarchy fields.
                        @endif
                    </p>
                </div>
            @else
                <form method="POST"
                    action="{{ route('admin.customers.team-leaders.update', ['teamLeader' => $teamLeader] + $returnQuery) }}"
                    class="admin-prod-form-body space-y-6">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label for="tl_edit_name" class="admin-prod-label">Name</label>
                        <input type="text" id="tl_edit_name" name="name" value="{{ old('name', $teamLeader->name) }}" required
                            class="admin-prod-input" autocomplete="name">
                        @error('name')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="tl_edit_email" class="admin-prod-label">Email</label>
                        <input type="email" id="tl_edit_email" name="email" value="{{ old('email', $teamLeader->email) }}"
                            required class="admin-prod-input" autocomplete="email">
                        @error('email')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="tl_edit_phone" class="admin-prod-label">Phone</label>
                        <input type="tel" id="tl_edit_phone" name="phone" value="{{ old('phone', $teamLeader->phone) }}"
                            class="admin-prod-input" autocomplete="tel" placeholder="e.g. +255 …">
                        @error('phone')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="tl_edit_region_id" class="admin-prod-label">Region</label>
                        <select name="region_id" id="tl_edit_region_id" required class="admin-prod-select w-full max-w-xl">
                            <option value="">Select region</option>
                            @foreach($regions as $region)
                                <option value="{{ $region->id }}"
                                    @selected((string) old('region_id', $teamLeader->region_id) === (string) $region->id)>
                                    {{ $region->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('region_id')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="tl_edit_branch_id" class="admin-prod-label">Branch</label>
                        <select name="branch_id" id="tl_edit_branch_id" required class="admin-prod-select w-full max-w-xl">
                            <option value="">Select branch</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}"
                                    @selected((string) old('branch_id', $teamLeader->branch_id) === (string) $branch->id)>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('branch_id')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    @if(\Illuminate\Support\Facades\Schema::hasColumn('users', 'regional_manager_id'))
                        <div>
                            <label for="tl_edit_regional_manager_id" class="admin-prod-label">Regional manager</label>
                            <select name="regional_manager_id" id="tl_edit_regional_manager_id" required
                                class="admin-prod-select w-full max-w-xl">
                                <option value="">Select regional manager</option>
                                @foreach($regionalManagers as $rm)
                                    <option value="{{ $rm->id }}"
                                        @selected((string) old('regional_manager_id', $teamLeader->regional_manager_id) === (string) $rm->id)>
                                        {{ $rm->name }}@if(!empty($rm->region_name)) — {{ $rm->region_name }}@endif
                                    </option>
                                @endforeach
                            </select>
                            @error('regional_manager_id')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                    <div>
                        <label for="tl_edit_password" class="admin-prod-label">New password</label>
                        <input type="password" id="tl_edit_password" name="password" class="admin-prod-input"
                            autocomplete="new-password" placeholder="Leave blank to keep current password">
                        @error('password')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="tl_edit_password_confirmation" class="admin-prod-label">Confirm new password</label>
                        <input type="password" id="tl_edit_password_confirmation" name="password_confirmation"
                            class="admin-prod-input" autocomplete="new-password">
                    </div>
                    <div>
                        <label for="tl_edit_business_name" class="admin-prod-label">Organization / title (optional)</label>
                        <input type="text" id="tl_edit_business_name" name="business_name"
                            value="{{ old('business_name', $teamLeader->business_name) }}"
                            class="admin-prod-input" autocomplete="organization">
                        @error('business_name')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    @if(\Illuminate\Support\Facades\Schema::hasColumn('users', 'notes'))
                        <div>
                            <label for="tl_edit_notes" class="admin-prod-label">Other notes (optional)</label>
                            <textarea id="tl_edit_notes" name="notes" rows="3" class="admin-prod-input"
                                placeholder="Internal notes">{{ old('notes', $teamLeader->notes) }}</textarea>
                            @error('notes')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                    <div class="admin-prod-form-footer !mt-0">
                        <button type="submit" class="admin-prod-btn-primary px-6">Save changes</button>
                    </div>
                </form>
            @endif
        </div>

        @if(\Illuminate\Support\Facades\Schema::hasColumn('users', 'team_leader_id'))
            <div class="admin-clay-panel overflow-hidden">
                <div class="admin-prod-form-head">
                    <h2 class="admin-prod-form-title">Agents under this team leader</h2>
                    <p class="admin-prod-form-hint">Sales agents reporting to {{ $teamLeader->name }}.</p>
                </div>
                <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col" class="admin-prod-th">Name</th>
                                <th scope="col" class="admin-prod-th">Email</th>
                                <th scope="col" class="admin-prod-th">Branch</th>
                                <th scope="col" class="admin-prod-th admin-prod-th--end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($managedAgents as $agent)
                                <tr>
                                    <td class="font-medium text-[#232f3e]">{{ $agent->name }}</td>
                                    <td class="text-slate-600">{{ $agent->email }}</td>
                                    <td class="text-slate-600">{{ $agent->branch?->name ?? '—' }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.agents.show', ['agent' => $agent] + $returnQuery) }}"
                                            class="admin-prod-link text-sm">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-slate-500 py-10">No agents assigned yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-admin-layout>
