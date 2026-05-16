<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->query('role', 'customer');
        if (! in_array($role, User::customerDirectoryRoleFilters(), true)) {
            $role = 'customer';
        }

        $users = User::where('role', $role)
            ->orderBy(in_array($role, ['agent', 'teamleader', 'regional_manager'], true) ? 'name' : 'created_at')
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
