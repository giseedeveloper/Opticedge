<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->query('role');
        $allowed = User::customerDirectoryRoleFilters();

        $query = User::query();

        if ($role !== null && $role !== '' && $role !== 'all') {
            if (! in_array($role, $allowed, true)) {
                $role = 'customer';
            }
            $query->where('role', $role);
        }

        $effectiveRole = ($role !== null && $role !== '' && $role !== 'all') ? $role : null;

        $users = $query
            ->orderBy(in_array($effectiveRole, ['agent', 'teamleader', 'regional_manager'], true) ? 'name' : 'created_at', 'asc')
            ->orderByDesc('created_at')
            ->take(200)
            ->get(['id', 'name', 'email', 'role', 'status', 'phone', 'business_name', 'created_at'])
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status ?? 'active',
                    'phone' => $user->phone,
                    'business_name' => $user->business_name,
                    'created_at' => $user->created_at?->toISOString(),
                ];
            });

        return response()->json(['data' => $users]);
    }
}
