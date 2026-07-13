@extends('layouts.guest-portal')

@section('content')
    <h1 class="text-2xl font-semibold mb-6">My profile</h1>

    @if (session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
            {{ session('success') }}
        </div>
    @endif

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

        <div>
            <label class="block text-sm font-medium mb-1">Experience</label>
            <textarea name="experience_bio" rows="4" placeholder="Briefly describe your work experience for vendors."
                class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">{{ old('experience_bio', $user->experience_bio) }}</textarea>
            <p class="mt-1 text-xs text-slate-500">Vendors see this when deciding whether to hire you.</p>
        </div>

        <button type="submit" class="rounded-lg bg-[#fa8900] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#e87b00]">Save profile</button>
    </form>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold mb-1">Work history</h2>
            <p class="text-sm text-slate-500 mb-4">Past and current vendor roles.</p>
            @forelse ($workHistory as $tenure)
                <div class="border-b border-slate-100 py-3 last:border-0">
                    <p class="font-medium text-slate-900">{{ $tenure['vendor_name'] ?? 'Vendor' }}</p>
                    <p class="text-sm text-slate-600">{{ $tenure['role_label'] }}</p>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ \Illuminate\Support\Carbon::parse($tenure['started_at'])->format('M j, Y') }}
                        –
                        @if ($tenure['ended_at'])
                            {{ \Illuminate\Support\Carbon::parse($tenure['ended_at'])->format('M j, Y') }}
                        @else
                            Present
                        @endif
                        @if ($tenure['is_current'])
                            <span class="ml-1 rounded-full bg-emerald-100 px-2 py-0.5 text-emerald-800">Current</span>
                        @endif
                    </p>
                </div>
            @empty
                <p class="text-sm text-slate-500">No work history yet. After you join a vendor, it will appear here.</p>
            @endforelse
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold mb-1">Ratings</h2>
            <p class="text-sm text-slate-500 mb-4">
                @if (($ratingSummary['count'] ?? 0) > 0)
                    Average {{ number_format($ratingSummary['average'], 1) }} / 5
                    ({{ $ratingSummary['count'] }} {{ \Illuminate\Support\Str::plural('rating', $ratingSummary['count']) }})
                @else
                    No ratings yet.
                @endif
            </p>
            @forelse ($ratingSummary['ratings'] ?? [] as $rating)
                <div class="border-b border-slate-100 py-3 last:border-0">
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-medium text-slate-900">{{ $rating['vendor_name'] ?? 'Vendor' }}</p>
                        <p class="text-sm font-semibold text-[#fa8900]">{{ $rating['score'] }} / 5</p>
                    </div>
                    @if (!empty($rating['comment']))
                        <p class="text-sm text-slate-600 mt-1">{{ $rating['comment'] }}</p>
                    @endif
                    <p class="text-xs text-slate-400 mt-1">
                        @if (!empty($rating['updated_at']))
                            {{ \Illuminate\Support\Carbon::parse($rating['updated_at'])->format('M j, Y') }}
                        @endif
                    </p>
                </div>
            @empty
                <p class="text-sm text-slate-500">Vendors can rate you after you work with them.</p>
            @endforelse
        </div>
    </div>
@endsection
