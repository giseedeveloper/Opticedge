<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SubadminRole;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return response()->json(['data' => $settings]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable|string',
        ])['settings'] ?? [];

        foreach ($data as $key => $value) {
            // `settings.key` is globally unique; match by key only so legacy NULL-tenant
            // rows are updated instead of creating a conflicting insert.
            Setting::query()->withoutGlobalScopes()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value === null ? null : (string) $value,
                    'tenant_id' => auth()->user()?->tenant_id,
                ]
            );
        }

        return response()->json(['message' => 'Settings updated.', 'data' => Setting::all()->pluck('value', 'key')]);
    }

    public function storageLink()
    {
        $user = auth()->user();
        if (! $user || $user->role !== 'admin') {
            return response()->json(['message' => 'Only full admins can run storage setup.'], 403);
        }

        $storageDir = public_path('storage');
        $legacyDir = storage_path('app/public');

        try {
            if (is_link($storageDir)) {
                unlink($storageDir);
            }
            if (! is_dir($storageDir)) {
                \Illuminate\Support\Facades\File::makeDirectory($storageDir, 0755, true);
                \Illuminate\Support\Facades\File::put($storageDir.'/.gitignore', "*\n!.gitignore\n");
            }
            foreach (['products', 'categories', 'receipts'] as $sub) {
                $path = $storageDir.'/'.$sub;
                if (! is_dir($path)) {
                    \Illuminate\Support\Facades\File::makeDirectory($path, 0755, true);
                }
            }
            if (is_dir($legacyDir)) {
                foreach (['products', 'categories', 'receipts'] as $sub) {
                    $src = $legacyDir.'/'.$sub;
                    $dst = $storageDir.'/'.$sub;
                    if (! is_dir($src)) {
                        continue;
                    }
                    foreach (glob($src.'/*') ?: [] as $file) {
                        if (is_file($file)) {
                            $dest = $dst.'/'.basename($file);
                            if (! file_exists($dest)) {
                                if (! is_dir($dst)) {
                                    \Illuminate\Support\Facades\File::makeDirectory($dst, 0755, true);
                                }
                                copy($file, $dest);
                            }
                        }
                    }
                }
            }

            return response()->json(['message' => 'Storage directory ready. Uploads use public/storage (no symlink).']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Storage setup failed: '.$e->getMessage()], 500);
        }
    }

    public function roles()
    {
        $ready = Schema::hasTable('subadmin_roles')
            && Schema::hasTable('subadmin_role_permissions')
            && Schema::hasColumn('users', 'subadmin_role_id');
        if (! $ready) {
            return response()->json(['data' => ['roles' => [], 'ability_matrix' => [], 'granted' => []]]);
        }

        $roles = SubadminRole::withCount('users')->orderBy('name')->get()->map(function ($r) {
            return [
                'id' => $r->id,
                'name' => $r->name,
                'description' => $r->description,
                'users_count' => $r->users_count,
            ];
        })->values()->all();

        return response()->json([
            'data' => [
                'roles' => $roles,
                'ability_matrix' => $this->buildAbilityMatrix(),
            ],
        ]);
    }

    public function storeRole(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:subadmin_roles,name',
            'description' => 'nullable|string|max:500',
        ]);
        $role = SubadminRole::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);
        return response()->json([
            'message' => 'Role created successfully.',
            'data' => ['id' => $role->id],
        ], 201);
    }

    public function rolePermissions(int $id)
    {
        $role = SubadminRole::with('permissions')->findOrFail($id);
        $granted = $role->permissions->map(fn ($p) => $p->module.'.'.$p->action)->values()->all();
        return response()->json(['data' => ['granted' => $granted]]);
    }

    public function updateRolePermissions(Request $request, int $id)
    {
        $role = SubadminRole::findOrFail($id);
        $validated = $request->validate([
            'permissions' => 'array',
            'permissions.*' => ['string', Rule::in($this->availablePermissionKeys())],
        ]);
        $permissions = collect($validated['permissions'] ?? [])
            ->filter(fn ($k) => is_string($k) && str_contains($k, '.'))
            ->map(function ($k) {
                [$module, $action] = explode('.', $k, 2);
                return ['module' => $module, 'action' => $action];
            })->values();
        $role->permissions()->delete();
        if ($permissions->isNotEmpty()) {
            $role->permissions()->createMany($permissions->all());
        }
        return response()->json(['message' => 'Role permissions updated successfully.']);
    }

    private function availablePermissionKeys(): array
    {
        $keys = [];
        foreach ($this->buildAbilityMatrix() as $module => $actions) {
            foreach ($actions as $action) {
                $keys[] = $module.'.'.$action;
            }
        }
        return $keys;
    }

    private function buildAbilityMatrix(): array
    {
        $actionOrder = ['view', 'create', 'edit', 'delete', 'approve', 'export', 'all'];
        $modules = [];
        foreach (Route::getRoutes() as $route) {
            $name = (string) $route->getName();
            if (! str_starts_with($name, 'admin.')) continue;
            $segments = explode('.', $name);
            $module = $segments[1] ?? 'dashboard';
            if ($module === '') {
                $module = 'dashboard';
            }
            $modules[$module] = true;
        }
        $matrix = [];
        $moduleNames = array_keys($modules);
        sort($moduleNames);
        foreach ($moduleNames as $module) {
            // Keep API validator aligned with web UI's fixed action columns.
            $matrix[$module] = $actionOrder;
        }
        return $matrix;
    }

    private function resolveRouteAction(string $routeName, array $methods): string
    {
        if (str_contains($routeName, '.export') || str_contains($routeName, '.download') || str_contains($routeName, '.invoice') || str_contains($routeName, '.report')) return 'export';
        if (str_contains($routeName, '.approve') || str_contains($routeName, '.reject') || str_contains($routeName, '.status')) return 'approve';
        if (str_contains($routeName, '.destroy') || str_contains($routeName, '.delete')) return 'delete';
        if (str_contains($routeName, '.edit') || str_contains($routeName, '.update')) return 'edit';
        if (str_contains($routeName, '.create') || str_contains($routeName, '.store')) return 'create';
        if (in_array('GET', $methods, true) || in_array('HEAD', $methods, true) || str_contains($routeName, '.index') || str_contains($routeName, '.show')) return 'view';
        return 'all';
    }
}
