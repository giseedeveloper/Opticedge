<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Device return</p>
                <h1 class="admin-prod-title">Request #{{ $returnRequest->id }}</h1>
                <p class="admin-prod-subtitle">{{ $routeLabel }}</p>
            </div>
            <a href="{{ route('admin.stock.device-returns') }}" class="admin-prod-btn-ghost shrink-0">Back to list</a>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="admin-prod-alert admin-prod-alert--error mb-4" role="alert">{{ session('error') }}</div>
        @endif

        <div class="admin-clay-panel mb-6 space-y-4 p-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Status</p>
                    <p class="text-lg font-medium text-slate-900">{{ ucfirst($returnRequest->status) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Created</p>
                    <p class="text-slate-900">{{ $returnRequest->created_at->format('Y-m-d H:i:s') }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">{{ $fromLabel }}</p>
                    <p class="font-medium text-slate-900">{{ $fromName }}</p>
                    @if($fromEmail)
                        <p class="text-sm text-slate-600">{{ $fromEmail }}</p>
                    @endif
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">{{ $toLabel }}</p>
                    <p class="font-medium text-slate-900">{{ $toName }}</p>
                    @if($toEmail)
                        <p class="text-sm text-slate-600">{{ $toEmail }}</p>
                    @endif
                </div>
            </div>
            @if($returnRequest->message ?? null)
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Sender note</p>
                    <p class="text-slate-800">{{ $returnRequest->message }}</p>
                </div>
            @endif
            @if($returnRequest->recipient_note ?? null)
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Recipient response</p>
                    <p class="text-slate-800">{{ $returnRequest->recipient_note }}</p>
                </div>
            @endif
            @if($returnRequest->decided_at ?? null)
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Decided</p>
                    <p class="text-slate-800">{{ $returnRequest->decided_at->format('Y-m-d H:i:s') }}
                        @if($returnRequest->decidedByUser) by {{ $returnRequest->decidedByUser->name }} @endif
                    </p>
                </div>
            @endif
        </div>

        @if(!empty($canAccept) && !empty($acceptUrl))
            <div class="admin-clay-panel mb-6 flex flex-wrap gap-3 p-4">
                <form method="POST" action="{{ $acceptUrl }}" onsubmit="return confirm('Accept this return?');">
                    @csrf
                    <button type="submit" class="admin-prod-btn-primary text-sm py-2 px-4">Accept return</button>
                </form>
                <form method="POST" action="{{ $declineUrl ?? '#' }}" onsubmit="return confirm('Decline this return?');">
                    @csrf
                    <button type="submit" class="admin-prod-btn-ghost text-sm py-2 px-4 text-red-700">Decline</button>
                </form>
            </div>
        @endif

        @include('admin.stock.partials.hierarchy-device-items-table', ['items' => $returnRequest->items])

        @if(!empty($pendingHint))
            <div class="admin-clay-panel mt-6 p-6">
                <p class="text-sm text-slate-700">{{ $pendingHint }}</p>
            </div>
        @endif
    </div>
</x-admin-layout>
