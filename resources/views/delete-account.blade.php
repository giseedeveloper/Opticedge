<x-marketing-layout title="Delete Account — OpticEdge Africa">
    <section class="max-w-6xl mx-auto px-4 py-8 sm:py-12">
        <div class="rounded-3xl bg-gradient-to-br from-[#232f3e] via-[#2a3849] to-[#1a2430] text-white px-6 py-10 sm:px-10 sm:py-12 shadow-xl shadow-[#232f3e]/20 overflow-hidden relative">
            <div class="absolute top-0 right-0 w-64 h-64 bg-[#fa8900]/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3" aria-hidden="true"></div>
            <div class="relative max-w-3xl">
                <p class="text-xs font-bold uppercase tracking-[0.24em] text-[#fa8900]">Account & privacy</p>
                <h1 class="mt-3 text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight">Delete your account</h1>
                <p class="mt-4 text-base sm:text-lg text-slate-300 leading-relaxed max-w-2xl">
                    Request permanent deletion of your OpticEdge Africa account and associated personal data.
                    This page is provided for Google Play and web users who need to delete an account without using the app.
                </p>
            </div>
        </div>

        <div class="mt-8 lg:mt-10 grid gap-8 lg:grid-cols-2 lg:items-start">
            <div class="space-y-6">
                <article class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-lg shadow-slate-200/40">
                    <h2 class="text-xl font-extrabold text-[#232f3e] tracking-tight">Delete from the mobile app</h2>
                    <ol class="mt-5 space-y-3 text-slate-600 leading-relaxed list-decimal pl-5">
                        <li>Open the OpticEdge / OpticSales app and sign in.</li>
                        <li>Go to <strong class="text-[#232f3e]">Profile</strong> (or Settings).</li>
                        <li>Select <strong class="text-[#232f3e]">Delete account</strong>.</li>
                        <li>Confirm with your password to permanently delete the account.</li>
                    </ol>
                </article>

                <article class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-lg shadow-slate-200/40">
                    <h2 class="text-xl font-extrabold text-[#232f3e] tracking-tight">What gets deleted</h2>
                    <ul class="mt-5 space-y-3 text-slate-600 leading-relaxed">
                        <li class="flex gap-3"><span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-[#fa8900]"></span><span>Your login credentials and profile details (name, email, phone).</span></li>
                        <li class="flex gap-3"><span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-[#fa8900]"></span><span>App session data and device notification tokens linked to your account.</span></li>
                        <li class="flex gap-3"><span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-[#fa8900]"></span><span>Personal account access to the OpticEdge platform.</span></li>
                    </ul>
                    <p class="mt-5 text-sm text-slate-500 leading-relaxed">
                        Business records that your organization is legally required to keep (for example invoices or stock history owned by a vendor) may be retained in anonymized or organization-owned form where required by law.
                        See our <a href="{{ route('privacy') }}" class="font-semibold text-[#007185] hover:text-[#fa8900] underline underline-offset-2">Privacy Policy</a>.
                    </p>
                </article>

                <article class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-lg shadow-slate-200/40">
                    <h2 class="text-xl font-extrabold text-[#232f3e] tracking-tight">Processing time</h2>
                    <p class="mt-4 text-slate-600 leading-relaxed">
                        Web deletion requests are reviewed and completed within <strong class="text-[#232f3e]">7 days</strong>.
                        You will receive confirmation at the email address you submit.
                    </p>
                    <p class="mt-3 text-sm text-slate-500">
                        Prefer email? Write to
                        <a href="mailto:support@opticedgeafrica.net" class="font-semibold text-[#007185] hover:text-[#fa8900] underline underline-offset-2">support@opticedgeafrica.net</a>
                        with subject “Account deletion request”.
                    </p>
                </article>
            </div>

            <article class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-lg shadow-slate-200/40 lg:sticky lg:top-28">
                <h2 class="text-xl font-extrabold text-[#232f3e] tracking-tight">Request deletion online</h2>
                <p class="mt-2 text-sm text-slate-500 leading-relaxed">
                    Fill this form if you cannot access the app. Use the same email registered on your OpticEdge account.
                </p>

                @if (session('status'))
                    <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('delete-account.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label for="name" class="block text-sm font-semibold text-[#232f3e] mb-1.5">Full name</label>
                        <input id="name" name="name" type="text" required value="{{ old('name') }}"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none focus:border-[#fa8900] focus:bg-white focus:ring-2 focus:ring-[#fa8900]/20"
                            placeholder="Your full name">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-semibold text-[#232f3e] mb-1.5">Account email</label>
                        <input id="email" name="email" type="email" required value="{{ old('email') }}"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none focus:border-[#fa8900] focus:bg-white focus:ring-2 focus:ring-[#fa8900]/20"
                            placeholder="email@example.com">
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-semibold text-[#232f3e] mb-1.5">Phone <span class="font-normal text-slate-400">(optional)</span></label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone') }}"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none focus:border-[#fa8900] focus:bg-white focus:ring-2 focus:ring-[#fa8900]/20"
                            placeholder="+255 ...">
                    </div>
                    <div>
                        <label for="reason" class="block text-sm font-semibold text-[#232f3e] mb-1.5">Reason <span class="font-normal text-slate-400">(optional)</span></label>
                        <textarea id="reason" name="reason" rows="4"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none focus:border-[#fa8900] focus:bg-white focus:ring-2 focus:ring-[#fa8900]/20"
                            placeholder="Tell us why you want to delete your account">{{ old('reason') }}</textarea>
                    </div>
                    <button type="submit"
                        class="w-full cursor-pointer rounded-xl bg-[#232f3e] hover:bg-[#1a2430] text-white font-bold py-3.5 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#fa8900]">
                        Submit deletion request
                    </button>
                    <p class="text-xs text-slate-500 leading-relaxed text-center">
                        By submitting, you confirm you own this account and want it permanently deleted.
                    </p>
                </form>
            </article>
        </div>
    </section>
</x-marketing-layout>
