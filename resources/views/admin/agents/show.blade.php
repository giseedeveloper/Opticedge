<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        @php
            $fromCustomers = request('from') === 'customers';
            $backUrl = $fromCustomers
                ? route('admin.customers.index', request()->only('role'))
                : route('admin.agents.index');
            $backLabel = $fromCustomers ? 'Back to all users' : 'Back to agents';
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
                <p class="admin-prod-eyebrow">Agent</p>
                <h1 class="admin-prod-title">{{ $agent->name }}</h1>
                <p class="admin-prod-subtitle">
                    {{ $agent->email }}
                    @if($agent->phone)
                        <span class="text-slate-400">·</span> {{ $agent->phone }}
                    @endif
                    @if($agent->branch)
                        <span class="text-slate-400">·</span> {{ $agent->branch->name }}
                    @endif
                    @if(\Illuminate\Support\Facades\Schema::hasColumn('users', 'team_leader_id') && $agent->teamLeader)
                        <span class="text-slate-400">·</span> TL: {{ $agent->teamLeader->name }}
                    @endif
                </p>
            </div>
        </div>

        <div class="admin-clay-panel admin-prod-form-shell overflow-hidden mb-6">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Update agent information</h2>
                <p class="admin-prod-form-hint">Edit name, email, phone, or branch. Leave password blank to keep the current one. If you change branch, it must match this agent’s team leader’s branch (or update the team leader below).</p>
            </div>
            <form method="POST" action="{{ route('admin.agents.update', $agent) }}" enctype="multipart/form-data" class="admin-prod-form-body space-y-6">
                @csrf
                @method('PATCH')
                @include('admin.partials.profile-photo-field', ['user' => $agent])
                <div>
                    <label for="agent_edit_name" class="admin-prod-label">Name</label>
                    <input type="text" id="agent_edit_name" name="name" value="{{ old('name', $agent->name) }}" required
                        class="admin-prod-input" autocomplete="name">
                    @error('name')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="agent_edit_email" class="admin-prod-label">Email</label>
                    <input type="email" id="agent_edit_email" name="email" value="{{ old('email', $agent->email) }}"
                        required class="admin-prod-input" autocomplete="email">
                    @error('email')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="agent_edit_phone" class="admin-prod-label">Phone</label>
                    <input type="tel" id="agent_edit_phone" name="phone" value="{{ old('phone', $agent->phone) }}"
                        class="admin-prod-input" autocomplete="tel" placeholder="e.g. +255 …">
                    @error('phone')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="agent_edit_branch_id" class="admin-prod-label">Branch</label>
                    <select name="branch_id" id="agent_edit_branch_id" class="admin-prod-select w-full max-w-xl">
                        <option value="">— No branch —</option>
                        @foreach($branches ?? [] as $branch)
                            <option value="{{ $branch->id }}"
                                @selected((string) old('branch_id', $agent->branch_id) === (string) $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('branch_id')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="agent_edit_password" class="admin-prod-label">New password</label>
                    <input type="password" id="agent_edit_password" name="password" class="admin-prod-input"
                        autocomplete="new-password" placeholder="Leave blank to keep current password">
                    @error('password')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="agent_edit_password_confirmation" class="admin-prod-label">Confirm new password</label>
                    <input type="password" id="agent_edit_password_confirmation" name="password_confirmation"
                        class="admin-prod-input" autocomplete="new-password">
                </div>
                <div class="admin-prod-form-footer !mt-0">
                    <button type="submit" class="admin-prod-btn-primary px-6">Save changes</button>
                </div>
            </form>
        </div>

        @if(\Illuminate\Support\Facades\Schema::hasColumn('users', 'team_leader_id'))
            <div class="admin-clay-panel admin-prod-form-shell overflow-hidden mb-6">
                <div class="admin-prod-form-head">
                    <h2 class="admin-prod-form-title">Team leader</h2>
                    <p class="admin-prod-form-hint">Who this agent reports to. Clear the selection to remove.</p>
                </div>
                <form method="POST" action="{{ route('admin.agents.update-team-leader', $agent) }}" class="admin-prod-form-body space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label for="team_leader_id" class="admin-prod-label">Team leader</label>
                        <select name="team_leader_id" id="team_leader_id" class="admin-prod-select w-full max-w-xl">
                            <option value="">— None —</option>
                            @foreach($teamLeaders ?? [] as $tl)
                                <option value="{{ $tl->id }}" @selected($agent->team_leader_id == $tl->id)>
                                    {{ $tl->name }}@if($tl->branch) ({{ $tl->branch->name }})@endif
                                </option>
                            @endforeach
                        </select>
                        @error('team_leader_id')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="admin-prod-form-footer !mt-0">
                        <button type="submit" class="admin-prod-btn-primary px-6">Save</button>
                    </div>
                </form>
            </div>
        @endif

        <div class="admin-clay-panel overflow-hidden">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Assigned products</h2>
                <p class="admin-prod-form-hint">Inventory allocated to this agent.</p>
            </div>
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Product</th>
                            <th scope="col" class="admin-prod-th">Assigned</th>
                            <th scope="col" class="admin-prod-th">Sold</th>
                            <th scope="col" class="admin-prod-th">Remaining</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $a)
                            <tr>
                                <td class="font-medium text-[#232f3e]">
                                    @if($a->product)
                                        {{ $a->product->category->name ?? '—' }} – {{ $a->product->name ?? 'Unknown model' }}
                                    @else
                                        <span class="text-amber-700">Unknown product (removed)</span>
                                    @endif
                                </td>
                                <td class="font-variant-numeric text-slate-600">{{ $a->quantity_assigned }}</td>
                                <td class="font-variant-numeric text-slate-600">{{ $a->quantity_sold }}</td>
                                <td class="font-variant-numeric text-slate-600">
                                    {{ $a->quantity_assigned - $a->quantity_sold }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-slate-500 py-10">No products assigned yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
