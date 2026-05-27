<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->isSuperadmin() || $user->role === 'superadmin') {
            return redirect()->route('superadmin.dashboard');
        }

        if (! in_array($user->role, ['admin', 'subadmin'], true)) {
            abort(403, 'Vendor admin access requires an admin or subadmin account.');
        }

        if ($user->tenant_id === null) {
            abort(403, 'This account is not linked to a vendor. Sign in with a vendor admin user or use the platform console.');
        }

        return $next($request);
    }
}
