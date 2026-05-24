<x-account-layout>
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-900">Agent Dashboard</h2>
        <p class="mt-1 text-slate-600">Products assigned to you. Record a sale when you sell to a customer.</p>
    </div>

    @if(session('success'))
        <p class="mb-4 rounded-lg bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('success') }}</p>
    @endif
    @if(session('error'))
        <p class="mb-4 rounded-lg bg-red-50 px-4 py-2 text-sm text-red-800">{{ session('error') }}</p>
    @endif

    <div class="mb-6 flex flex-wrap gap-3">
        <a href="{{ route('agent.return-devices') }}"
            class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-800 hover:bg-slate-50">
            Return devices to team leader
        </a>
    </div>

    <div class="mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase text-slate-500">Assigned</p>
            <p class="text-2xl font-bold text-slate-900">{{ $totalAssigned }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase text-slate-500">Sold</p>
            <p class="text-2xl font-bold text-slate-900">{{ $totalSold }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase text-slate-500">Remaining</p>
            <p class="text-2xl font-bold text-slate-900">{{ $totalRemaining }}</p>
        </div>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
        <h3 class="border-b border-slate-100 bg-slate-50 px-6 py-3 text-sm font-semibold text-slate-900">My assigned products</h3>
        <div class="divide-y divide-slate-100">
            @forelse($assignments as $a)
                @php $remaining = $a->quantity_assigned - $a->quantity_sold; @endphp
                <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4">
                    <div>
                        <p class="font-medium text-slate-900">{{ $a->product->category->name ?? '—' }} – {{ $a->product->name }}</p>
                        <p class="text-sm text-slate-500">Assigned: {{ $a->quantity_assigned }} · Sold: {{ $a->quantity_sold }} · Remaining: {{ $remaining }}</p>
                    </div>
                    <div>
                        @if($remaining > 0)
                            <a href="{{ route('agent.record-sale-form', $a) }}"
                                class="inline-flex items-center rounded-lg bg-[#fa8900] px-4 py-2 text-sm font-medium text-white hover:bg-[#e87b00]">
                                Record sale
                            </a>
                        @else
                            <span class="text-sm text-slate-400">No stock left</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-slate-500">No products assigned yet. Your team leader will assign devices to you.</div>
            @endforelse
        </div>
    </div>
</x-account-layout>
