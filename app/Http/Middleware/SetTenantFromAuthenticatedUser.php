<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantFromAuthenticatedUser
{
    public function handle(Request $request, Closure $next): Response
    {
        TenantContext::clear();

        $user = $request->user();
        if ($user) {
            if ($user->isSuperadmin()) {
                TenantContext::bypass();
            } elseif ($user->tenant_id !== null) {
                TenantContext::set($user->tenant_id);
            }
        }

        return $next($request);
    }
}
