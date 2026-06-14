@php
    $commandRoutes = $commandRoutes ?? [
        'execute' => 'superadmin.command.execute',
        'migrateForce' => 'superadmin.command.migrate-force',
        'migratePath' => 'superadmin.command.migrate-path',
        'seedClass' => 'superadmin.command.seed-class',
        'emptyTable' => 'superadmin.command.empty-table',
        'extensionTrack' => 'superadmin.command.extension-track',
        'extensionUntrack' => 'superadmin.command.extension-untrack',
    ];
@endphp
<x-superadmin-layout>
    @include('admin.partials.catalog-styles')

    <div class="admin-prod-page">
        <div class="admin-prod-toolbar !mb-6">
            <div>
                <p class="admin-prod-eyebrow">System</p>
                <h1 class="admin-prod-title">Command center</h1>
                <p class="admin-prod-subtitle">Whitelisted Artisan commands, single-file migrations, and PHP extensions.</p>
            </div>
        </div>

        @if (session('success'))
            <div class="admin-prod-alert admin-prod-alert--success mb-6 font-mono text-sm" role="status">
                <pre class="whitespace-pre-wrap font-sans m-0">{{ session('success') }}</pre>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="admin-clay-panel admin-prod-form-shell overflow-hidden">
                <div class="admin-prod-form-head">
                    <h2 class="admin-prod-form-title">Allowed Artisan commands</h2>
                </div>
                <div class="admin-prod-form-body space-y-4">
                    <ul
                        class="max-h-48 overflow-y-auto custom-scrollbar text-sm text-slate-600 space-y-1 rounded-xl border border-slate-200/70 p-3 bg-slate-50/50">
                        @foreach ($allowedCommands as $cmd)
                            <li><code class="text-slate-800">{{ $cmd }}</code></li>
                        @endforeach
                    </ul>

                    <form action="{{ route($commandRoutes['execute']) }}" method="POST" class="space-y-4 border-t border-slate-200/60 pt-4">
                        @csrf
                        <div>
                            <label for="command" class="admin-prod-label">Command</label>
                            <select name="command" id="command" class="admin-prod-select">
                                @foreach ($allowedCommands as $cmd)
                                    <option value="{{ $cmd }}" @selected(old('command') === $cmd)>{{ $cmd }}</option>
                                @endforeach
                            </select>
                            @error('command')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex flex-wrap gap-4">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="force" value="1" class="rounded border-slate-300 text-[#fa8900] focus:ring-[#fa8900]" @checked(old('force'))>
                                <span>--force</span>
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" name="seed" value="1" class="rounded border-slate-300 text-[#fa8900] focus:ring-[#fa8900]" @checked(old('seed'))>
                                <span>--seed (<code class="text-xs">migrate:fresh</code> / <code class="text-xs">migrate:refresh</code> only; <code class="text-xs">db:fresh-seed</code> always seeds)</span>
                            </label>
                        </div>
                        <button type="submit" class="rounded-lg bg-[#232f3e] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#19212c]">
                            Run command
                        </button>
                    </form>
                    <p class="text-xs text-slate-500">JSON: <code class="text-slate-700">GET /command/{command}</code> (same whitelist).</p>
                </div>
            </div>

            <div class="admin-clay-panel admin-prod-form-shell overflow-hidden">
                <div class="admin-prod-form-head">
                    <h2 class="admin-prod-form-title">Migrations</h2>
                    <p class="admin-prod-form-hint">Run <code class="text-xs">migrate --force</code> for all pending files, or <code class="text-xs">migrate --path=…</code> for one file.</p>
                </div>
                <div class="admin-prod-form-body space-y-4">
                    <form action="{{ route($commandRoutes['migrateForce']) }}" method="POST" class="space-y-3">
                        @csrf
                        <p class="text-sm text-slate-600">Run all pending migrations in production mode.</p>
                        @error('migrate_force')
                            <p class="text-red-600 text-xs font-semibold">{{ $message }}</p>
                        @enderror
                        <button type="submit" class="admin-prod-btn-primary">
                            migrate --force
                        </button>
                    </form>

                    <div class="border-t border-slate-200/60 pt-4">
                        <p class="text-xs text-slate-500 mb-3">Or run a single file with <code class="text-slate-700">migrate --path=… --force</code></p>
                    <form action="{{ route($commandRoutes['migratePath']) }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <label for="migration" class="admin-prod-label">Migration file</label>
                            @if (count($migrationFiles) === 0)
                                <p class="admin-prod-alert admin-prod-alert--warning !mb-0 text-sm">No files under <code>database/migrations</code>.</p>
                            @else
                                <select name="migration" id="migration" class="admin-prod-select max-h-40">
                                    @foreach ($migrationFiles as $file)
                                        <option value="{{ $file }}" @selected(old('migration') === $file)>{{ $file }}</option>
                                    @endforeach
                                </select>
                            @endif
                            @error('migration')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" @disabled(count($migrationFiles) === 0)
                            class="admin-prod-btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                            Migrate selected file
                        </button>
                    </form>
                    </div>

                    <div class="border-t border-slate-200/60 pt-4">
                        <h3 class="text-sm font-bold text-slate-800 mb-2">migrate:status</h3>
                        <pre
                            class="text-xs text-slate-100 bg-[#1e293b] rounded-xl p-3 max-h-64 overflow-auto custom-scrollbar whitespace-pre-wrap font-mono border border-slate-700/50">{{ $migrateStatus }}</pre>
                    </div>

                    <form action="{{ route($commandRoutes['seedClass']) }}" method="POST" class="space-y-3 border-t border-slate-200/60 pt-4">
                        @csrf
                        <div>
                            <label for="seeder_class" class="admin-prod-label">Seeder class</label>
                            @if (count($seederClasses) === 0)
                                <p class="admin-prod-alert admin-prod-alert--warning !mb-0 text-sm">No seeders found under <code>database/seeders</code>.</p>
                            @else
                                <select name="seeder_class" id="seeder_class" class="admin-prod-select max-h-40">
                                    @foreach ($seederClasses as $class)
                                        <option value="{{ $class }}" @selected(old('seeder_class') === $class)>{{ $class }}</option>
                                    @endforeach
                                </select>
                            @endif
                            @error('seeder_class')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="force" value="1" class="rounded border-slate-300 text-[#fa8900] focus:ring-[#fa8900]" @checked(old('force'))>
                            <span>--force</span>
                        </label>
                        <button type="submit" @disabled(count($seederClasses) === 0)
                            class="admin-prod-btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                            Seed selected class
                        </button>
                    </form>

                    <form action="{{ route($commandRoutes['emptyTable']) }}" method="POST" class="space-y-3 border-t border-slate-200/60 pt-4">
                        @csrf
                        <div>
                            <label for="table" class="admin-prod-label">Table to empty</label>
                            @if (count($dbTables) === 0)
                                <p class="admin-prod-alert admin-prod-alert--warning !mb-0 text-sm">No database tables found.</p>
                            @else
                                <select name="table" id="table" class="admin-prod-select max-h-40">
                                    @foreach ($dbTables as $table)
                                        <option value="{{ $table }}" @selected(old('table') === $table)>{{ $table }}</option>
                                    @endforeach
                                </select>
                            @endif
                            @error('table')
                                <p class="text-red-600 text-xs mt-1.5 font-semibold">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-amber-700 mt-2">This removes all rows in the selected table.</p>
                        </div>
                        <button type="submit" @disabled(count($dbTables) === 0)
                            class="rounded-lg bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800 disabled:opacity-50 disabled:cursor-not-allowed">
                            Empty selected table
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="mt-6 admin-clay-panel admin-prod-form-shell overflow-hidden">
            <div class="admin-prod-form-head">
                <h2 class="admin-prod-form-title">PHP extensions</h2>
                <p class="admin-prod-form-hint">Loaded in this PHP process · “Track” is a checklist only (does not install).</p>
            </div>
            <div class="admin-prod-form-body space-y-4">
                <p class="text-xs text-slate-500">PHP {{ $phpVersion }} · SAPI <code>{{ $phpSapi }}</code></p>

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 mb-2">Loaded</h3>
                        <ul
                            class="text-sm text-slate-600 max-h-64 overflow-y-auto custom-scrollbar border border-slate-200/70 rounded-xl p-3 bg-slate-50/50 space-y-0.5">
                            @foreach ($extensions as $ext)
                                <li><code class="text-slate-800">{{ $ext }}</code></li>
                            @endforeach
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-sm font-bold text-slate-800 mb-2">Tracked checklist</h3>
                        @if (count($trackedExtensions))
                            <ul class="mb-4 space-y-2">
                                @foreach ($trackedExtensions as $ext)
                                    <li
                                        class="flex items-center justify-between gap-2 text-sm rounded-xl border border-slate-200/70 px-3 py-2 bg-slate-50/50">
                                        <span>
                                            <code class="text-slate-800">{{ $ext }}</code>
                                            @if (in_array($ext, $extensions, true))
                                                <span class="ml-2 text-xs text-emerald-600 font-semibold">loaded</span>
                                            @else
                                                <span class="ml-2 text-xs text-amber-600 font-semibold">not loaded</span>
                                            @endif
                                        </span>
                                        <form action="{{ route($commandRoutes['extensionUntrack']) }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="extension" value="{{ $ext }}">
                                            <button type="submit" class="admin-prod-link--danger text-xs font-semibold">Remove</button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-sm text-slate-500 mb-3">Nothing tracked yet.</p>
                        @endif

                        <form action="{{ route($commandRoutes['extensionTrack']) }}" method="POST" class="flex flex-wrap items-end gap-2">
                            @csrf
                            <div class="flex-1 min-w-[8rem]">
                                <label for="extension" class="admin-prod-label !text-xs">Extension (e.g. gd, intl)</label>
                                <input type="text" name="extension" id="extension" value="{{ old('extension') }}" placeholder="gd" class="admin-prod-input">
                            </div>
                            <button type="submit" class="rounded-lg bg-slate-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 shrink-0">
                                Add
                            </button>
                        </form>
                        @error('extension')
                            <p class="text-red-600 text-xs mt-2 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-superadmin-layout>
