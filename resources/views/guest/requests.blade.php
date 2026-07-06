@extends('layouts.guest-portal')

@section('content')
    <h1 class="text-2xl font-semibold mb-2">Vendor requests</h1>
    <p class="text-sm text-slate-600 mb-6">Accept to join a vendor, or decline if you are not interested.</p>

    @forelse ($invitations as $invitation)
        @php($item = $invitation->toGuestListArray())
        <div class="mb-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-lg font-semibold text-slate-900">{{ $item['vendor_name'] }}</p>
                    <p class="text-sm text-[#fa8900] font-medium">{{ $item['proposed_role_label'] }}</p>
                </div>
                <span class="text-xs text-slate-500">{{ $invitation->created_at?->diffForHumans() }}</span>
            </div>

            @if ($item['invited_by_name'])
                <p class="mt-2 text-sm text-slate-600">From: {{ $item['invited_by_name'] }}</p>
            @endif
            @if ($item['branch_name'] || $item['region_name'])
                <p class="mt-1 text-sm text-slate-600">
                    @if ($item['branch_name']) Branch: {{ $item['branch_name'] }} @endif
                    @if ($item['region_name']) · Region: {{ $item['region_name'] }} @endif
                </p>
            @endif
            @if ($item['message'])
                <p class="mt-3 text-sm text-slate-700 rounded-lg bg-slate-50 px-3 py-2">{{ $item['message'] }}</p>
            @endif

            <div class="mt-4 flex flex-wrap gap-3">
                <form method="POST" action="{{ route('guest.invitations.accept', $invitation) }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-[#fa8900] px-4 py-2 text-sm font-semibold text-white hover:bg-[#e87b00]">Accept</button>
                </form>
                <form method="POST" action="{{ route('guest.invitations.decline', $invitation) }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Decline</button>
                </form>
            </div>
        </div>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white/70 p-8 text-center text-slate-500">
            No pending requests right now.
        </div>
    @endforelse
@endsection
