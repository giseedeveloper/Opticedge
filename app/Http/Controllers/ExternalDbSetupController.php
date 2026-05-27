<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ValidatesOpticDbPass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ExternalDbSetupController extends Controller
{
    use ValidatesOpticDbPass;

    /**
     * Run migrate then db:seed via GET when ?pass= matches config optic.db_seed_pass.
     */
    public function __invoke(Request $request): JsonResponse
    {
        if ($deny = $this->opticDbPassFailed($request)) {
            return $deny;
        }

        $steps = [];

        try {
            Artisan::call('migrate', [
                '--force' => true,
                '--no-interaction' => true,
            ]);
            $steps['migrate'] = [
                'ok' => true,
                'message' => trim(Artisan::output()) ?: 'Migrations finished.',
            ];
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'command' => 'setup',
                'steps' => $steps,
                'message' => 'Migration failed: '.$e->getMessage(),
            ], 500);
        }

        $seedOptions = [
            '--force' => true,
            '--no-interaction' => true,
        ];

        $class = $request->query('class');
        if ($class !== null && $class !== '') {
            $class = (string) $class;
            if (! preg_match('/^[A-Za-z0-9\\\\_]+$/', $class)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Invalid class parameter.',
                    'steps' => $steps,
                ], 422);
            }
            if (! str_starts_with($class, 'Database\\Seeders\\')) {
                $class = 'Database\\Seeders\\'.$class;
            }
            $seedOptions['--class'] = $class;
        }

        try {
            Artisan::call('db:seed', $seedOptions);
            $steps['seed'] = [
                'ok' => true,
                'class' => $seedOptions['--class'] ?? 'default',
                'message' => trim(Artisan::output()) ?: 'Seeding finished.',
            ];
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'command' => 'setup',
                'steps' => $steps,
                'message' => 'Seeding failed: '.$e->getMessage(),
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'command' => 'setup',
            'steps' => $steps,
            'message' => 'Database migrated and seeded successfully.',
        ]);
    }
}
