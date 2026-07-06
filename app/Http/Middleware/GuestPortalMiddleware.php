<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GuestPortalMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'guest') {
            abort(403, 'Guest portal access requires a guest account.');
        }

        if (($user->status ?? 'active') !== 'active') {
            abort(403, 'Your account is not active.');
        }

        return $next($request);
    }
}
