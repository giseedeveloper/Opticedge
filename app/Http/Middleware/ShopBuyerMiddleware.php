<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShopBuyerMiddleware
{
    /** Roles that may browse the shop and manage cart/orders/addresses. */
    private const ROLES = ['customer', 'dealer', 'teamleader', 'regional_manager'];

    public function handle(Request $request, Closure $next): Response
    {
        $role = $request->user()?->role;

        if (! in_array($role, self::ROLES, true)) {
            abort(403, 'Shop access not allowed for this role.');
        }

        return $next($request);
    }
}
