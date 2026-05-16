<x-team-leader-layout title="Addresses">
    <div class="admin-prod-page !pt-4 sm:!pt-6">
        <div class="admin-prod-toolbar !mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="admin-prod-eyebrow">Account</p>
                <h1 class="admin-prod-title">Your addresses</h1>
                <p class="admin-prod-subtitle">Shipping addresses used at checkout.</p>
            </div>
            <a href="{{ route('team-leader.addresses.create') }}" class="admin-prod-btn-primary shrink-0">Add address</a>
        </div>

        @if (session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-6" role="status">{{ session('success') }}</div>
        @endif

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <a href="{{ route('team-leader.addresses.create') }}"
                class="admin-clay-panel-interactive flex min-h-[200px] flex-col items-center justify-center p-8 text-center">
                <svg class="mx-auto h-10 w-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span class="mt-3 text-base font-bold text-[#232f3e]">Add address</span>
                <span class="mt-1 text-sm text-slate-600">Create a new shipping address</span>
            </a>

            @foreach ($addresses as $address)
                <div class="admin-clay-panel flex min-h-[200px] flex-col justify-between p-6">
                    <div>
                        @if ($address->is_default)
                            <span class="mb-2 inline-block rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">Default</span>
                        @endif
                        <h3 class="border-b border-white/50 pb-2 text-sm font-bold text-[#232f3e]">{{ $address->type }}</h3>
                        <div class="mt-3 space-y-1 text-sm text-slate-600">
                            <p class="font-semibold text-[#232f3e]">{{ Auth::user()->name }}</p>
                            <p>{{ $address->address }}</p>
                            <p>{{ $address->city }}, {{ $address->state }} {{ $address->zip }}</p>
                            <p>{{ $address->country }}</p>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-3 border-t border-white/50 pt-4 text-sm font-semibold">
                        <a href="{{ route('team-leader.addresses.edit', $address) }}" class="text-[#fa8900] hover:underline">Edit</a>
                        <span class="text-slate-300">|</span>
                        <form action="{{ route('addresses.destroy', $address) }}" method="POST"
                            onsubmit="return confirm('Delete this address?');" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Remove</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-team-leader-layout>
