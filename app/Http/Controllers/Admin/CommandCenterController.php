<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CommandCenterController extends Controller
{
    /**
     * @return list<string>
     */
    private function databaseTables(): array
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $rows = DB::select('SHOW TABLES');
            $tables = array_map(static function ($row) {
                $data = (array) $row;
                return (string) array_values($data)[0];
            }, $rows);
        } elseif ($driver === 'sqlite') {
            $rows = DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'");
            $tables = array_map(static fn ($row) => (string) $row->name, $rows);
        } else {
            $tables = [];
        }

        sort($tables);

        return $tables;
    }

    private function trackedExtensionsPath(): string
    {
        return storage_path('app/command_tracked_extensions.json');
    }

    /**
     * @return list<string>
     */
    private function getTrackedExtensions(): array
    {
        $path = $this->trackedExtensionsPath();
        if (! is_file($path)) {
            return [];
        }
        $j = json_decode((string) file_get_contents($path), true);

        return is_array($j) ? array_values(array_unique(array_filter(array_map('strtolower', $j)))) : [];
    }

    public function index()
    {
        $allowedCommands = ArtisanCommandController::allowedCommands();

        $migrationFiles = collect(glob(base_path('database/migrations/*.php')) ?: [])
            ->map(fn ($p) => basename((string) $p))
            ->sort()
            ->values()
            ->all();

        $seederClasses = collect(glob(base_path('database/seeders/*.php')) ?: [])
            ->map(fn ($p) => pathinfo((string) $p, PATHINFO_FILENAME))
            ->filter(fn ($name) => $name !== '' && $name !== 'DatabaseSeeder')
            ->map(fn ($name) => 'Database\\Seeders\\' . $name)
            ->sort()
            ->values()
            ->all();

        $migrateStatus = '';
        try {
            Artisan::call('migrate:status', ['--no-interaction' => true]);
            $migrateStatus = trim(Artisan::output());
        } catch (\Throwable $e) {
            $migrateStatus = 'Error: '.$e->getMessage();
        }

        // Same PHP process as this web request — extensions actually loaded on this server
        $extensions = array_values(array_unique(array_map('strtolower', get_loaded_extensions())));
        sort($extensions);

        $trackedExtensions = $this->getTrackedExtensions();
        $dbTables = $this->databaseTables();

        $phpVersion = PHP_VERSION;
        $phpSapi = PHP_SAPI;

        $commandRoutes = [
            'execute' => 'superadmin.command.execute',
            'migrateForce' => 'superadmin.command.migrate-force',
            'migratePath' => 'superadmin.command.migrate-path',
            'seedClass' => 'superadmin.command.seed-class',
            'emptyTable' => 'superadmin.command.empty-table',
            'extensionTrack' => 'superadmin.command.extension-track',
            'extensionUntrack' => 'superadmin.command.extension-untrack',
        ];

        return view('admin.command-center', compact(
            'allowedCommands',
            'migrationFiles',
            'seederClasses',
            'migrateStatus',
            'extensions',
            'trackedExtensions',
            'dbTables',
            'phpVersion',
            'phpSapi',
            'commandRoutes'
        ));
    }

    public function execute(Request $request)
    {
        $validated = $request->validate([
            'command' => 'required|string|max:128',
            'force' => 'nullable|boolean',
            'seed' => 'nullable|boolean',
        ]);

        $command = ArtisanCommandController::resolveAllowedCommand($validated['command']);
        if ($command === null) {
            return redirect()->back()->withInput()->withErrors(['command' => 'That command is not allowed.']);
        }

        $options = ['--no-interaction' => true];
        if (! empty($validated['force'])) {
            $options['--force'] = true;
        }
        if (! empty($validated['seed']) && in_array($command, ['migrate:fresh', 'migrate:refresh'], true)) {
            $options['--seed'] = true;
        }

        try {
            Artisan::call($command, $options);
            $output = trim(Artisan::output());

            return redirect()->back()->with(
                'success',
                $output !== '' ? $output : "Command [{$command}] finished."
            );
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->withErrors(['command' => $e->getMessage()]);
        }
    }

    public function migrateForce()
    {
        try {
            Artisan::call('migrate', [
                '--force' => true,
                '--no-interaction' => true,
            ]);
            $output = trim(Artisan::output());

            return redirect()->back()->with(
                'success',
                $output !== '' ? $output : 'migrate --force finished.'
            );
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['migrate_force' => $e->getMessage()]);
        }
    }

    public function migratePath(Request $request)
    {
        $validated = $request->validate([
            'migration' => 'required|string|max:255',
        ]);

        $base = basename($validated['migration']);
        if (! preg_match('/^[a-zA-Z0-9_\-]+\.php$/', $base)) {
            return redirect()->back()->withErrors(['migration' => 'Invalid migration filename.']);
        }

        $full = base_path('database/migrations/'.$base);
        if (! is_file($full)) {
            return redirect()->back()->withErrors(['migration' => 'Migration file not found in database/migrations.']);
        }

        $relative = 'database/migrations/'.$base;

        try {
            Artisan::call('migrate', [
                '--path' => $relative,
                '--force' => true,
                '--no-interaction' => true,
            ]);
            $output = trim(Artisan::output());

            return redirect()->back()->with(
                'success',
                $output !== '' ? $output : "Ran migration: {$base}"
            );
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['migration' => $e->getMessage()]);
        }
    }

    public function seedClass(Request $request)
    {
        $validated = $request->validate([
            'seeder_class' => 'required|string|max:255',
            'force' => 'nullable|boolean',
        ]);

        $allowedSeederClasses = collect(glob(base_path('database/seeders/*.php')) ?: [])
            ->map(fn ($p) => pathinfo((string) $p, PATHINFO_FILENAME))
            ->filter(fn ($name) => $name !== '' && $name !== 'DatabaseSeeder')
            ->map(fn ($name) => 'Database\\Seeders\\' . $name)
            ->values()
            ->all();

        if (! in_array($validated['seeder_class'], $allowedSeederClasses, true)) {
            return redirect()->back()->withErrors(['seeder_class' => 'Seeder class is not allowed.']);
        }

        $options = [
            '--class' => $validated['seeder_class'],
            '--no-interaction' => true,
        ];
        if (! empty($validated['force'])) {
            $options['--force'] = true;
        }

        try {
            Artisan::call('db:seed', $options);
            $output = trim(Artisan::output());

            return redirect()->back()->with(
                'success',
                $output !== '' ? $output : "Seeder [{$validated['seeder_class']}] finished."
            );
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['seeder_class' => $e->getMessage()]);
        }
    }

    public function emptyTable(Request $request)
    {
        $validated = $request->validate([
            'table' => 'required|string|max:128',
        ]);

        $allowedTables = $this->databaseTables();
        if (! in_array($validated['table'], $allowedTables, true)) {
            return redirect()->back()->withErrors(['table' => 'Table is not allowed.']);
        }

        $table = $validated['table'];
        $driver = DB::getDriverName();

        try {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
            }
            DB::table($table)->truncate();
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            return redirect()->back()->with('success', "Table [{$table}] data emptied.");
        } catch (\Throwable $e) {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            return redirect()->back()->withErrors(['table' => $e->getMessage()]);
        }
    }

    public function trackExtension(Request $request)
    {
        $validated = $request->validate([
            'extension' => 'required|string|max:64|regex:/^[a-zA-Z0-9_]+$/',
        ]);

        $name = strtolower($validated['extension']);
        $list = $this->getTrackedExtensions();
        if (! in_array($name, $list, true)) {
            $list[] = $name;
            sort($list);
            File::put($this->trackedExtensionsPath(), json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        return redirect()->back()->with('success', "Tracking extension: {$name}");
    }

    public function untrackExtension(Request $request)
    {
        $validated = $request->validate([
            'extension' => 'required|string|max:64',
        ]);

        $name = strtolower($validated['extension']);
        $list = array_values(array_filter($this->getTrackedExtensions(), fn ($e) => $e !== $name));
        File::put($this->trackedExtensionsPath(), json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return redirect()->back()->with('success', "Removed {$name} from tracked list.");
    }
}
