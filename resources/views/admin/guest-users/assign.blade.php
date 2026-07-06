<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page admin-prod-form-wide">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-8">
            <div>
                <p class="admin-prod-eyebrow">Users</p>
                <h1 class="admin-prod-title">Invite {{ $guest->name }}</h1>
                <p class="admin-prod-subtitle">Send a vendor invitation. The guest must accept before they can join your team.</p>
            </div>
            <a href="{{ route('admin.guest-users.index') }}" class="admin-prod-back shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to guest users
            </a>
        </div>

        @if ($errors->any())
            <div class="admin-prod-alert admin-prod-alert--error mb-6" role="alert">
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="admin-clay-panel overflow-hidden mb-6">
            <div class="admin-prod-form-head">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4 min-w-0">
                        @if ($guest->avatar)
                            <img src="{{ $guest->avatar }}" alt="" class="h-14 w-14 shrink-0 rounded-2xl object-cover border border-white shadow-sm">
                        @else
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 text-lg font-bold text-slate-500 border border-white shadow-sm">
                                {{ strtoupper(substr($guest->name, 0, 1)) }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="text-base font-bold text-slate-900 truncate">{{ $guest->name }}</p>
                            <p class="text-sm text-slate-600 truncate">{{ $guest->email }}</p>
                            @if ($guest->created_at)
                                <p class="text-xs text-slate-500 mt-0.5">Registered {{ $guest->created_at->format('M j, Y') }}</p>
                            @endif
                        </div>
                    </div>
                    <span class="admin-prod-badge shrink-0">Guest account</span>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.guest-users.assign.store', $guest) }}">
            @csrf

            <div class="admin-clay-panel admin-prod-form-shell overflow-hidden">
                <div class="admin-prod-form-head">
                    <h2 class="admin-prod-form-title">Role &amp; contact</h2>
                    <p class="admin-prod-form-hint">Choose the role they will have after accepting, and update their phone if needed.</p>
                </div>
                <div class="admin-prod-form-body space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="role" class="admin-prod-label">Role</label>
                            <select name="role" id="role" class="admin-prod-select" required>
                                <option value="agent" @selected(old('role', 'agent') === 'agent')>Agent</option>
                                <option value="teamleader" @selected(old('role') === 'teamleader')>Team leader</option>
                                <option value="regional_manager" @selected(old('role') === 'regional_manager')>Regional manager</option>
                            </select>
                            @error('role')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="phone" class="admin-prod-label">Phone</label>
                            <input type="tel" id="phone" name="phone" value="{{ old('phone', $guest->phone) }}"
                                class="admin-prod-input" autocomplete="tel" placeholder="e.g. +255 …">
                            @error('phone')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-clay-panel admin-prod-form-shell overflow-hidden mt-6" id="placement-section">
                <div class="admin-prod-form-head">
                    <h2 class="admin-prod-form-title">Placement</h2>
                    <p class="admin-prod-form-hint" id="placement-hint">Branch and reporting structure for this role.</p>
                </div>
                <div class="admin-prod-form-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div data-role-field="regional_manager teamleader agent">
                            <label for="branch_id" class="admin-prod-label">Branch</label>
                            <select name="branch_id" id="branch_id" class="admin-prod-select">
                                <option value="">— No branch —</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        <div data-role-field="regional_manager teamleader">
                            <label for="region_id" class="admin-prod-label">Region</label>
                            <select name="region_id" id="region_id" class="admin-prod-select">
                                <option value="">— Select region —</option>
                                @foreach ($regions as $region)
                                    <option value="{{ $region->id }}" @selected((string) old('region_id') === (string) $region->id)>{{ $region->name }}</option>
                                @endforeach
                            </select>
                            @error('region_id')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        <div data-role-field="teamleader">
                            <label for="regional_manager_id" class="admin-prod-label">Regional manager</label>
                            <select name="regional_manager_id" id="regional_manager_id" class="admin-prod-select">
                                <option value="">— Select regional manager —</option>
                                @foreach ($regionalManagers as $manager)
                                    <option value="{{ $manager->id }}" @selected((string) old('regional_manager_id') === (string) $manager->id)>{{ $manager->name }}</option>
                                @endforeach
                            </select>
                            @error('regional_manager_id')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        <div data-role-field="agent">
                            <label for="team_leader_id" class="admin-prod-label">Team leader</label>
                            <select name="team_leader_id" id="team_leader_id" class="admin-prod-select">
                                <option value="">— None —</option>
                                @foreach ($teamLeaders as $leader)
                                    <option value="{{ $leader->id }}" @selected((string) old('team_leader_id') === (string) $leader->id)>{{ $leader->name }}</option>
                                @endforeach
                            </select>
                            @error('team_leader_id')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-clay-panel admin-prod-form-shell overflow-hidden mt-6">
                <div class="admin-prod-form-head">
                    <h2 class="admin-prod-form-title">Additional details</h2>
                    <p class="admin-prod-form-hint">Optional organization title and internal notes.</p>
                </div>
                <div class="admin-prod-form-body space-y-6">
                    <div>
                        <label for="business_name" class="admin-prod-label">Business name</label>
                        <input type="text" id="business_name" name="business_name" value="{{ old('business_name') }}"
                            class="admin-prod-input" placeholder="Organization or title (optional)">
                        @error('business_name')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="notes" class="admin-prod-label">Notes</label>
                        <textarea id="notes" name="notes" rows="3" class="admin-prod-textarea"
                            placeholder="Internal notes visible to admins only">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="admin-clay-panel admin-prod-form-shell overflow-hidden mt-6">
                <div class="admin-prod-form-head">
                    <h2 class="admin-prod-form-title">Invitation message</h2>
                    <p class="admin-prod-form-hint">A short note included in the email the guest receives.</p>
                </div>
                <div class="admin-prod-form-body">
                    <div>
                        <label for="message" class="admin-prod-label">Message to guest <span class="font-normal text-slate-500">(optional)</span></label>
                        <textarea id="message" name="message" rows="4" class="admin-prod-textarea"
                            placeholder="We would like you to join our team as an agent.">{{ old('message') }}</textarea>
                        @error('message')
                            <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="admin-prod-form-footer !mt-6">
                        <a href="{{ route('admin.guest-users.index') }}" class="admin-prod-btn-ghost">Cancel</a>
                        <button type="submit" class="admin-prod-btn-primary px-8">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Send invitation
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const roleSelect = document.getElementById('role');
            const fields = document.querySelectorAll('[data-role-field]');
            const placementHint = document.getElementById('placement-hint');

            const hints = {
                agent: 'Set the agent’s branch and optional team leader.',
                teamleader: 'Region, branch, and regional manager are required for team leaders.',
                regional_manager: 'Assign the region this manager will oversee.',
            };

            function syncFields() {
                const role = roleSelect.value;

                if (placementHint && hints[role]) {
                    placementHint.textContent = hints[role];
                }

                fields.forEach((el) => {
                    const roles = el.getAttribute('data-role-field').split(' ');
                    const visible = roles.includes(role);
                    el.style.display = visible ? '' : 'none';
                    el.querySelectorAll('select, input').forEach((input) => {
                        input.disabled = !visible;
                    });
                });
            }

            roleSelect.addEventListener('change', syncFields);
            syncFields();
        })();
    </script>
</x-admin-layout>
