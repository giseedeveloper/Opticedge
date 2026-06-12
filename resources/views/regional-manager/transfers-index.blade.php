<x-regional-manager-layout title="Transfer requests">
    <div class="admin-prod-page !pt-4 sm:!pt-6">
        <div class="admin-prod-toolbar !mb-6">
            <div>
                <p class="admin-prod-eyebrow">Regional manager</p>
                <h1 class="admin-prod-title">Transfer requests</h1>
                <p class="mt-1 text-slate-600">Incoming product requests from admin. Accept or decline each request to receive the devices into your inventory.</p>
            </div>
        </div>

        @if(session('success'))
            <p class="mb-4 rounded-lg bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('success') }}</p>
        @endif
        @if(session('error'))
            <p class="mb-4 rounded-lg bg-red-50 px-4 py-2 text-sm text-red-800">{{ session('error') }}</p>
        @endif

        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
            <table class="js-datatable min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Date</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">From admin</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Devices</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-900">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($transfers as $t)
                        <tr>
                            <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ $t->created_at->format('M j, Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <span class="text-slate-900">{{ $t->createdByAdmin->name ?? 'Admin' }}</span>
                                <span class="ml-2 inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-900">Incoming</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $t->items->count() }} IMEI(s)</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                    @if($t->status === 'pending') bg-amber-100 text-amber-900
                                    @elseif($t->status === 'approved') bg-green-100 text-green-900
                                    @elseif($t->status === 'rejected') bg-red-100 text-red-900
                                    @else bg-slate-100 text-slate-700 @endif">
                                    {{ ucfirst($t->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if($t->status === 'pending')
                                        <form method="POST" action="{{ route('regional-manager.transfers.accept', $t->id) }}" class="inline"
                                            onsubmit="return confirm('Accept this transfer? Devices will be added to your inventory.');">
                                            @csrf
                                            <button type="submit" class="text-xs font-medium text-green-700 hover:text-green-900">Accept</button>
                                        </form>
                                        <form method="POST" action="{{ route('regional-manager.transfers.decline', $t->id) }}" class="inline"
                                            onsubmit="return confirm('Decline this transfer request?');">
                                            @csrf
                                            <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-800">Decline</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @if($t->message || $t->admin_note)
                            <tr class="bg-slate-50/80">
                                <td colspan="5" class="px-4 py-2 text-xs text-slate-600">
                                    @if($t->message)<span class="font-medium text-slate-700">Admin note:</span> {{ $t->message }}@endif
                                    @if($t->message && $t->admin_note)<br>@endif
                                    @if($t->admin_note)<span class="font-medium text-slate-700">Your response:</span> {{ $t->admin_note }}@endif
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No transfer requests yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-regional-manager-layout>
