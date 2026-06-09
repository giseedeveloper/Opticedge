<?php

namespace App\Http\Middleware;

use App\Models\SubadminRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class SubadminAbilityMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'subadmin') {
            return $next($request);
        }

        // Backward compatibility before role-permission migration is applied.
        if (! Schema::hasColumn('users', 'subadmin_role_id') || ! Schema::hasTable('subadmin_roles')) {
            if (($user->ability ?? 'fullaccess') === 'fullaccess') {
                return $next($request);
            }

            $routeName = (string) optional($request->route())->getName();
            $isReadMethod = in_array($request->method(), ['GET', 'HEAD'], true);
            if (! $isReadMethod || str_contains($routeName, '.create') || str_contains($routeName, '.edit')) {
                abort(403, 'You have view-only access.');
            }

            return $next($request);
        }

        $role = $user->subadminRole()->with('permissions')->first();
        if (! $role) {
            abort(403, 'No subadmin role assigned.');
        }

        if (($role->system_key ?? '') === 'fullaccess') {
            return $next($request);
        }

        $isReadMethod = in_array($request->method(), ['GET', 'HEAD'], true);
        if (($role->system_key ?? '') === 'view' && $isReadMethod) {
            return $next($request);
        }

        $uri = trim((string) optional($request->route())->uri(), '/');
        if (in_array($uri, ['api/admin/users/my-permissions'], true)) {
            return $next($request);
        }

        [$module, $action] = $this->resolveModuleAndAction($request);
        $allowed = $this->hasPermission($role, $module, $action);

        if (! $allowed) {
            abort(403, 'You do not have permission to access this section.');
        }

        return $next($request);
    }

    private function hasPermission(SubadminRole $role, string $module, string $action): bool
    {
        $modules = [$module];
        if ($module === 'customers') {
            $modules[] = 'users';
        }

        return $role->permissions->contains(function ($perm) use ($modules, $action) {
            if (in_array($perm->module, $modules, true) && ($perm->action === $action || $perm->action === 'all')) {
                return true;
            }

            if ($perm->module === '*' && ($perm->action === 'all' || $perm->action === $action)) {
                return true;
            }

            return false;
        });
    }

    private function resolveModuleAndAction(Request $request): array
    {
        $routeName = (string) optional($request->route())->getName();
        $segments = $routeName !== '' ? explode('.', $routeName) : [];

        if (! empty($segments)) {
            $module = $segments[1] ?? 'dashboard';
        } else {
            $uri = trim((string) optional($request->route())->uri(), '/');
            $uriSegments = explode('/', $uri);
            if (($uriSegments[0] ?? '') === 'api') {
                $module = $uriSegments[2] ?? 'dashboard';
            } else {
                $module = $uriSegments[1] ?? 'dashboard';
            }
        }

        if ($module === '') {
            $module = 'dashboard';
        }

        $module = $this->normalizeApiModule($request, $module);
        $action = $this->inferAction($segments, $request->method(), $routeName, $request);

        return [$module, $action];
    }

    /**
     * Map unnamed mobile API routes to the same permission modules as the web admin sidebar.
     */
    private function normalizeApiModule(Request $request, string $module): string
    {
        $uri = trim((string) optional($request->route())->uri(), '/');
        if (! str_starts_with($uri, 'api/admin/')) {
            return $module;
        }

        if ($module === 'organization-tree' || $module === 'regional-managers') {
            return 'customers';
        }

        // Mobile stock routes use different URI segments; web permissions use admin.stock.* → module "stock".
        $stockApiPrefixes = [
            'api/admin/stocks',
            'api/admin/purchases',
            'api/admin/imei-search',
            'api/admin/imei-items',
            'api/admin/passthrough-sales',
            'api/admin/distribution-sales',
            'api/admin/agent-sales',
            'api/admin/agent-credits',
            'api/admin/branch-transfer',
            'api/admin/pending-sales',
            'api/admin/agent-transfers',
            'api/admin/product-list',
            'api/admin/barcodes',
        ];
        foreach ($stockApiPrefixes as $prefix) {
            if (str_starts_with($uri, $prefix)) {
                return 'stock';
            }
        }

        if ($module === 'brands') {
            return 'categories';
        }

        if ($module !== 'users') {
            return $module;
        }

        if (str_contains($uri, 'approve-dealer') || str_contains($uri, 'reject-dealer')) {
            return 'dealers';
        }

        $role = strtolower((string) $request->query('role', ''));
        if ($role === 'dealer') {
            return 'dealers';
        }
        if ($role === 'agent') {
            return 'agents';
        }
        if ($role === 'subadmin') {
            return 'subadmins';
        }

        return 'customers';
    }

    private function inferAction(array $segments, string $method, string $routeName, Request $request): string
    {
        $joined = implode('.', $segments);
        $method = strtoupper($method);
        $uri = trim((string) optional($request->route())->uri(), '/');

        if (str_contains($uri, 'approve-dealer') || str_contains($uri, 'reject-dealer')) {
            return 'approve';
        }

        if (str_contains($joined, '.create') || str_contains($joined, '.store')) {
            return 'create';
        }

        if (str_contains($joined, '.edit') || str_contains($joined, '.update')) {
            return 'edit';
        }

        if (str_contains($joined, '.destroy') || str_contains($joined, '.delete')) {
            return 'delete';
        }

        if (str_contains($joined, '.approve') || str_contains($joined, '.reject') || str_contains($joined, '.status')) {
            return 'approve';
        }

        if (
            str_contains($joined, '.export')
            || str_contains($joined, '.download')
            || str_contains($joined, '.invoice')
            || str_contains($joined, '.report')
        ) {
            return 'export';
        }

        if (str_contains($joined, '.index') || str_contains($joined, '.show') || $method === 'GET' || $method === 'HEAD') {
            return 'view';
        }

        if (str_starts_with($routeName, 'admin.')) {
            return 'edit';
        }

        return $method === 'GET' || $method === 'HEAD' ? 'view' : 'edit';
    }
}
