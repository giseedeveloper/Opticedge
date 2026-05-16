<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page admin-prod-page--narrow">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-8">
            <div>
                <p class="admin-prod-eyebrow">Sales team</p>
                <h1 class="admin-prod-title">Add agent</h1>
                <p class="admin-prod-subtitle">Create a new agent. They get a dashboard and can sell products you assign.</p>
            </div>
            <a href="{{ route('admin.agents.index') }}" class="admin-prod-back shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to agents
            </a>
        </div>

        <div class="admin-clay-panel admin-prod-form-shell overflow-hidden">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Account</h2>
                <p class="admin-prod-form-hint">Name, email, phone, branch, optional team leader, and password for sign-in.</p>
            </div>
            <form method="POST" action="{{ route('admin.agents.store') }}" class="admin-prod-form-body space-y-6">
                @csrf
                <div>
                    <label for="name" class="admin-prod-label">Name</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                        class="admin-prod-input" autocomplete="name">
                    @error('name')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="email" class="admin-prod-label">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                        class="admin-prod-input" autocomplete="email">
                    @error('email')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="phone" class="admin-prod-label">Phone</label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
                        class="admin-prod-input" autocomplete="tel" placeholder="e.g. +255 …">
                    @error('phone')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="branch_id" class="admin-prod-label">Branch</label>
                    <select name="branch_id" id="branch_id" class="admin-prod-select w-full max-w-xl">
                        <option value="">— No branch —</option>
                        @foreach($branches ?? [] as $branch)
                            <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="admin-prod-form-hint !mt-1.5">Set the rep’s home branch (e.g. Mwanza, Mbeya, Dar) so branch filters, reports, and mobile branch views attribute sales correctly.</p>
                    @error('branch_id')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                @if(Schema::hasColumn('users', 'team_leader_id'))
                    <div>
                        <label for="team_leader_id" class="admin-prod-label">Team leader</label>
                        <select name="team_leader_id" id="team_leader_id" class="admin-prod-select w-full max-w-xl">
                            <option value="">— None —</option>
                            @foreach($teamLeaders ?? [] as $tl)
                                <option value="{{ $tl->id }}" @selected((string) old('team_leader_id') === (string) $tl->id)>
                                    {{ $tl->name }}@if($tl->branch) ({{ $tl->branch->name }})@endif
                                </option>
                            @endforeach
                        </select>
                        <p class="admin-prod-form-hint !mt-1.5">If you set a branch, pick a team leader for that same branch when possible.</p>
                        @error('team_leader_id')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
                <div>
                    <label for="password" class="admin-prod-label">Password</label>
                    <input type="password" id="password" name="password" required class="admin-prod-input"
                        autocomplete="new-password">
                    @error('password')
                        <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="admin-prod-label">Confirm password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                        class="admin-prod-input" autocomplete="new-password">
                </div>
                <div class="admin-prod-form-footer">
                    <a href="{{ route('admin.agents.index') }}" class="admin-prod-btn-ghost">Cancel</a>
                    <button type="submit" class="admin-prod-btn-primary px-8">Create agent</button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
