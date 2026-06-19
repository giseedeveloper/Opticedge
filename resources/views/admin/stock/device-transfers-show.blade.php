<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Device transfer</p>
                <h1 class="admin-prod-title">Request #{{ $transfer->id }}</h1>
                <p class="admin-prod-subtitle">{{ $routeLabel }}</p>
            </div>
            <a href="{{ route('admin.stock.device-transfers') }}" class="admin-prod-btn-ghost shrink-0">Back to list</a>
        </div>

        <div class="admin-clay-panel mb-6 space-y-4 p-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Status</p>
                    <p class="text-lg font-medium text-slate-900">{{ ucfirst($transfer->status) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Created</p>
                    <p class="text-slate-900">{{ $transfer->created_at->format('Y-m-d H:i:s') }}</p>
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
            @if($transfer->message ?? null)
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Message</p>
                    <p class="text-slate-800">{{ $transfer->message }}</p>
                </div>
            @endif
            @if($transfer->admin_note ?? null)
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Admin note</p>
                    <p class="text-slate-800">{{ $transfer->admin_note }}</p>
                </div>
            @endif
            @if($transfer->decided_at ?? null)
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Decided</p>
                    <p class="text-slate-800">{{ $transfer->decided_at->format('Y-m-d H:i:s') }}
                        @if($transfer->decidedByUser) by {{ $transfer->decidedByUser->name }} @endif
                    </p>
                </div>
            @endif
        </div>

        @include('admin.stock.partials.hierarchy-device-items-table', ['items' => $transfer->items])

        @if(!empty($pendingHint))
            <div class="admin-clay-panel mt-6 p-6">
                <p class="text-sm text-slate-700">{{ $pendingHint }}</p>
            </div>
        @endif
    </div>
</x-admin-layout>
