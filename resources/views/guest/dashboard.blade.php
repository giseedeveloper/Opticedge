<x-guest-layout>
    <div class="max-w-lg mx-auto text-center py-8">
        @if (auth()->user()->avatar)
            <img src="{{ auth()->user()->avatar }}" alt="" class="mx-auto h-16 w-16 rounded-full object-cover mb-4">
        @endif

        <h1 class="text-2xl font-semibold text-slate-900">Welcome, {{ auth()->user()->name }}</h1>
        <p class="mt-2 text-slate-600">{{ auth()->user()->email }}</p>

        <div class="mt-8 rounded-2xl border border-amber-200 bg-amber-50 px-6 py-5 text-left">
            <p class="text-sm font-medium text-amber-900">Waiting for assignment</p>
            <p class="mt-2 text-sm text-amber-800 leading-relaxed">
                Your account is registered. A vendor administrator will review and assign you as an
                <strong>agent</strong>, <strong>team leader</strong>, or <strong>regional manager</strong>.
                You will receive access to your dashboard once assigned.
            </p>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="mt-8">
            @csrf
            <button type="submit" class="text-sm text-slate-500 hover:text-slate-800 underline">Sign out</button>
        </form>
    </div>
</x-guest-layout>
