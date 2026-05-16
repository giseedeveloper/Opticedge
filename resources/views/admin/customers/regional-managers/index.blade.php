<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page" x-data="{ showForm: {{ $errors->any() ? 'true' : 'false' }} }">
        <div class="admin-prod-toolbar !mb-0 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="admin-prod-eyebrow">Users & Dealers</p>
                <h1 class="admin-prod-title">Regional managers</h1>
                <p class="admin-prod-subtitle">People who oversee a region. Use the button to create a new account.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <button type="button" @click="showForm = !showForm"
                    class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                    <span x-show="!showForm">Add regional manager</span>
                    <span x-show="showForm" x-cloak>Close form</span>
                </button>
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

        <div x-show="showForm" x-cloak x-transition class="mt-6 admin-clay-panel admin-prod-form-shell overflow-hidden">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">New regional manager</h2>
                <p class="admin-prod-form-hint">Name, contact, region, password, and optional notes.</p>
            </div>
            @if($regions->isEmpty())
                <div class="admin-prod-form-body">
                    <p class="text-sm text-slate-600">No regions found. Run migrations and seed Tanzanian regions first.</p>
                </div>
            @else
                <form method="POST" action="{{ route('admin.customers.regional-managers.store') }}" class="admin-prod-form-body space-y-6">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="rm_name" class="admin-prod-label">Name</label>
                            <input type="text" id="rm_name" name="rm[name]" value="{{ old('rm.name') }}" required class="admin-prod-input" autocomplete="name">
                            @error('rm.name')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="rm_phone" class="admin-prod-label">Phone</label>
                            <input type="tel" id="rm_phone" name="rm[phone]" value="{{ old('rm.phone') }}" class="admin-prod-input" autocomplete="tel" placeholder="e.g. +255 …">
                            @error('rm.phone')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label for="rm_email" class="admin-prod-label">Email</label>
                            <input type="email" id="rm_email" name="rm[email]" value="{{ old('rm.email') }}" required class="admin-prod-input" autocomplete="email">
                            @error('rm.email')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label for="rm_region_id" class="admin-prod-label">Region</label>
                            <select id="rm_region_id" name="rm[region_id]" required class="admin-prod-input">
                                <option value="">Select region</option>
                                @foreach ($regions as $region)
                                    <option value="{{ $region->id }}" @selected(old('rm.region_id') == $region->id)>{{ $region->name }}</option>
                                @endforeach
                            </select>
                            @error('rm.region_id')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="rm_password" class="admin-prod-label">Password</label>
                            <input type="password" id="rm_password" name="rm[password]" required minlength="8" class="admin-prod-input" autocomplete="new-password">
                            @error('rm.password')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="rm_password_confirmation" class="admin-prod-label">Confirm password</label>
                            <input type="password" id="rm_password_confirmation" name="rm[password_confirmation]" required minlength="8" class="admin-prod-input" autocomplete="new-password">
                        </div>
                        <div class="md:col-span-2">
                            <label for="rm_business_name" class="admin-prod-label">Organization / title (optional)</label>
                            <input type="text" id="rm_business_name" name="rm[business_name]" value="{{ old('rm.business_name') }}" class="admin-prod-input" autocomplete="organization">
                            @error('rm.business_name')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label for="rm_notes" class="admin-prod-label">Other notes (optional)</label>
                            <textarea id="rm_notes" name="rm[notes]" rows="3" class="admin-prod-input" placeholder="Internal notes">{{ old('rm.notes') }}</textarea>
                            @error('rm.notes')
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
