<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar !mb-6">
            <div>
                <p class="admin-prod-eyebrow">Account</p>
                <h1 class="admin-prod-title">Subscription</h1>
                <p class="admin-prod-subtitle">
                    @if ($tenantSuspended)
                        Your vendor account is suspended. Choose a package below to renew or upgrade and restore access for your team.
                    @else
                        View your package and billing status.
                    @endif
                </p>
            </div>
        </div>

        @if (session('warning'))
            <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ session('warning') }}
            </div>
        @endif

        @if ($tenantSuspended)
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                <strong>Account suspended.</strong> Your regional managers, team leaders, and agents cannot sign in until you renew your subscription.
            </div>
        @endif

        <div class="admin-clay-panel admin-prod-form-shell overflow-hidden mb-8">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Current subscription</h2>
            </div>
            <dl class="admin-prod-form-body grid gap-4 sm:grid-cols-2 text-sm">
                <div>
                    <dt class="font-medium text-slate-500">Vendor</dt>
                    <dd class="mt-0.5 text-slate-800">{{ $tenant->brand_name ?? $tenant->name }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500">Package</dt>
                    <dd class="mt-0.5 text-slate-800">{{ $tenant->package?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500">Billing</dt>
                    <dd class="mt-0.5 text-slate-800">
                        @if ($tenant->package)
                            {{ $tenant->package->formattedPrice() }} / {{ $tenant->package->intervalLabel() }}
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500">Status</dt>
                    <dd class="mt-0.5">
                        @if ($tenant->status === 'active')
                            <span class="admin-prod-user-status admin-prod-user-status--active">Active</span>
                        @else
                            <span class="admin-prod-dealer-status admin-prod-dealer-status--suspended">{{ ucfirst($tenant->status) }}</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="font-medium text-slate-500">Subscription ends</dt>
                    <dd class="mt-0.5 text-slate-800">{{ $tenant->subscription_ends_at?->format('M j, Y') ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        @if ($tenantSuspended && $packages->isNotEmpty())
            <div>
                <h2 class="text-lg font-bold text-[#232f3e] mb-4">Renew or upgrade</h2>
                <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($packages as $package)
                        <article class="admin-clay-panel flex flex-col {{ $tenant->package_id === $package->id ? 'ring-2 ring-[#fa8900]/35' : '' }}">
                            <div class="p-6 flex-1">
                                <h3 class="text-xl font-bold text-[#232f3e]">{{ $package->name }}</h3>
                                @if ($package->description)
                                    <p class="mt-2 text-sm text-slate-600">{{ $package->description }}</p>
                                @endif
                                <p class="mt-4">
                                    <span class="text-2xl font-extrabold text-[#232f3e]">{{ $package->formattedPrice() }}</span>
                                    <span class="text-slate-600 text-sm">/ {{ strtolower($package->intervalLabel()) }}</span>
                                </p>
                                @if ($package->max_users)
                                    <p class="mt-3 text-sm text-slate-600">Up to {{ $package->max_users }} users</p>
                                @endif
                            </div>
                            <div class="px-6 pb-6">
                                <form action="{{ route('admin.tenant.subscribe', $package) }}" method="POST" class="space-y-3">
                                    @csrf
                                    <label class="block text-sm font-medium text-slate-700" for="payment_phone_{{ $package->id }}">M-Pesa phone number</label>
                                    <input type="tel" name="payment_phone" id="payment_phone_{{ $package->id }}"
                                           value="{{ old('payment_phone', auth()->user()->phone) }}"
                                           required
                                           placeholder="+255 7xx xxx xxx"
                                           class="admin-prod-input w-full">
                                    @error('payment_phone')
                                        <p class="text-red-600 text-xs font-semibold">{{ $message }}</p>
                                    @enderror
                                    <button type="submit"
                                            class="w-full rounded-xl bg-[#fa8900] hover:bg-[#e07800] text-white font-bold py-3 text-sm transition-colors">
                                        {{ $tenant->package_id === $package->id ? 'Renew package' : 'Upgrade to this package' }}
                                    </button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-admin-layout>
