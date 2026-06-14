<x-account-layout>
    <div class="mb-6">
        <a href="{{ route('agent.dashboard') }}" class="text-sm text-[#fa8900] hover:underline">← Agent dashboard</a>
        <h2 class="mt-2 text-2xl font-bold text-slate-900">My return requests</h2>
        <p class="mt-1 text-slate-600">Track devices you submitted to return to your team leader. Pending requests can be cancelled.</p>
    </div>

    @if (session('success'))
        <p class="mb-4 rounded-lg bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('success') }}</p>
    @endif
    @if (session('error'))
        <p class="mb-4 rounded-lg bg-red-50 px-4 py-2 text-sm text-red-800">{{ session('error') }}</p>
    @endif

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-900">Date</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-900">To team leader</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-900">Devices</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-900">Status</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-900">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($returns as $r)
                    <tr>
                        <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ $r->created_at->format('M j, Y H:i') }}</td>
                        <td class="px-4 py-3 text-slate-900">{{ $r->toTeamLeader->name ?? 'Team leader' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $r->items->count() }} IMEI(s)</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                @if($r->status === 'pending') bg-amber-100 text-amber-900
                                @elseif($r->status === 'approved') bg-green-100 text-green-900
                                @elseif($r->status === 'rejected') bg-red-100 text-red-900
                                @else bg-slate-100 text-slate-700 @endif">
                                {{ ucfirst($r->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($r->status === 'pending')
                                <form method="POST" action="{{ route('agent.return-requests.cancel', $r->id) }}" class="inline"
                                    onsubmit="return confirm('Cancel this return request?');">
                                    @csrf
                                    <button type="submit" class="text-xs font-medium text-slate-600 hover:text-slate-900">Cancel</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">No return requests yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-account-layout>
