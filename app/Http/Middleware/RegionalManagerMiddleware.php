<?php

namespace App\Http\Middleware;

use App\Support\TenantSuspension;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RegionalManagerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || $request->user()->role !== 'regional_manager') {
            abort(403);
        }

        $blockedReason = TenantSuspension::blocksLoginForUser($request->user());
        if ($blockedReason !== null) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => $blockedReason]);
        }

        return $next($request);
    }
}
