<?php

namespace App\Http\Middleware;

use App\Support\TenantSuspension;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictSuspendedTenantAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! TenantSuspension::adminHasRestrictedAccess($user)) {
            return $next($request);
        }

        $routeName = (string) optional($request->route())->getName();

        if (in_array($routeName, TenantSuspension::adminAllowedRouteNames(), true)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your vendor subscription is suspended. Renew or upgrade your package to restore access.',
            ], 403);
        }

        return redirect()
            ->route('admin.tenant.edit')
            ->with('warning', 'Your vendor account is suspended. Renew or upgrade your subscription to restore full access.');
    }
}
