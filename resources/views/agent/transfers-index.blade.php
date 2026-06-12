<x-account-layout>
    <div class="mb-6">
        <div>
            <a href="{{ route('agent.dashboard') }}" class="text-sm text-slate-600 hover:text-slate-900">&larr; Back to dashboard</a>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Transfer requests</h2>
            <p class="mt-1 text-slate-600">Incoming device transfers sent to you. Accept or decline each request to receive the devices.</p>
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
                    <th class="px-4 py-3 text-left font-semibold text-slate-900">From → To</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-900">Devices</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-900">Status</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-900">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($transfers as $t)
                    @php
                        $isIncoming = (int) $t->to_agent_id === (int) Auth::id();
                        $isOutgoing = (int) $t->from_agent_id === (int) Auth::id();
                    @endphp
                    <tr>
                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ $t->created_at->format('M j, Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <span class="text-slate-900">{{ $t->fromAgent->name }}</span>
                            <span class="text-slate-400"> → </span>
                            <span class="text-slate-900">{{ $t->toAgent->name }}</span>
                            @if($isIncoming)
                                <span class="ml-2 inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-900">Incoming</span>
                            @elseif($isOutgoing)
                                <span class="ml-2 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">Outgoing</span>
                            @endif
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
                                @if($t->status === 'pending' && $isIncoming)
                                    <form method="POST" action="{{ route('agent.transfers.accept', $t->id) }}" class="inline"
                                        onsubmit="return confirm('Accept this transfer? Devices will be assigned to you.');">
                                        @csrf
                                        <button type="submit" class="text-xs font-medium text-green-700 hover:text-green-900">Accept</button>
                                    </form>
                                    <form method="POST" action="{{ route('agent.transfers.decline', $t->id) }}" class="inline"
                                        onsubmit="return confirm('Decline this transfer request?');">
                                        @csrf
                                        <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-800">Decline</button>
                                    </form>
                                @endif
                                @if($t->status === 'pending' && $isOutgoing)
                                    <form method="POST" action="{{ route('agent.transfers.cancel', $t->id) }}" class="inline"
                                        onsubmit="return confirm('Cancel this transfer request?');">
                                        @csrf
                                        <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-800">Cancel</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @if($t->message || $t->admin_note)
                        <tr class="bg-slate-50/80">
                            <td colspan="5" class="px-4 py-2 text-xs text-slate-600">
                                @if($t->message)<span class="font-medium text-slate-700">Sender note:</span> {{ $t->message }}@endif
                                @if($t->message && $t->admin_note)<br>@endif
                                @if($t->admin_note)<span class="font-medium text-slate-700">Response note:</span> {{ $t->admin_note }}@endif
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
</x-account-layout>
