<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\PaymentOption;
use App\Models\SubadminRole;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        $paymentOptions = PaymentOption::visible()->orderBy('name')->get(['id', 'name']);
        $rolesFeatureReady = Schema::hasTable('subadmin_roles')
            && Schema::hasTable('subadmin_role_permissions')
            && Schema::hasColumn('users', 'subadmin_role_id');

        if ($rolesFeatureReady) {
            $roles = SubadminRole::withCount('users')->orderBy('name')->get();
            $selectedRoleId = (int) request('role_id', $roles->first()?->id ?? 0);
            $selectedRole = $roles->firstWhere('id', $selectedRoleId) ?? $roles->first();
            $abilityMatrix = $this->buildAbilityMatrix();
            $granted = $selectedRole
                ? $selectedRole->permissions()->get()->map(fn ($p) => $p->module . '.' . $p->action)->all()
                : [];
        } else {
            $roles = collect();
            $selectedRole = null;
            $abilityMatrix = [];
            $granted = [];
        }

        return view('admin.settings.index', compact(
            'settings',
            'paymentOptions',
            'roles',
            'selectedRole',
            'abilityMatrix',
            'granted',
            'rolesFeatureReady'
        ));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'default_agent_sale_channel_id' => 'nullable|integer|exists:payment_options,id',
            'default_agent_commission_channel_id' => 'nullable|integer|exists:payment_options,id',
            'default_watu_channel_id' => 'nullable|integer|exists:payment_options,id',
        ]);

        foreach ($data as $key => $value) {
            // `settings.key` is globally unique; match by key only so legacy NULL-tenant
            // rows are updated instead of creating a conflicting insert.
            Setting::query()->withoutGlobalScopes()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value === null ? null : (string) $value,
                    'tenant_id' => auth()->user()->tenant_id,
                ]
            );
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }

    public function storeSubadminRole(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:subadmin_roles,name',
            'description' => 'nullable|string|max:500',
        ]);

        SubadminRole::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('admin.settings.index')->with('success', 'Role created successfully.');
    }

    public function updateSubadminRolePermissions(Request $request, SubadminRole $role)
    {
        $validated = $request->validate([
            'permissions' => 'array',
            'permissions.*' => [
                'string',
                Rule::in($this->availablePermissionKeys()),
            ],
        ]);

        $permissions = collect($validated['permissions'] ?? [])
            ->filter(fn ($k) => is_string($k) && str_contains($k, '.'))
            ->map(function ($k) {
                [$module, $action] = explode('.', $k, 2);

                return ['module' => $module, 'action' => $action];
            })
            ->unique(fn (array $permission) => $permission['module'] . '.' . $permission['action'])
            ->values();

        $role->permissions()->delete();
        if ($permissions->isNotEmpty()) {
            $role->permissions()->createMany($permissions->all());
        }

        return redirect()
            ->route('admin.settings.index', ['role_id' => $role->id])
            ->with('success', 'Role permissions updated successfully.');
    }

    private function availablePermissionKeys(): array
    {
        $keys = [];
        foreach ($this->buildAbilityMatrix() as $module => $actions) {
            foreach ($actions as $action) {
                $keys[] = $module . '.' . $action;
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
            if (! str_starts_with($name, 'admin.')) {
                continue;
            }

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
            // UI renders fixed action columns for each module, so backend must accept all of them.
            $matrix[$module] = $actionOrder;
        }

        return $matrix;
    }

    private function resolveRouteAction(string $routeName, array $methods): string
    {
        if (
            str_contains($routeName, '.export')
            || str_contains($routeName, '.download')
            || str_contains($routeName, '.invoice')
            || str_contains($routeName, '.report')
        ) {
            return 'export';
        }

        if (str_contains($routeName, '.approve') || str_contains($routeName, '.reject') || str_contains($routeName, '.status')) {
            return 'approve';
        }

        if (str_contains($routeName, '.destroy') || str_contains($routeName, '.delete')) {
            return 'delete';
        }

        if (str_contains($routeName, '.edit') || str_contains($routeName, '.update')) {
            return 'edit';
        }

        if (str_contains($routeName, '.create') || str_contains($routeName, '.store')) {
            return 'create';
        }

        if (in_array('GET', $methods, true) || in_array('HEAD', $methods, true) || str_contains($routeName, '.index') || str_contains($routeName, '.show')) {
            return 'view';
        }

        return 'all';
    }
}
