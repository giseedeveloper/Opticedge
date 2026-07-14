<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuestVendorInvitation;
use App\Models\User;
use App\Models\UserRating;
use App\Services\GuestVendorInvitationService;
use App\Services\WorkerReputationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class AdminGuestUserApiController extends Controller
{
    private const ASSIGNABLE_ROLES = ['agent', 'teamleader', 'regional_manager'];

    public function index(Request $request, WorkerReputationService $reputation): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $viewerTenantId = $request->user()?->tenant_id !== null
            ? (int) $request->user()->tenant_id
            : null;

        $users = User::withoutGlobalScopes()
            ->where('role', 'guest')
            ->whereNull('tenant_id')
            ->when($search !== '', fn ($q) => $q->directorySearch($search))
            ->orderByDesc('created_at')
            ->take(200)
            ->get();

        $userIds = $users->pluck('id')->all();
        $totals = $reputation->ratingTotalsForUserIds($userIds);
        $soldDevices = $reputation->soldDeviceCountsForUserIds($userIds, $viewerTenantId);

        $data = $users->map(function (User $user) use ($totals, $soldDevices) {
            $stats = $totals[$user->id] ?? ['avg_rating' => null, 'ratings_count' => 0];

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'profile_image_url' => $user->profile_image_url,
                'status' => $user->status ?? 'active',
                'created_at' => $user->created_at?->toISOString(),
                'avg_rating' => $stats['avg_rating'],
                'ratings_count' => $stats['ratings_count'],
                'sold_devices' => (int) ($soldDevices[$user->id] ?? 0),
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function show(int $guestUser, Request $request, WorkerReputationService $reputation): JsonResponse
    {
        $user = $this->findGuest($guestUser);
        $viewerTenantId = $request->user()?->tenant_id !== null
            ? (int) $request->user()->tenant_id
            : null;
        $reputationPayload = $reputation->profileReputationPayload($user, $viewerTenantId);

        return response()->json(['data' => array_merge([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'profile_image_url' => $user->profile_image_url,
            'status' => $user->status ?? 'active',
            'created_at' => $user->created_at?->toISOString(),
        ], $reputationPayload)]);
    }

    /**
     * Send an invitation for the guest to join this vendor (guest must accept).
     */
    public function assign(Request $request, int $guestUser, GuestVendorInvitationService $invitations): JsonResponse
    {
        $user = $this->findGuest($guestUser);
        $admin = $request->user();
        $tenantId = $admin?->tenant_id;

        if ($tenantId === null) {
            return response()->json(['message' => 'Your admin account is not linked to a vendor.'], 422);
        }

        $validated = $this->validateAssignment($request);
        $role = $validated['role'];

        $payload = $invitations->buildAssignmentPayload($role, $validated, $user);

        $invitation = $invitations->sendInvitation(
            $user,
            $admin,
            (int) $tenantId,
            $role,
            $payload,
            $validated['message'] ?? null,
        );

        return response()->json([
            'message' => 'Invitation sent. The guest must accept before joining your vendor.',
            'data' => $invitation->fresh()->toGuestListArray(),
        ], 201);
    }

    public function storeRating(Request $request, int $guestUser, WorkerReputationService $reputation): JsonResponse
    {
        $user = $this->findGuest($guestUser);
        $admin = $request->user();
        $tenantId = $admin?->tenant_id;

        if ($tenantId === null) {
            return response()->json(['message' => 'Your admin account is not linked to a vendor.'], 422);
        }

        $validated = $request->validate([
            'score' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ]);

        try {
            $rating = $reputation->upsertRating(
                $user,
                $admin,
                (int) $tenantId,
                (int) $validated['score'],
                $validated['comment'] ?? null,
                UserRating::SOURCE_MANUAL,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Rating saved.',
            'data' => $rating->toPublicArray(),
            'summary' => $reputation->ratingSummary($user),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAssignment(Request $request): array
    {
        $role = $request->input('role', 'agent');
        if (! in_array($role, self::ASSIGNABLE_ROLES, true)) {
            abort(422, 'Invalid role. Choose agent, teamleader, or regional_manager.');
        }

        $rules = [
            'role' => ['required', Rule::in(self::ASSIGNABLE_ROLES)],
            'phone' => 'nullable|string|max:100',
            'business_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:10000',
            'message' => 'nullable|string|max:2000',
        ];

        if (in_array($role, self::ASSIGNABLE_ROLES, true)) {
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

        return $request->validate($rules);
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
