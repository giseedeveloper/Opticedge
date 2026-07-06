<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page max-w-3xl">
        <div class="mb-6">
            <a href="{{ route('admin.guest-users.index') }}" class="text-sm text-[#fa8900] hover:underline">&larr; Back to guest users</a>
            <h1 class="admin-prod-title mt-2">Assign {{ $guest->name }}</h1>
            <p class="text-sm text-slate-600">{{ $guest->email }}</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.guest-users.assign.store', $guest) }}" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-900 mb-1">Role</label>
                <select name="role" id="role" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
                    <option value="agent" @selected(old('role') === 'agent')>Agent</option>
                    <option value="teamleader" @selected(old('role') === 'teamleader')>Team leader</option>
                    <option value="regional_manager" @selected(old('role') === 'regional_manager')>Regional manager</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-900 mb-1">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $guest->phone) }}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>

            <div data-role-field="regional_manager teamleader agent">
                <label class="block text-sm font-medium text-slate-900 mb-1">Branch</label>
                <select name="branch_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="">— None —</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            <div data-role-field="regional_manager teamleader">
                <label class="block text-sm font-medium text-slate-900 mb-1">Region</label>
                <select name="region_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="">— Select region —</option>
                    @foreach ($regions as $region)
                        <option value="{{ $region->id }}" @selected((string) old('region_id') === (string) $region->id)>{{ $region->name }}</option>
                    @endforeach
                </select>
            </div>

            <div data-role-field="teamleader">
                <label class="block text-sm font-medium text-slate-900 mb-1">Regional manager</label>
                <select name="regional_manager_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="">— Select regional manager —</option>
                    @foreach ($regionalManagers as $manager)
                        <option value="{{ $manager->id }}" @selected((string) old('regional_manager_id') === (string) $manager->id)>{{ $manager->name }}</option>
                    @endforeach
                </select>
            </div>

            <div data-role-field="agent">
                <label class="block text-sm font-medium text-slate-900 mb-1">Team leader</label>
                <select name="team_leader_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="">— None —</option>
                    @foreach ($teamLeaders as $leader)
                        <option value="{{ $leader->id }}" @selected((string) old('team_leader_id') === (string) $leader->id)>{{ $leader->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-900 mb-1">Business name</label>
                <input type="text" name="business_name" value="{{ old('business_name') }}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-900 mb-1">Notes</label>
                <textarea name="notes" rows="3" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('notes') }}</textarea>
            </div>

            <button type="submit" class="rounded-xl bg-[#fa8900] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#e87b00]">
                Assign to my vendor
            </button>
        </form>
    </div>

    <script>
        (function () {
            const roleSelect = document.getElementById('role');
            const fields = document.querySelectorAll('[data-role-field]');

            function syncFields() {
                const role = roleSelect.value;
                fields.forEach((el) => {
                    const roles = el.getAttribute('data-role-field').split(' ');
                    el.style.display = roles.includes(role) ? '' : 'none';
                });
            }

            roleSelect.addEventListener('change', syncFields);
            syncFields();
        })();
    </script>
</x-admin-layout>
