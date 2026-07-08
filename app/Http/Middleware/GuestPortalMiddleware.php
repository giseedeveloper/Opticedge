<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GuestPortalMiddleware
{
    private const INVITATION_ROLES = ['guest', 'agent', 'teamleader', 'regional_manager'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Guest portal access requires a guest account.');
        }

        if ($this->isInvitationRequest($request) && in_array($user->role, self::INVITATION_ROLES, true)) {
            if (($user->status ?? 'active') !== 'active') {
                abort(403, 'Your account is not active.');
            }

            return $next($request);
        }

        if ($user->role !== 'guest') {
            abort(403, 'Guest portal access requires a guest account.');
        }

        if (($user->status ?? 'active') !== 'active') {
            abort(403, 'Your account is not active.');
        }

        return $next($request);
    }

    private function isInvitationRequest(Request $request): bool
    {
        $path = trim($request->path(), '/');

        return str_starts_with($path, 'api/guest/invitations')
            || str_starts_with($path, 'guest/invitations');
    }
}
