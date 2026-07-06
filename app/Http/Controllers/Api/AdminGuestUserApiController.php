<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationDispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AdminGuestUserApiController extends Controller
{
    private const ASSIGNABLE_ROLES = ['agent', 'teamleader', 'regional_manager'];

    /**
     * Cross-vendor list of unassigned guest users (tenant_id is null).
     */
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        $users = User::withoutGlobalScopes()
            ->where('role', 'guest')
            ->whereNull('tenant_id')
            ->when($search !== '', fn ($q) => $q->directorySearch($search))
            ->orderByDesc('created_at')
            ->take(200)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'profile_image_url' => $user->profile_image_url,
                'status' => $user->status ?? 'active',
                'created_at' => $user->created_at?->toISOString(),
            ]);

        return response()->json(['data' => $users]);
    }

    public function show(int $guestUser): JsonResponse
    {
        $user = $this->findGuest($guestUser);

        return response()->json(['data' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'status' => $user->status ?? 'active',
            'created_at' => $user->created_at?->toISOString(),
        ]]);
    }

    /**
     * Promote a guest to agent, team leader, or regional manager under the admin's vendor.
     */
    public function assign(Request $request, int $guestUser): JsonResponse
    {
        $user = $this->findGuest($guestUser);
        $admin = $request->user();
        $tenantId = $admin?->tenant_id;

        if ($tenantId === null) {
            return response()->json(['message' => 'Your admin account is not linked to a vendor.'], 422);
        }

        $role = $request->input('role', 'agent');
        if (! in_array($role, self::ASSIGNABLE_ROLES, true)) {
            return response()->json(['message' => 'Invalid role. Choose agent, teamleader, or regional_manager.'], 422);
        }

        $rules = [
            'role' => ['required', Rule::in(self::ASSIGNABLE_ROLES)],
            'phone' => 'nullable|string|max:100',
            'business_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:10000',
        ];

        if (in_array($role, ['agent', 'teamleader', 'regional_manager'], true)) {
            $rules['branch_id'] = 'nullable|exists:branches,id';
        }
        if ($role === 'agent' && Schema::hasColumn('users', 'team_leader_id')) {
            $rules['team_leader_id'] = 'nullable|integer';
        }
        if ($role === 'teamleader' && Schema::hasColumn('users', 'regional_manager_id')) {
            $rules['regional_manager_id'] = 'nullable|integer';
        }
        if ($role === 'regional_manager' && Schema::hasColumn('users', 'region_id')) {
            $rules['region_id'] = 'required|exists:regions,id';
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
        }

        $validated = $request->validate($rules);

        $payload = [
            'role' => $role,
            'tenant_id' => $tenantId,
            'status' => 'active',
            'phone' => $validated['phone'] ?? $user->phone,
            'business_name' => $validated['business_name'] ?? null,
        ];

        if (isset($validated['branch_id'])) {
            $payload['branch_id'] = $validated['branch_id'] ?: null;
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
        }
        if ($role === 'teamleader') {
            if (Schema::hasColumn('users', 'region_id') && isset($validated['region_id'])) {
                $payload['region_id'] = (int) $validated['region_id'];
            }
            if (Schema::hasColumn('users', 'notes') && array_key_exists('notes', $validated)) {
                $payload['notes'] = $validated['notes'];
            }
        }
        if (Schema::hasColumn('users', 'ability')) {
            $payload['ability'] = 'fullaccess';
        }

        User::withoutGlobalScopes()
            ->whereKey($user->id)
            ->update($payload);

        $assigned = User::withoutGlobalScopes()->findOrFail($user->id);

        if ($assigned->role === 'agent') {
            app(NotificationDispatchService::class)->userActivated($assigned);
        }

        return response()->json([
            'message' => 'Guest user assigned successfully.',
            'data' => $assigned->fresh(['branch', 'region', 'teamLeader', 'regionalManager'])->toDirectoryListArray(),
        ]);
    }

    private function findGuest(int $id): User
    {
        $user = User::withoutGlobalScopes()
            ->whereKey($id)
            ->where('role', 'guest')
            ->whereNull('tenant_id')
            ->first();

        if (! $user) {
            abort(404, 'Guest user not found.');
        }

        return $user;
    }
}
