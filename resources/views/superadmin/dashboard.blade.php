<x-superadmin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page !pt-4 sm:!pt-6">
        <div class="admin-prod-toolbar !mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="admin-prod-eyebrow">Platform control</p>
                <h1 class="admin-prod-title">Dashboard</h1>
                <p class="admin-prod-subtitle">Monitor vendors, subscription packages, and the shared master catalog
                    available to every tenant on the platform.</p>
            </div>
            <div class="flex flex-wrap gap-2 shrink-0">
                <a href="{{ route('superadmin.tenants.create') }}" class="admin-prod-btn-primary">Add vendor</a>
                <a href="{{ route('superadmin.command.center') }}" class="admin-prod-btn-ghost">Command center</a>
            </div>
        </div>

        <div class="admin-clay-panel p-5 mb-6" x-data="selcomBusinessBalance()" x-init="load()">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Selcom Business balance</p>
                        <template x-if="loaded && live !== null">
                            <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full"
                                :class="live ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600'"
                                x-text="live ? 'Live' : 'Sandbox'"></span>
                        </template>
                    </div>

                    <div x-show="loading" class="mt-2 h-9 w-40 animate-pulse rounded bg-slate-200/80"></div>

                    <template x-if="!loading && ok">
                        <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#232f3e]">
                            <span x-text="currency"></span>
                            <span x-text="formatted"></span>
                        </p>
                    </template>

                    <template x-if="!loading && !ok">
                        <p class="mt-2 text-sm text-amber-700 max-w-md" x-text="message"></p>
                    </template>

                    <template x-if="!loading && ok && account">
                        <p class="mt-1 text-xs text-slate-500">Account <span class="font-variant-numeric" x-text="account"></span></p>
                    </template>
                </div>

                <button type="button" @click="load()" :disabled="loading"
                    class="admin-prod-btn-ghost text-sm shrink-0 disabled:opacity-60">
                    <span x-show="!loading">Refresh</span>
                    <span x-show="loading" x-cloak>Checking…</span>
                </button>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7">
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Vendors</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#232f3e]">{{ $stats['tenants_total'] }}</p>
                <p class="mt-1 text-xs text-slate-500">Total accounts</p>
            </div>
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Active</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-emerald-700">{{ $stats['tenants_active'] }}</p>
                <p class="mt-1 text-xs text-slate-500">Live vendors</p>
            </div>
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Suspended</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-amber-700">{{ $stats['tenants_suspended'] }}</p>
                <p class="mt-1 text-xs text-slate-500">Access paused</p>
            </div>
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Packages</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#232f3e]">{{ $stats['packages'] }}</p>
                <p class="mt-1 text-xs text-slate-500">Subscription tiers</p>
            </div>
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Regions</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#232f3e]">{{ $stats['regions'] }}</p>
                <p class="mt-1 text-xs text-slate-500">Master catalog</p>
            </div>
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Brands</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#232f3e]">{{ $stats['brands'] }}</p>
                <p class="mt-1 text-xs text-slate-500">Platform brands</p>
            </div>
            <div class="admin-clay-panel p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Models</p>
                <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#232f3e]">{{ $stats['models'] }}</p>
                <p class="mt-1 text-xs text-slate-500">Platform models</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-5">
            <div class="lg:col-span-3 admin-clay-panel overflow-hidden">
                <div class="admin-prod-form-head border-b border-white/60">
                    <h2 class="admin-prod-form-title">Recent vendors</h2>
                    <p class="admin-prod-form-hint">Latest vendor accounts created on the platform.</p>
                </div>
                <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                    <table class="min-w-[640px]">
                        <thead>
                            <tr>
                                <th scope="col" class="admin-prod-th">Vendor</th>
                                <th scope="col" class="admin-prod-th">Package</th>
                                <th scope="col" class="admin-prod-th">Status</th>
                                <th scope="col" class="admin-prod-th">Created</th>
                                <th scope="col" class="admin-prod-th admin-prod-th--end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentTenants as $tenant)
                                <tr>
                                    <td>
                                        <p class="font-semibold text-[#232f3e]">{{ $tenant->name }}</p>
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $tenant->slug }}</p>
                                    </td>
                                    <td class="text-slate-600">{{ $tenant->package?->name ?? '—' }}</td>
                                    <td>
                                        @if ($tenant->status === 'active')
                                            <span class="admin-prod-user-status admin-prod-user-status--active">Active</span>
                                        @elseif ($tenant->status === 'suspended')
                                            <span class="admin-prod-dealer-status admin-prod-dealer-status--suspended">Suspended</span>
                                        @else
                                            <span class="admin-prod-user-status admin-prod-user-status--inactive">{{ ucfirst($tenant->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-slate-600 whitespace-nowrap">{{ $tenant->created_at?->format('M j, Y') ?? '—' }}</td>
                                    <td class="admin-prod-cell-actions">
                                        <a href="{{ route('superadmin.tenants.edit', $tenant) }}" class="admin-prod-link">Manage</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="admin-prod-muted py-8 text-center">No vendors yet.
                                        <a href="{{ route('superadmin.tenants.create') }}" class="admin-prod-link ml-1">Create the first vendor</a>.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($recentTenants->isNotEmpty())
                    <div class="admin-prod-pagination flex justify-end">
                        <a href="{{ route('superadmin.tenants.index') }}" class="admin-prod-link text-sm">View all vendors →</a>
                    </div>
                @endif
            </div>

            <div class="lg:col-span-2 admin-clay-panel overflow-hidden">
                <div class="admin-prod-form-head border-b border-white/60">
                    <h2 class="admin-prod-form-title">Quick actions</h2>
                    <p class="admin-prod-form-hint">Common platform administration tasks.</p>
                </div>
                <div class="admin-prod-form-body space-y-2">
                    <a href="{{ route('superadmin.tenants.index') }}"
                        class="flex items-center justify-between rounded-xl border border-white/80 bg-white/50 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-white/90 transition">
                        <span>Manage vendors</span>
                        <span class="admin-prod-count-pill admin-prod-count-pill--neutral">{{ $stats['tenants_total'] }}</span>
                    </a>
                    <a href="{{ route('superadmin.packages.index') }}"
                        class="flex items-center justify-between rounded-xl border border-white/80 bg-white/50 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-white/90 transition">
                        <span>Subscription packages</span>
                        <span class="admin-prod-count-pill admin-prod-count-pill--info">{{ $stats['packages'] }}</span>
                    </a>
                    <a href="{{ route('superadmin.regions.index') }}"
                        class="flex items-center justify-between rounded-xl border border-white/80 bg-white/50 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-white/90 transition">
                        <span>Master regions</span>
                        <span class="admin-prod-count-pill admin-prod-count-pill--neutral">{{ $stats['regions'] }}</span>
                    </a>
                    <a href="{{ route('superadmin.brands.index') }}"
                        class="flex items-center justify-between rounded-xl border border-white/80 bg-white/50 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-white/90 transition">
                        <span>Master brands</span>
                        <span class="admin-prod-count-pill admin-prod-count-pill--neutral">{{ $stats['brands'] }}</span>
                    </a>
                    <a href="{{ route('superadmin.models.index') }}"
                        class="flex items-center justify-between rounded-xl border border-white/80 bg-white/50 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-white/90 transition">
                        <span>Master models</span>
                        <span class="admin-prod-count-pill admin-prod-count-pill--neutral">{{ $stats['models'] }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('selcomBusinessBalance', () => ({
                    loading: false,
                    loaded: false,
                    ok: false,
                    message: '',
                    currency: 'TZS',
                    formatted: '',
                    account: '',
                    live: null,
                    url: @json(route('superadmin.selcom-business.balance')),

                    load() {
                        if (this.loading) return;
                        this.loading = true;
                        fetch(this.url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                            .then(res => res.json())
                            .then(data => {
                                this.ok = data.ok === true;
                                this.message = data.message || '';
                                this.live = ('live' in data) ? data.live : null;
                                if (this.ok) {
                                    this.currency = data.currency || 'TZS';
                                    this.account = data.account_number || '';
                                    const amount = (data.available_balance === null || data.available_balance === undefined)
                                        ? null : Number(data.available_balance);
                                    this.formatted = amount === null || isNaN(amount)
                                        ? '—'
                                        : amount.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
                                }
                            })
                            .catch(() => {
                                this.ok = false;
                                this.message = 'Could not reach the server.';
                            })
                            .finally(() => {
                                this.loading = false;
                                this.loaded = true;
                            });
                    }
                }));
            });
        </script>
    @endpush
</x-superadmin-layout>
