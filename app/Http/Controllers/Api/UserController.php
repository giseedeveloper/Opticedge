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

        $query = User::query()->withLocationRelations();

        if ($role !== null && $role !== '' && $role !== 'all') {
            if (! in_array($role, $allowed, true)) {
                $role = 'customer';
            }
            $query->where('role', $role);
        }

        $effectiveRole = ($role !== null && $role !== '' && $role !== 'all') ? $role : null;
        $sortParams = User::resolveDirectorySort(
            $request->string('sort')->trim()->toString(),
            $request->string('direction')->trim()->toString(),
            $effectiveRole
        );

        $users = $query
            ->directoryOrder($sortParams['sort'], $sortParams['direction'], $effectiveRole)
            ->take(200)
            ->get()
            ->map(fn (User $user) => $user->toDirectoryListArray());

        return response()->json(['data' => $users]);
    }
}
