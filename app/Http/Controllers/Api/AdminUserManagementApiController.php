<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubadminRole;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $user = User::withLocationRelations()->findOrFail($user->id);
        $user->load('subadminRole:id,name');

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
        if ($role === 'regional_manager' && Schema::hasColumn('users', 'region_id')) {
            $rules['region_id'] = 'required|exists:regions,id';
            $rules['business_name'] = 'nullable|string|max:255';
            $rules['notes'] = 'nullable|string|max:10000';
        }
        if ($role === 'teamleader') {
            if (Schema::hasColumn('users', 'region_id')) {
                $rules['region_id'] = 'required|exists:regions,id';
            }
            if (Schema::hasColumn('users', 'branch_id')) {
                $rules['branch_id'] = 'required|exists:branches,id';
            }
            if (Schema::hasColumn('users', 'regional_manager_id')) {
                $rules['regional_manager_id'] = 'required|integer';
            }
            $rules['business_name'] = 'nullable|string|max:255';
            $rules['notes'] = 'nullable|string|max:10000';
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
        if ($role === 'regional_manager' && Schema::hasColumn('users', 'region_id')) {
            $payload['region_id'] = (int) $validated['region_id'];
            $payload['branch_id'] = null;
            $payload['regional_manager_id'] = null;
            if (isset($validated['business_name'])) {
                $payload['business_name'] = $validated['business_name'];
            }
            if (Schema::hasColumn('users', 'notes') && array_key_exists('notes', $validated)) {
                $payload['notes'] = $validated['notes'];
            }
        }
        if ($role === 'teamleader') {
            if (Schema::hasColumn('users', 'region_id') && isset($validated['region_id'])) {
                $payload['region_id'] = (int) $validated['region_id'];
            }
            if (isset($validated['business_name'])) {
                $payload['business_name'] = $validated['business_name'];
            }
            if (Schema::hasColumn('users', 'notes') && array_key_exists('notes', $validated)) {
                $payload['notes'] = $validated['notes'];
            }
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

        if ($user->role === 'agent') {
            app(\App\Services\NotificationDispatchService::class)->userActivated($user->fresh());
        }

        return response()->json(['message' => 'User activated.']);
    }

    public function deactivate(User $user): JsonResponse
    {
        $this->assertManageable($user);
        $user->update(['status' => 'inactive']);

        app(\App\Services\NotificationDispatchService::class)->userDeactivated($user->fresh());

        return response()->json(['message' => 'User deactivated.']);
    }

    public function approveDealer(User $user): JsonResponse
    {
        if ($user->role !== 'dealer') {
            return response()->json(['message' => 'User is not a dealer.'], 422);
        }
        $user->update(['status' => 'active']);

        app(\App\Services\NotificationDispatchService::class)->dealerApproved($user->fresh());

        return response()->json(['message' => 'Dealer approved.']);
    }

    public function rejectDealer(User $user): JsonResponse
    {
        if ($user->role !== 'dealer') {
            return response()->json(['message' => 'User is not a dealer.'], 422);
        }
        $user->update(['status' => 'suspended']);

        app(\App\Services\NotificationDispatchService::class)->dealerRejected($user->fresh());

        return response()->json(['message' => 'Dealer rejected.']);
    }

    public function subadminRoles(): JsonResponse
    {
        $roles = SubadminRole::orderBy('name')->get(['id', 'name']);

        return response()->json(['data' => $roles]);
    }

    public function createFormData(Request $request): JsonResponse
    {
        $role = $request->query('role', 'agent');
        $data = [
            'branches' => Schema::hasTable('branches')
                ? DB::table('branches')->orderBy('name')->get(['id', 'name'])
                : [],
            'regions' => Schema::hasTable('regions')
                ? DB::table('regions')->orderBy('name')->get(['id', 'name'])
                : [],
            'team_leaders' => User::where('role', 'teamleader')->orderBy('name')->get(['id', 'name', 'branch_id']),
            'regional_managers' => User::where('role', 'regional_manager')
                ->where('status', 'active')
                ->when(Schema::hasColumn('users', 'region_id'), fn ($q) => $q->whereNotNull('region_id'))
                ->orderBy('name')
                ->get(['id', 'name', 'region_id']),
            'subadmin_roles' => SubadminRole::orderBy('name')->get(['id', 'name']),
        ];

        if (Schema::hasTable('regions') && $data['regional_managers']->isNotEmpty()) {
            $regionNames = DB::table('regions')->pluck('name', 'id');
            $data['regional_managers'] = $data['regional_managers']->map(function ($row) use ($regionNames) {
                return [
                    'id' => $row->id,
                    'name' => $row->name,
                    'region_id' => $row->region_id,
                    'region_name' => $regionNames[$row->region_id] ?? null,
                ];
            })->values();
        }

        return response()->json(['data' => $data, 'role' => $role]);
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $this->assertManageable($user);

        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json(['message' => 'Password updated.']);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->assertManageable($user);

        if (($user->role ?? '') === 'admin') {
            return response()->json(['message' => 'Admin account cannot be deleted here.'], 422);
        }

        if ((int) $user->id === (int) auth()->id()) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        try {
            $user->delete();
        } catch (QueryException $e) {
            return response()->json(['message' => 'Cannot delete this user because it is linked to existing records.'], 422);
        }

        return response()->json(['message' => 'User deleted.']);
    }

    public function transferBranch(Request $request, User $user): JsonResponse
    {
        if ($user->role !== 'agent') {
            return response()->json(['message' => 'Only agents can be branch-transferred.'], 422);
        }

        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $branchId = $validated['branch_id'] ?? null;
        if ($branchId === '') {
            $branchId = null;
        }

        $user->update(['branch_id' => $branchId]);

        if (Schema::hasColumn('users', 'team_leader_id') && $user->team_leader_id) {
            $tl = User::query()->whereKey($user->team_leader_id)->where('role', 'teamleader')->first();
            if ($tl && $tl->branch_id && $branchId && (int) $tl->branch_id !== (int) $branchId) {
                $user->update(['team_leader_id' => null]);

                return response()->json([
                    'message' => 'Branch changed; team leader was cleared because branches no longer match.',
                ]);
            }
        }

        return response()->json(['message' => 'Agent branch updated.']);
    }

    public function updateTeamLeader(Request $request, User $user): JsonResponse
    {
        if ($user->role !== 'agent') {
            return response()->json(['message' => 'Only agents have team leaders.'], 422);
        }

        if (! Schema::hasColumn('users', 'team_leader_id')) {
            return response()->json(['message' => 'Run migrations to enable team leader assignment.'], 422);
        }

        $validated = $request->validate([
            'team_leader_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($q) {
                    $q->where('role', 'teamleader')->where('status', 'active');
                }),
            ],
        ]);

        $tlId = $validated['team_leader_id'] ?? null;
        if ($tlId === '' || $tlId === null) {
            $tlId = null;
        } else {
            $tlId = (int) $tlId;
            $tl = User::query()->whereKey($tlId)->where('role', 'teamleader')->first();
            if ($user->branch_id && $tl && $tl->branch_id && (int) $user->branch_id !== (int) $tl->branch_id) {
                return response()->json([
                    'message' => 'Team leader must belong to the same branch as this agent.',
                ], 422);
            }
        }

        $user->update(['team_leader_id' => $tlId]);

        return response()->json(['message' => 'Team leader updated.']);
    }

    public function myPermissions(): JsonResponse
    {
        $user = auth()->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->role === 'admin') {
            return response()->json([
                'data' => [
                    'role' => 'admin',
                    'full_access' => true,
                    'permissions' => [],
                ],
            ]);
        }

        if ($user->role !== 'subadmin') {
            return response()->json(['data' => ['role' => $user->role, 'full_access' => false, 'permissions' => []]]);
        }

        if (! Schema::hasColumn('users', 'subadmin_role_id') || ! Schema::hasTable('subadmin_roles')) {
            $full = ($user->ability ?? 'fullaccess') === 'fullaccess';

            return response()->json([
                'data' => [
                    'role' => 'subadmin',
                    'full_access' => $full,
                    'permissions' => $full ? [] : [['module' => '*', 'action' => 'view']],
                ],
            ]);
        }

        $role = $user->subadminRole()->with('permissions')->first();
        if (! $role) {
            return response()->json(['data' => ['role' => 'subadmin', 'full_access' => false, 'permissions' => []]]);
        }

        $fullAccess = in_array($role->system_key ?? '', ['fullaccess', 'view'], true)
            && ($role->system_key ?? '') === 'fullaccess';

        $permissions = $role->permissions->map(fn ($p) => [
            'module' => $p->module,
            'action' => $p->action,
        ])->values()->all();

        return response()->json([
            'data' => [
                'role' => 'subadmin',
                'full_access' => $fullAccess || ($role->system_key ?? '') === 'fullaccess',
                'view_only' => ($role->system_key ?? '') === 'view',
                'permissions' => $permissions,
            ],
        ]);
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
            'branch_name' => $user->listBranchName(),
            'region_id' => $user->region_id,
            'region_name' => $user->listRegionName(),
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
