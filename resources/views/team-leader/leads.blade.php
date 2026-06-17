<x-team-leader-layout title="Leads">
    <div class="admin-prod-page">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="admin-prod-title">Leads</h1>
                <p class="admin-prod-subtitle">Customer product requests you submitted.</p>
            </div>
            <a href="{{ route('team-leader.record-sale') }}" class="admin-prod-btn-primary">Submit lead</a>
        </div>

        @if(session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-4" role="status">{{ session('success') }}</div>
        @endif

        <div class="admin-clay-panel overflow-x-auto">
            <table class="min-w-[720px] w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase text-slate-500 border-b border-slate-200">
                        <th class="py-3 pr-4">Submitted</th>
                        <th class="py-3 pr-4">Customer</th>
                        <th class="py-3 pr-4">Phone</th>
                        <th class="py-3 pr-4">Category</th>
                        <th class="py-3 pr-4">Model</th>
                        <th class="py-3">Branch</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($leads as $lead)
                        <tr>
                            <td class="py-3 pr-4">{{ $lead->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="py-3 pr-4 font-medium text-slate-800">{{ $lead->customer_name }}</td>
                            <td class="py-3 pr-4">{{ $lead->customer_phone }}</td>
                            <td class="py-3 pr-4">{{ $lead->category?->name ?? '—' }}</td>
                            <td class="py-3 pr-4">{{ $lead->product?->name ?? '—' }}</td>
                            <td class="py-3">{{ $lead->branch?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">No leads submitted yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-team-leader-layout>
