<x-admin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar !mb-0 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="admin-prod-eyebrow">Users</p>
                <h1 class="admin-prod-title">OpticEdge users</h1>
                <p class="mt-1 text-sm text-slate-600">Self-registered users waiting to be assigned to your vendor. Review work history and ratings before inviting.</p>
            </div>
        </div>

        @if (session('success'))
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                {{ session('success') }}
            </div>
        @endif

        <form method="GET" class="mt-6 flex gap-3">
            <input type="search" name="search" value="{{ $search }}" placeholder="Search name or email"
                class="w-full max-w-md rounded-xl border border-slate-200 px-4 py-2 text-sm">
            <button type="submit" class="rounded-xl bg-[#fa8900] px-4 py-2 text-sm font-medium text-white">Search</button>
        </form>

        <div class="mt-6 overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Rating</th>
                        <th class="px-4 py-3 font-medium">Registered</th>
                        <th class="px-4 py-3 font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($guests as $guest)
                        @php $stats = $totals[$guest->id] ?? ['avg_rating' => null, 'ratings_count' => 0]; @endphp
                        <tr class="border-b border-slate-100">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($guest->avatar)
                                        <img src="{{ $guest->avatar }}" alt="" class="h-8 w-8 rounded-full object-cover">
                                    @endif
                                    <span class="font-medium text-slate-900">{{ $guest->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $guest->email }}</td>
                            <td class="px-4 py-3 text-slate-700">
                                @if (($stats['ratings_count'] ?? 0) > 0)
                                    <span class="font-semibold text-[#fa8900]">{{ number_format($stats['avg_rating'], 1) }}</span>
                                    <span class="text-slate-500">/ 5 ({{ $stats['ratings_count'] }})</span>
                                @else
                                    <span class="text-slate-400">No ratings</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ $guest->created_at?->format('M j, Y') }}</td>
                            <td class="px-4 py-3 space-x-3">
                                <a href="{{ route('admin.guest-users.show', $guest) }}"
                                    class="text-slate-700 hover:underline font-medium">Profile</a>
                                <a href="{{ route('admin.guest-users.assign', $guest) }}"
                                    class="text-[#fa8900] hover:underline font-medium">Send request</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No OpticEdge users waiting for assignment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $guests->links() }}
        </div>
    </div>
</x-admin-layout>
