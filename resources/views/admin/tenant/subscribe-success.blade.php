<x-admin-layout>
    <div class="admin-prod-page admin-prod-page--narrow">
        <div class="admin-clay-panel p-8 text-center">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-[#232f3e]">Subscription restored</h1>
            <p class="mt-3 text-slate-600">
                Your vendor account is active again on the <strong>{{ $intent->package->name }}</strong> package.
                Your regional managers, team leaders, and agents can sign in again.
            </p>
            <p class="mt-2 text-sm text-slate-500">
                Subscription ends {{ $intent->tenant->subscription_ends_at?->format('M j, Y') ?? '—' }}
            </p>
            <a href="{{ route('admin.dashboard') }}"
               class="mt-6 inline-flex rounded-xl bg-[#fa8900] hover:bg-[#e07800] px-6 py-3 text-sm font-bold text-white">
                Go to dashboard
            </a>
        </div>
    </div>
</x-admin-layout>
