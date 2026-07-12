<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar">
            <div>
                <p class="admin-prod-eyebrow">Payments</p>
                <h1 class="admin-prod-title">Channels</h1>
                <p class="admin-prod-subtitle">Payment channels: mobile, bank, and cash. Balances update from sales and expenses.</p>
            </div>
            <div class="flex gap-2 shrink-0">
                <a href="{{ route('admin.payment-transfer.history') }}" class="admin-prod-btn-ghost inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    Transfer history
                </a>
                <a href="{{ route('admin.payment-transfer.create') }}" class="admin-prod-btn-primary inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4" />
                    </svg>
                    Transfer
                </a>
                <a href="{{ route('admin.payment-options.create') }}" class="admin-prod-btn-primary inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add channel
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif

        @php
            $summary = $channelsSummary ?? [
                'total_balance' => 0,
                'visible_balance' => 0,
                'hidden_balance' => 0,
                'count' => 0,
            ];
        @endphp

        <x-admin-page-dashboard label="Summary" class="mb-6">
            <dl class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <dt class="text-xs uppercase text-slate-500">Total in all channels</dt>
                    <dd class="text-lg font-semibold text-slate-900 tabular-nums">{{ number_format($summary['total_balance'], 0) }} <span class="text-sm font-medium text-slate-500">TZS</span></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-slate-500">Visible channels</dt>
                    <dd class="text-lg font-semibold text-green-700 tabular-nums">{{ number_format($summary['visible_balance'], 0) }} <span class="text-sm font-medium text-slate-500">TZS</span></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-slate-500">Hidden channels</dt>
                    <dd class="text-lg font-semibold text-slate-500 tabular-nums">{{ number_format($summary['hidden_balance'], 0) }} <span class="text-sm font-medium text-slate-500">TZS</span></dd>
                </div>
                <div>
                    <dt class="text-xs uppercase text-slate-500">Channels</dt>
                    <dd class="text-lg font-semibold text-slate-900 tabular-nums">{{ number_format($summary['count']) }}</dd>
                </div>
            </dl>
        </x-admin-page-dashboard>

        <div class="admin-clay-panel overflow-hidden">
            <div class="admin-prod-table-wrap admin-prod-table-wrap--flush overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th scope="col" class="admin-prod-th">Name</th>
                            <th scope="col" class="admin-prod-th">Type</th>
                            <th scope="col" class="admin-prod-th">Status</th>
                            <th scope="col" class="admin-prod-th">Balance (TZS)</th>
                            <th scope="col" class="admin-prod-th admin-prod-th--end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paymentOptions as $option)
                            <tr class="{{ $option->is_hidden ? 'opacity-90' : '' }}">
                                <td class="font-semibold text-[#232f3e]">{{ $option->name }}</td>
                                <td>
                                    <span
                                        class="admin-prod-tag {{ $option->type === 'mobile' ? 'border-blue-200 text-blue-800 bg-blue-50/80' : ($option->type === 'bank' ? 'admin-prod-tag--accent' : '') }}">
                                        {{ ucfirst($option->type) }}
                                    </span>
                                </td>
                                <td>
                                    @if($option->is_hidden)
                                        <span class="admin-prod-user-status admin-prod-user-status--inactive">Hidden</span>
                                    @else
                                        <span class="admin-prod-user-status admin-prod-user-status--active">Visible</span>
                                    @endif
                                </td>
                                <td class="font-bold font-variant-numeric text-slate-800">{{ number_format($option->balance ?? 0, 0) }}</td>
                                <td class="admin-prod-cell-actions">
                                    <div class="admin-prod-actions flex-wrap gap-x-3 gap-y-1 justify-end">
                                        <a href="{{ route('admin.payment-options.edit', $option) }}" class="admin-prod-link">Edit</a>
                                        <form action="{{ route('admin.payment-options.toggle-visibility', $option) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="admin-prod-btn-inline {{ $option->is_hidden ? 'admin-prod-link--success' : 'admin-prod-link' }}">
                                                {{ $option->is_hidden ? 'Show' : 'Hide' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-slate-500 py-10">
                                    No channels yet.
                                    <a href="{{ route('admin.payment-options.create') }}" class="admin-prod-link">Add your first channel</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>
