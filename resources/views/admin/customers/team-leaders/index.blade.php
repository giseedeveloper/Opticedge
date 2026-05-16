<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page" x-data="{ showForm: {{ $errors->any() ? 'true' : 'false' }} }">
        <div class="admin-prod-toolbar !mb-0 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="admin-prod-eyebrow">Users & Dealers</p>
                <h1 class="admin-prod-title">Team leaders</h1>
                <p class="admin-prod-subtitle">Leaders tied to a branch and regional manager. Use the button to create a new account.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <button type="button" @click="showForm = !showForm"
                    class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                    <span x-show="!showForm">Add team leader</span>
                    <span x-show="showForm" x-cloak>Close form</span>
                </button>
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

        <div x-show="showForm" x-cloak x-transition class="mt-6 admin-clay-panel admin-prod-form-shell overflow-hidden">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">New team leader</h2>
                <p class="admin-prod-form-hint">The regional manager must be assigned to the same region you select.</p>
            </div>
            @if($regions->isEmpty())
                <div class="admin-prod-form-body">
                    <p class="text-sm text-slate-600">No regions found. Run migrations and seed Tanzanian regions first.</p>
                </div>
            @elseif($branches->isEmpty())
                <div class="admin-prod-form-body">
                    <p class="text-sm text-slate-600">No branches found. Add a branch under <strong>Stock → Branches</strong> first.</p>
                </div>
            @elseif($regionalManagers->isEmpty())
                <div class="admin-prod-form-body">
                    <p class="text-sm text-slate-600">Create at least one regional manager first (sidebar: <strong>Regional managers</strong>).</p>
                </div>
            @else
                <form method="POST" action="{{ route('admin.customers.team-leaders.store') }}" class="admin-prod-form-body space-y-6">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="tl_name" class="admin-prod-label">Name</label>
                            <input type="text" id="tl_name" name="tl[name]" value="{{ old('tl.name') }}" required class="admin-prod-input" autocomplete="name">
                            @error('tl.name')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="tl_phone" class="admin-prod-label">Phone</label>
                            <input type="tel" id="tl_phone" name="tl[phone]" value="{{ old('tl.phone') }}" class="admin-prod-input" autocomplete="tel" placeholder="e.g. +255 …">
                            @error('tl.phone')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label for="tl_email" class="admin-prod-label">Email</label>
                            <input type="email" id="tl_email" name="tl[email]" value="{{ old('tl.email') }}" required class="admin-prod-input" autocomplete="email">
                            @error('tl.email')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="tl_region_id" class="admin-prod-label">Region</label>
                            <select id="tl_region_id" name="tl[region_id]" required class="admin-prod-input">
                                <option value="">Select region</option>
                                @foreach ($regions as $region)
                                    <option value="{{ $region->id }}" @selected(old('tl.region_id') == $region->id)>{{ $region->name }}</option>
                                @endforeach
                            </select>
                            @error('tl.region_id')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="tl_branch_id" class="admin-prod-label">Branch</label>
                            <select id="tl_branch_id" name="tl[branch_id]" required class="admin-prod-input">
                                <option value="">Select branch</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected(old('tl.branch_id') == $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('tl.branch_id')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label for="tl_regional_manager_id" class="admin-prod-label">Regional manager</label>
                            <select id="tl_regional_manager_id" name="tl[regional_manager_id]" required class="admin-prod-input">
                                <option value="">Select regional manager</option>
                                @foreach ($regionalManagers as $rmUser)
                                    <option value="{{ $rmUser->id }}" @selected(old('tl.regional_manager_id') == $rmUser->id)>
                                        {{ $rmUser->name }}
                                        @if($rmUser->relationLoaded('region') && $rmUser->region)
                                            — {{ $rmUser->region->name }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('tl.regional_manager_id')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="tl_password" class="admin-prod-label">Password</label>
                            <input type="password" id="tl_password" name="tl[password]" required minlength="8" class="admin-prod-input" autocomplete="new-password">
                            @error('tl.password')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="tl_password_confirmation" class="admin-prod-label">Confirm password</label>
                            <input type="password" id="tl_password_confirmation" name="tl[password_confirmation]" required minlength="8" class="admin-prod-input" autocomplete="new-password">
                        </div>
                        <div class="md:col-span-2">
                            <label for="tl_business_name" class="admin-prod-label">Organization / title (optional)</label>
                            <input type="text" id="tl_business_name" name="tl[business_name]" value="{{ old('tl.business_name') }}" class="admin-prod-input" autocomplete="organization">
                            @error('tl.business_name')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label for="tl_notes" class="admin-prod-label">Other notes (optional)</label>
                            <textarea id="tl_notes" name="tl[notes]" rows="3" class="admin-prod-input" placeholder="Internal notes">{{ old('tl.notes') }}</textarea>
                            @error('tl.notes')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="admin-prod-form-footer !mt-0">
                        <button type="button" @click="showForm = false" class="admin-prod-btn-ghost">Cancel</button>
                        <button type="submit" class="admin-prod-btn-primary px-8">Create account</button>
                    </div>
                </form>
            @endif
        </div>

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
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-slate-500 py-10">No team leaders yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($teamLeaders->hasPages())
                <div class="admin-prod-pagination">{{ $teamLeaders->links() }}</div>
            @endif
        </div>
    </div>
</x-admin-layout>
