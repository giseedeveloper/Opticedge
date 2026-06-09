<x-account-layout>
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('agent.dashboard') }}" class="text-sm text-slate-600 hover:text-slate-900">&larr; Back to dashboard</a>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">Transfer requests</h2>
            <p class="mt-1 text-slate-600">Requests you sent or received. Pending transfers wait for admin approval.</p>
        </div>
        <a href="{{ route('agent.transfer.create') }}"
            class="inline-flex items-center rounded-lg bg-[#fa8900] px-4 py-2 text-sm font-medium text-white hover:bg-[#e87b00]">
            New transfer
        </a>
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
                    <th class="px-4 py-3 text-left font-semibold text-slate-900"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($transfers as $t)
                    <tr>
                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ $t->created_at->format('M j, Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <span class="text-slate-900">{{ $t->fromAgent->name }}</span>
                            <span class="text-slate-400"> → </span>
                            <span class="text-slate-900">{{ $t->toAgent->name }}</span>
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
                            @if($t->status === 'pending' && (int) $t->from_agent_id === (int) Auth::id())
                                <form method="POST" action="{{ route('agent.transfers.cancel', $t->id) }}" class="inline"
                                    onsubmit="return confirm('Cancel this transfer request?');">
                                    @csrf
                                    <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-800">Cancel</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @if($t->message || $t->admin_note)
                        <tr class="bg-slate-50/80">
                            <td colspan="5" class="px-4 py-2 text-xs text-slate-600">
                                @if($t->message)<span class="font-medium text-slate-700">Agent:</span> {{ $t->message }}@endif
                                @if($t->message && $t->admin_note)<br>@endif
                                @if($t->admin_note)<span class="font-medium text-slate-700">Admin:</span> {{ $t->admin_note }}@endif
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
