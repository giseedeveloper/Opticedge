<?php

namespace App\Http\Controllers\Api\Superadmin;

use App\Http\Controllers\Admin\ArtisanCommandController;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SuperadminCommandCenterApiController extends Controller
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

    public function index(): JsonResponse
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
            ->map(fn ($name) => 'Database\\Seeders\\'.$name)
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

        $extensions = array_values(array_unique(array_map('strtolower', get_loaded_extensions())));
        sort($extensions);

        return response()->json([
            'data' => [
                'allowed_commands' => $allowedCommands,
                'migration_files' => $migrationFiles,
                'seeder_classes' => $seederClasses,
                'migrate_status' => $migrateStatus,
                'extensions' => $extensions,
                'tracked_extensions' => $this->getTrackedExtensions(),
                'db_tables' => $this->databaseTables(),
                'php_version' => PHP_VERSION,
                'php_sapi' => PHP_SAPI,
            ],
        ]);
    }

    public function execute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'command' => 'required|string|max:128',
            'force' => 'nullable|boolean',
            'seed' => 'nullable|boolean',
        ]);

        $command = ArtisanCommandController::resolveAllowedCommand($validated['command']);
        if ($command === null) {
            return response()->json(['message' => 'That command is not allowed.'], 422);
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

            return response()->json([
                'message' => $output !== '' ? $output : "Command [{$command}] finished.",
                'data' => ['command' => $command, 'output' => $output],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function migratePath(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'migration' => 'required|string|max:255',
        ]);

        $base = basename($validated['migration']);
        if (! preg_match('/^[a-zA-Z0-9_\-]+\.php$/', $base)) {
            return response()->json(['message' => 'Invalid migration filename.'], 422);
        }

        $full = base_path('database/migrations/'.$base);
        if (! is_file($full)) {
            return response()->json(['message' => 'Migration file not found in database/migrations.'], 422);
        }

        $relative = 'database/migrations/'.$base;

        try {
            Artisan::call('migrate', [
                '--path' => $relative,
                '--force' => true,
                '--no-interaction' => true,
            ]);
            $output = trim(Artisan::output());

            return response()->json([
                'message' => $output !== '' ? $output : "Ran migration: {$base}",
                'data' => ['migration' => $base, 'output' => $output],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function seedClass(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'seeder_class' => 'required|string|max:255',
            'force' => 'nullable|boolean',
        ]);

        $allowedSeederClasses = collect(glob(base_path('database/seeders/*.php')) ?: [])
            ->map(fn ($p) => pathinfo((string) $p, PATHINFO_FILENAME))
            ->filter(fn ($name) => $name !== '' && $name !== 'DatabaseSeeder')
            ->map(fn ($name) => 'Database\\Seeders\\'.$name)
            ->values()
            ->all();

        if (! in_array($validated['seeder_class'], $allowedSeederClasses, true)) {
            return response()->json(['message' => 'Seeder class is not allowed.'], 422);
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

            return response()->json([
                'message' => $output !== '' ? $output : "Seeder [{$validated['seeder_class']}] finished.",
                'data' => ['seeder_class' => $validated['seeder_class'], 'output' => $output],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function trackExtension(Request $request): JsonResponse
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

        return response()->json([
            'message' => "Tracking extension: {$name}",
            'data' => ['tracked_extensions' => $this->getTrackedExtensions()],
        ]);
    }

    public function untrackExtension(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'extension' => 'required|string|max:64',
        ]);

        $name = strtolower($validated['extension']);
        $list = array_values(array_filter($this->getTrackedExtensions(), fn ($e) => $e !== $name));
        File::put($this->trackedExtensionsPath(), json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return response()->json([
            'message' => "Removed {$name} from tracked list.",
            'data' => ['tracked_extensions' => $this->getTrackedExtensions()],
        ]);
    }

    public function runCommand(Request $request, string $command): JsonResponse
    {
        $resolved = ArtisanCommandController::resolveAllowedCommand($command);
        if ($resolved === null) {
            return response()->json([
                'message' => 'Command not allowed.',
                'data' => ['allowed' => ArtisanCommandController::allowedCommands()],
            ], 422);
        }

        $options = ['--no-interaction' => true];
        if ($request->boolean('force')) {
            $options['--force'] = true;
        }
        if ($request->boolean('seed') && in_array($resolved, ['migrate:fresh', 'migrate:refresh'], true)) {
            $options['--seed'] = true;
        }

        try {
            Artisan::call($resolved, $options);
            $output = trim(Artisan::output());

            return response()->json([
                'message' => $output !== '' ? $output : "Command [{$resolved}] executed successfully.",
                'data' => ['command' => $resolved, 'output' => $output],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function emptyTable(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'table' => 'required|string|max:128',
        ]);

        $allowedTables = $this->databaseTables();
        if (! in_array($validated['table'], $allowedTables, true)) {
            return response()->json(['message' => 'Table is not allowed.'], 422);
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

            return response()->json(['message' => "Table [{$table}] data emptied."]);
        } catch (\Throwable $e) {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
