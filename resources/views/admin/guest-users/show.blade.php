<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page admin-prod-form-wide">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-8">
            <div>
                <p class="admin-prod-eyebrow">Users</p>
                <h1 class="admin-prod-title">{{ $guest->name }}</h1>
                <p class="admin-prod-subtitle">Review experience, work history, and ratings before inviting this OpticEdge user.</p>
            </div>
            <div class="flex gap-2 shrink-0">
                <a href="{{ route('admin.guest-users.index') }}" class="admin-prod-back">Back</a>
                <a href="{{ route('admin.guest-users.assign', $guest) }}" class="admin-prod-btn-primary">Send request</a>
            </div>
        </div>

        @if (session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-6">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="admin-prod-alert admin-prod-alert--error mb-6">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="admin-clay-panel overflow-hidden mb-6">
            <div class="admin-prod-form-head">
                <div class="flex items-center gap-4">
                    @if ($guest->avatar)
                        <img src="{{ $guest->avatar }}" alt="" class="h-14 w-14 rounded-2xl object-cover border border-white shadow-sm">
                    @else
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-lg font-bold text-slate-500">
                            {{ strtoupper(substr($guest->name, 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <p class="text-base font-bold text-slate-900">{{ $guest->name }}</p>
                        <p class="text-sm text-slate-600">{{ $guest->email }}</p>
                        @if ($guest->phone)
                            <p class="text-sm text-slate-500">{{ $guest->phone }}</p>
                        @endif
                        <p class="text-xs text-slate-500 mt-1">
                            Average rating:
                            @if (($ratingSummary['count'] ?? 0) > 0)
                                <span class="font-semibold text-[#fa8900]">{{ number_format($ratingSummary['average'], 1) }} / 5</span>
                                ({{ $ratingSummary['count'] }})
                            @else
                                No ratings yet
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            @if ($guest->experience_bio)
                <div class="px-6 pb-6">
                    <h2 class="text-sm font-semibold text-slate-900 mb-1">Experience</h2>
                    <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $guest->experience_bio }}</p>
                </div>
            @endif
        </div>

        <div class="grid gap-6 lg:grid-cols-2 mb-6">
            <div class="admin-clay-panel p-6">
                <h2 class="admin-prod-form-title mb-3">Work history</h2>
                @forelse ($workHistory as $tenure)
                    <div class="border-b border-slate-100 py-3 last:border-0">
                        <p class="font-medium">{{ $tenure['vendor_name'] ?? 'Vendor' }}</p>
                        <p class="text-sm text-slate-600">{{ $tenure['role_label'] }}</p>
                        <p class="text-xs text-slate-500 mt-1">
                            {{ \Illuminate\Support\Carbon::parse($tenure['started_at'])->format('M j, Y') }}
                            –
                            {{ $tenure['ended_at'] ? \Illuminate\Support\Carbon::parse($tenure['ended_at'])->format('M j, Y') : 'Present' }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No prior vendor work on record.</p>
                @endforelse
            </div>

            <div class="admin-clay-panel p-6">
                <h2 class="admin-prod-form-title mb-3">Ratings</h2>
                @forelse ($ratingSummary['ratings'] ?? [] as $rating)
                    <div class="border-b border-slate-100 py-3 last:border-0">
                        <div class="flex justify-between gap-2">
                            <p class="font-medium">{{ $rating['vendor_name'] ?? 'Vendor' }}</p>
                            <p class="font-semibold text-[#fa8900]">{{ $rating['score'] }} / 5</p>
                        </div>
                        @if (!empty($rating['comment']))
                            <p class="text-sm text-slate-600 mt-1">{{ $rating['comment'] }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500 mb-4">No ratings yet.</p>
                @endforelse

                <form method="POST" action="{{ route('admin.guest-users.ratings.store', $guest) }}" class="mt-4 space-y-3 border-t border-slate-100 pt-4">
                    @csrf
                    <p class="text-sm font-semibold text-slate-900">Rate this worker</p>
                    <div>
                        <label class="admin-prod-label" for="score">Score (1–5)</label>
                        <select name="score" id="score" class="admin-prod-select" required>
                            @for ($i = 5; $i >= 1; $i--)
                                <option value="{{ $i }}">{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="admin-prod-label" for="comment">Comment</label>
                        <textarea name="comment" id="comment" rows="3" class="admin-prod-input" placeholder="Optional feedback"></textarea>
                    </div>
                    <button type="submit" class="admin-prod-btn-primary">Save rating</button>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
