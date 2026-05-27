<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page admin-prod-page--narrow">
        <div class="admin-prod-toolbar !mb-6">
            <div>
                <p class="admin-prod-eyebrow">Vendor</p>
                <h1 class="admin-prod-title">Vendor profile</h1>
                <p class="admin-prod-subtitle">Update your store name and branding. Package and subscription are managed by the platform.</p>
            </div>
        </div>

        @if (session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="admin-prod-alert admin-prod-alert--error mb-4" role="alert">{{ $errors->first() }}</div>
        @endif

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

        <form action="{{ route('admin.tenant.update') }}" method="POST" class="admin-clay-panel admin-prod-form-shell overflow-hidden">
            @csrf
            @method('PUT')
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">Store details</h2>
            </div>
            <div class="admin-prod-form-body space-y-4">
                <div>
                    <label for="name" class="admin-prod-label">Vendor name</label>
                    <input id="name" name="name" required class="admin-prod-input w-full"
                        value="{{ old('name', $tenant->name) }}">
                </div>
                <div>
                    <label for="brand_name" class="admin-prod-label">Brand name</label>
                    <input id="brand_name" name="brand_name" class="admin-prod-input w-full"
                        value="{{ old('brand_name', $tenant->brand_name) }}">
                </div>
                <div>
                    <label for="slug" class="admin-prod-label">Slug</label>
                    <input id="slug" name="slug" required class="admin-prod-input w-full"
                        value="{{ old('slug', $tenant->slug) }}">
                    <p class="mt-1 text-xs text-slate-500">Used in URLs and internal references. Must be unique.</p>
                </div>
                <button type="submit" class="admin-prod-btn-primary">Save changes</button>
            </div>
        </form>
    </div>
</x-admin-layout>
