<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectSuperadminFromAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($user->isSuperadmin() || $user->role === 'superadmin')) {
            return redirect()->route('superadmin.dashboard');
        }

        return $next($request);
    }
}
