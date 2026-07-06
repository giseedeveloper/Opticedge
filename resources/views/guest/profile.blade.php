@extends('layouts.guest-portal')

@section('content')
    <h1 class="text-2xl font-semibold mb-6">My profile</h1>

    <form method="POST" action="{{ route('guest.profile.update') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        @csrf
        @method('PUT')

        <div class="flex items-center gap-4 pb-4 border-b border-slate-100">
            @if ($user->avatar)
                <img src="{{ $user->avatar }}" alt="" class="h-14 w-14 rounded-full object-cover">
            @endif
            <div>
                <p class="text-sm text-slate-500">Signed in as guest</p>
                <p class="font-medium">{{ $user->email }}</p>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Full name</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Phone</label>
            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
        </div>

        <button type="submit" class="rounded-lg bg-[#fa8900] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#e87b00]">Save profile</button>
    </form>
@endsection
