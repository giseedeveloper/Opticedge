<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database setup — {{ config('app.name') }}</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
</head>

<body class="min-h-screen bg-slate-100 font-sans text-slate-700 antialiased p-4 sm:p-8">
    <div class="max-w-lg mx-auto bg-white rounded-2xl shadow-lg border border-slate-200 p-6 sm:p-8"
        x-data="dbSetup()">
        <h1 class="text-xl font-bold text-slate-900">Database setup</h1>
        <p class="mt-2 text-sm text-slate-600">
            Run migrations and seeders on this server. Requires the setup password from
            <code class="text-xs bg-slate-100 px-1 rounded">OPTIC_DB_SEED_PASS</code> in <code class="text-xs bg-slate-100 px-1 rounded">.env</code>
            (default <code class="text-xs bg-slate-100 px-1 rounded">1234</code>).
        </p>

        <div class="mt-6">
            <label for="pass" class="block text-sm font-semibold text-slate-800 mb-1">Setup password</label>
            <input type="password" id="pass" x-model="pass" autocomplete="off"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-orange-500 focus:ring-2 focus:ring-orange-500/30"
                placeholder="Enter OPTIC_DB_SEED_PASS">
        </div>

        <div class="mt-4">
            <label for="seeder" class="block text-sm font-semibold text-slate-800 mb-1">Seeder class (optional)</label>
            <input type="text" id="seeder" x-model="seederClass"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                placeholder="e.g. TenantPackageSeeder">
            <p class="text-xs text-slate-500 mt-1">Leave empty for default <code>DatabaseSeeder</code>.</p>
        </div>

        <div class="mt-6 flex flex-col gap-2">
            <button type="button" @click="run('setup')" :disabled="loading"
                class="w-full py-2.5 rounded-lg bg-[#232f3e] hover:bg-slate-800 text-white text-sm font-bold disabled:opacity-50">
                Migrate + seed (recommended)
            </button>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" @click="run('migrate')" :disabled="loading"
                    class="py-2.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-sm font-semibold disabled:opacity-50">
                    Migrate only
                </button>
                <button type="button" @click="run('seed')" :disabled="loading"
                    class="py-2.5 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-sm font-semibold disabled:opacity-50">
                    Seed only
                </button>
            </div>
        </div>

        <div x-show="loading" class="mt-4 text-sm text-slate-500">Running…</div>

        <pre x-show="result" x-cloak
            class="mt-4 p-3 rounded-lg text-xs overflow-x-auto whitespace-pre-wrap"
            :class="ok ? 'bg-emerald-50 text-emerald-900 border border-emerald-200' : 'bg-red-50 text-red-900 border border-red-200'"
            x-text="result"></pre>

        <details class="mt-6 text-xs text-slate-500">
            <summary class="cursor-pointer font-medium text-slate-600">Direct API URLs</summary>
            <ul class="mt-2 space-y-1 font-mono break-all">
                <li>GET {{ $migrateUrl }}?pass=…</li>
                <li>GET {{ $seedUrl }}?pass=…</li>
                <li>GET {{ $setupUrl }}?pass=…</li>
            </ul>
        </details>
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    <script>
        function dbSetup() {
            return {
                pass: '',
                seederClass: '',
                loading: false,
                result: '',
                ok: false,
                urls: {
                    migrate: @json($migrateUrl),
                    seed: @json($seedUrl),
                    setup: @json($setupUrl),
                },
                async run(action) {
                    if (!this.pass) {
                        this.ok = false;
                        this.result = 'Enter the setup password.';
                        return;
                    }
                    this.loading = true;
                    this.result = '';
                    let url = this.urls[action] + '?pass=' + encodeURIComponent(this.pass);
                    if ((action === 'seed' || action === 'setup') && this.seederClass.trim()) {
                        url += '&class=' + encodeURIComponent(this.seederClass.trim());
                    }
                    try {
                        const res = await fetch(url, {
                            headers: {
                                Accept: 'application/json'
                            }
                        });
                        const data = await res.json();
                        this.ok = !!data.ok;
                        this.result = JSON.stringify(data, null, 2);
                    } catch (e) {
                        this.ok = false;
                        this.result = 'Request failed: ' + e.message;
                    }
                    this.loading = false;
                }
            };
        }
    </script>
</body>

</html>
