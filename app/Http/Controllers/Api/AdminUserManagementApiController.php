<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubadminRole;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AdminUserManagementApiController extends Controller
{
    private const MANAGEABLE_ROLES = [
        'customer', 'dealer', 'agent', 'teamleader', 'regional_manager', 'subadmin',
    ];

    public function show(User $user): JsonResponse
    {
        $this->assertManageable($user);
        $user->load(['branch:id,name', 'teamLeader:id,name', 'regionalManager:id,name', 'subadminRole:id,name']);

        return response()->json(['data' => $this->serializeUser($user)]);
    }

    public function store(Request $request): JsonResponse
    {
        $role = $request->input('role', 'agent');
        if (! in_array($role, self::MANAGEABLE_ROLES, true)) {
            return response()->json(['message' => 'Invalid role.'], 422);
        }

        $rules = [
            'role' => ['required', Rule::in(self::MANAGEABLE_ROLES)],
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:100',
        ];

        if ($role === 'dealer') {
            $rules['business_name'] = 'required|string|max:255';
        }
        if (in_array($role, ['agent', 'teamleader', 'regional_manager'], true)) {
            $rules['branch_id'] = 'nullable|exists:branches,id';
        }
        if ($role === 'agent' && Schema::hasColumn('users', 'team_leader_id')) {
            $rules['team_leader_id'] = 'nullable|integer';
        }
        if ($role === 'teamleader' && Schema::hasColumn('users', 'regional_manager_id')) {
            $rules['regional_manager_id'] = 'nullable|integer';
        }
        if ($role === 'subadmin') {
            $rules['subadmin_role_id'] = 'required|exists:subadmin_roles,id';
        }

        $validated = $request->validate($rules);
        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role' => $role,
            'status' => $role === 'dealer' ? 'pending' : 'active',
        ];

        if ($role === 'dealer') {
            $payload['business_name'] = $validated['business_name'];
        }
        if (isset($validated['branch_id'])) {
            $payload['branch_id'] = $validated['branch_id'] ?: null;
        }
        if ($role === 'subadmin') {
            $payload['subadmin_role_id'] = $validated['subadmin_role_id'];
            $payload['branch_id'] = null;
        }
        if ($role === 'agent' && Schema::hasColumn('users', 'team_leader_id')) {
            $payload['team_leader_id'] = $validated['team_leader_id'] ?? null;
        }
        if ($role === 'teamleader' && Schema::hasColumn('users', 'regional_manager_id')) {
            $payload['regional_manager_id'] = $validated['regional_manager_id'] ?? null;
        }
        if (Schema::hasColumn('users', 'ability')) {
            $payload['ability'] = 'fullaccess';
        }

        $user = User::create($payload);
        $user->forceFill(['email_verified_at' => now()])->save();

        return response()->json([
            'message' => 'User created.',
            'data' => $this->serializeUser($user->fresh()),
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->assertManageable($user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:100',
            'password' => 'nullable|string|min:8|confirmed',
            'branch_id' => 'nullable|exists:branches,id',
            'business_name' => 'nullable|string|max:255',
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ];
        if (array_key_exists('branch_id', $validated)) {
            $payload['branch_id'] = $validated['branch_id'] ?: null;
        }
        if ($user->role === 'dealer' && isset($validated['business_name'])) {
            $payload['business_name'] = $validated['business_name'];
        }
        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);

        return response()->json([
            'message' => 'User updated.',
            'data' => $this->serializeUser($user->fresh()),
        ]);
    }

    public function activate(User $user): JsonResponse
    {
        $this->assertManageable($user);
        $user->update(['status' => 'active']);

        return response()->json(['message' => 'User activated.']);
    }

    public function deactivate(User $user): JsonResponse
    {
        $this->assertManageable($user);
        $user->update(['status' => 'inactive']);

        return response()->json(['message' => 'User deactivated.']);
    }

    public function approveDealer(User $user): JsonResponse
    {
        if ($user->role !== 'dealer') {
            return response()->json(['message' => 'User is not a dealer.'], 422);
        }
        $user->update(['status' => 'active']);

        return response()->json(['message' => 'Dealer approved.']);
    }

    public function rejectDealer(User $user): JsonResponse
    {
        if ($user->role !== 'dealer') {
            return response()->json(['message' => 'User is not a dealer.'], 422);
        }
        $user->update(['status' => 'suspended']);

        return response()->json(['message' => 'Dealer rejected.']);
    }

    public function subadminRoles(): JsonResponse
    {
        $roles = SubadminRole::orderBy('name')->get(['id', 'name']);

        return response()->json(['data' => $roles]);
    }

    private function assertManageable(User $user): void
    {
        if (! in_array($user->role, self::MANAGEABLE_ROLES, true)) {
            abort(404);
        }
    }

    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status ?? 'active',
            'phone' => $user->phone,
            'business_name' => $user->business_name,
            'branch_id' => $user->branch_id,
            'branch_name' => $user->branch?->name,
            'team_leader_id' => $user->team_leader_id ?? null,
            'team_leader_name' => $user->teamLeader?->name,
            'regional_manager_id' => $user->regional_manager_id ?? null,
            'regional_manager_name' => $user->regionalManager?->name,
            'subadmin_role_id' => $user->subadmin_role_id ?? null,
            'subadmin_role_name' => $user->subadminRole?->name,
            'created_at' => $user->created_at?->toISOString(),
        ];
    }
}
