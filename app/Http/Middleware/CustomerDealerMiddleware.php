<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerDealerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = $request->user()?->role;

        if (! in_array($role, ['customer', 'dealer'], true)) {
            abort(403, 'Customer or dealer access required.');
        }

        return $next($request);
    }
}
