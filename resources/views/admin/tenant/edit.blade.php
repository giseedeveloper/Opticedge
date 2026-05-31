<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page admin-prod-page--narrow">
        <div class="admin-prod-toolbar !mb-6">
            <div>
                <p class="admin-prod-eyebrow">Account</p>
                <h1 class="admin-prod-title">Subscription</h1>
                <p class="admin-prod-subtitle">View your package and billing status. Contact platform support to change package or status.</p>
            </div>
        </div>

        <div class="admin-clay-panel admin-prod-form-shell overflow-hidden mb-6">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Subscription</h2>
                <p class="admin-prod-form-hint">Read-only — contact platform support to change package or status.</p>
            </div>
            <dl class="admin-prod-form-body grid gap-4 sm:grid-cols-2 text-sm">
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
    </div>
</x-admin-layout>
