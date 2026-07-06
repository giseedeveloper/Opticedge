@extends('layouts.guest-portal')

@section('content')
    <div class="rounded-2xl border border-white/80 bg-white/90 p-6 shadow-sm">
        <div class="flex items-center gap-4">
            @if ($user->avatar)
                <img src="{{ $user->avatar }}" alt="" class="h-16 w-16 rounded-full object-cover">
            @endif
            <div>
                <h1 class="text-2xl font-semibold">Hi, {{ $user->name }}</h1>
                <p class="text-slate-600 text-sm">{{ $user->email }}</p>
            </div>
        </div>

        @if ($pendingCount > 0)
            <div class="mt-6 rounded-xl border border-[#fa8900]/30 bg-orange-50 px-4 py-4">
                <p class="font-medium text-[#c56f00]">{{ $pendingCount }} vendor {{ Str::plural('request', $pendingCount) }} waiting</p>
                <p class="mt-1 text-sm text-slate-700">Review invitations from vendors and accept or decline.</p>
                <a href="{{ route('guest.requests') }}" class="mt-3 inline-block text-sm font-semibold text-[#fa8900] hover:underline">View requests →</a>
            </div>
        @else
            <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700">
                No pending requests yet. Vendors can find you in their admin panel and send an invitation to join as agent, team leader, or regional manager.
            </div>
        @endif
    </div>
@endsection
