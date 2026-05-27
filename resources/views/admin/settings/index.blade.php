<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page" x-data="{ tab: @js(request()->has('role_id') ? 'roles' : 'store') }">
        <div class="mb-8">
            <p class="admin-prod-eyebrow">Store</p>
            <h1 class="admin-prod-title">Store settings</h1>
            <p class="admin-prod-subtitle">Sales channel defaults for your store and subadmin permissions.</p>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="admin-prod-alert admin-prod-alert--error mb-4" role="alert">{{ session('error') }}</div>
        @endif

        <div class="mb-5 inline-flex rounded-xl bg-white/70 p-1 border border-white/80">
            <button type="button" @click="tab='store'"
                :class="tab === 'store' ? 'bg-[#fa8900] text-white' : 'text-slate-600'"
                class="px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                Store
            </button>
            <button type="button" @click="tab='roles'"
                :class="tab === 'roles' ? 'bg-[#fa8900] text-white' : 'text-slate-600'"
                class="px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                Roles & Permissions
            </button>
        </div>

        <div x-show="tab === 'store'" x-cloak class="admin-clay-panel admin-prod-form-shell overflow-hidden">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Sales channel defaults</h2>
                <p class="admin-prod-form-hint">Defaults for agent app sales and commission expenses. Selcom and email are managed in platform superadmin settings.</p>
            </div>
            <form action="{{ route('admin.settings.update') }}" method="POST" class="admin-prod-form-body space-y-6">
                @csrf

                <div>
                    <label for="default_watu_channel_id" class="admin-prod-label">Default Watu channel</label>
                    <select name="default_watu_channel_id" id="default_watu_channel_id" class="admin-prod-select">
                        <option value="">-- Select Watu channel --</option>
                        @foreach($paymentOptions as $channel)
                            <option value="{{ $channel->id }}" @selected(($settings['default_watu_channel_id'] ?? '') == (string) $channel->id)>
                                {{ $channel->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-500 mt-2">
                        This channel is automatically used when an agent records a Watu sale in the app.
                    </p>
                </div>

                <div>
                    <label for="default_agent_sale_channel_id" class="admin-prod-label">Default regular sale channel (optional)</label>
                    <select name="default_agent_sale_channel_id" id="default_agent_sale_channel_id" class="admin-prod-select">
                        <option value="">-- Any channel (agent chooses) --</option>
                        @foreach($paymentOptions as $channel)
                            <option value="{{ $channel->id }}" @selected(($settings['default_agent_sale_channel_id'] ?? '') == (string) $channel->id)>
                                {{ $channel->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-500 mt-2">
                        Leave blank to let agents choose any channel. Set to restrict to one channel.
                    </p>
                </div>

                <div>
                    <label for="default_agent_commission_channel_id" class="admin-prod-label">Default commission channel (agent credit)</label>
                    <select name="default_agent_commission_channel_id" id="default_agent_commission_channel_id" class="admin-prod-select">
                        <option value="">-- Select channel for commission expenses --</option>
                        @foreach($paymentOptions as $channel)
                            <option value="{{ $channel->id }}" @selected(($settings['default_agent_commission_channel_id'] ?? '') == (string) $channel->id)>
                                {{ $channel->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-500 mt-2">
                        When you save commission on an agent credit, an expense is posted to this channel (balance is reduced by the commission amount).
                    </p>
                </div>

                <div class="admin-prod-form-footer !mt-0 !pt-0 !border-0 !shadow-none">
                    <button type="submit" class="admin-prod-btn-primary px-8">Save changes</button>
                </div>
            </form>
        </div>

        <div x-show="tab === 'roles'" x-cloak class="admin-clay-panel overflow-hidden">
            @if(!$rolesFeatureReady)
                <div class="p-5">
                    <div class="admin-prod-alert admin-prod-alert--warning mb-0">
                        Roles & Permissions needs database update first. Run <code>php artisan migrate</code>, then reload this page.
                    </div>
                </div>
            @else
            <div class="grid grid-cols-1 lg:grid-cols-12 min-h-[560px]">
                <aside class="lg:col-span-4 border-r border-slate-100/80 p-4 bg-white/40">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="font-semibold text-[#232f3e]">Roles</h2>
                    </div>
                    <form action="{{ route('admin.settings.subadmin-roles.store') }}" method="POST" class="space-y-2 mb-4">
                        @csrf
                        <input type="text" name="name" placeholder="New role name" class="admin-prod-input" required>
                        <input type="text" name="description" placeholder="Description (optional)" class="admin-prod-input">
                        <button type="submit" class="admin-prod-btn-primary w-full">+ New role</button>
                    </form>
                    <div class="space-y-2">
                        @forelse($roles as $role)
                            <a href="{{ route('admin.settings.index', ['role_id' => $role->id]) }}"
                                class="block rounded-xl border px-3 py-2.5 transition-colors {{ optional($selectedRole)->id === $role->id ? 'border-[#fa8900] bg-orange-50/70' : 'border-slate-200/70 bg-white/80 hover:bg-slate-50' }}">
                                <p class="font-semibold text-sm text-[#232f3e]">{{ $role->name }}</p>
                                <p class="text-xs text-slate-500">{{ $role->users_count }} users</p>
                            </a>
                        @empty
                            <p class="text-sm text-slate-500">No roles yet.</p>
                        @endforelse
                    </div>
                </aside>

                <section class="lg:col-span-8 p-4 sm:p-6 bg-white/20">
                    @if($selectedRole)
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div>
                                <h3 class="text-xl font-semibold text-[#232f3e]">{{ $selectedRole->name }}</h3>
                                <p class="text-sm text-slate-500">
                                    {{ $selectedRole->description ?: 'Set module-level access, then save changes.' }}
                                </p>
                            </div>
                        </div>

                        <form action="{{ route('admin.settings.subadmin-roles.update', $selectedRole) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="overflow-x-auto rounded-xl border border-slate-200/70">
                                <table class="w-full text-sm">
                                    <thead class="bg-[#1f2a44] text-white">
                                        <tr>
                                            <th class="px-3 py-2 text-left uppercase tracking-wide text-[11px]">Module</th>
                                            @foreach(['view', 'create', 'edit', 'delete', 'approve', 'export', 'all'] as $action)
                                                <th class="px-2 py-2 text-center uppercase tracking-wide text-[11px]">{{ $action }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white/70">
                                        @foreach($abilityMatrix as $module => $actions)
                                            <tr>
                                                <td class="px-3 py-2 font-semibold text-[#232f3e]">{{ \Illuminate\Support\Str::headline($module) }}</td>
                                                @foreach(['view', 'create', 'edit', 'delete', 'approve', 'export', 'all'] as $action)
                                                    @php
                                                        $key = $module . '.' . $action;
                                                        $isChecked = in_array($key, $granted, true);
                                                    @endphp
                                                    <td class="px-2 py-2 text-center">
                                                        <input type="checkbox"
                                                            name="permissions[]"
                                                            value="{{ $key }}"
                                                            @checked($isChecked)
                                                            class="h-4 w-4 rounded border border-black text-[#fa8900] focus:ring-[#fa8900] cursor-pointer">
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4 flex justify-end">
                                <button type="submit" class="admin-prod-btn-primary px-6">Save changes</button>
                            </div>
                        </form>
                    @else
                        <p class="text-sm text-slate-500">Create a role on the left to manage permissions.</p>
                    @endif
                </section>
            </div>
            @endif
        </div>
    </div>
</x-admin-layout>
