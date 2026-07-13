<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GuestVendorInvitation;
use App\Models\User;
use App\Services\GuestVendorInvitationService;
use App\Services\WorkerReputationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class GuestPortalApiController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $pendingCount = $this->pendingInvitationsQuery($user)->count();

        return response()->json([
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'status' => $user->status ?? 'active',
                'avatar' => $user->avatar,
                'profile_image_url' => $user->profile_image_url,
                'pending_invitations_count' => $pendingCount,
                'message' => $pendingCount > 0
                    ? 'You have vendor requests waiting for your response.'
                    : 'Your account is registered. Vendors can send you assignment requests.',
            ],
        ]);
    }

    public function profile(Request $request, WorkerReputationService $reputation): JsonResponse
    {
        $user = $request->user();
        $reputationPayload = $reputation->profileReputationPayload($user);

        return response()->json([
            'data' => array_merge([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'profile_image_url' => $user->profile_image_url,
                'role' => $user->role,
                'email_verified' => $user->hasVerifiedEmail(),
                'created_at' => $user->created_at?->toISOString(),
            ], $reputationPayload),
        ]);
    }

    public function updateProfile(Request $request, WorkerReputationService $reputation): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:100',
            'experience_bio' => 'nullable|string|max:5000',
        ]);

        $payload = [
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
        ];
        if (Schema::hasColumn('users', 'experience_bio')) {
            $payload['experience_bio'] = $validated['experience_bio'] ?? null;
        }

        User::withoutGlobalScopes()
            ->whereKey($user->id)
            ->update($payload);

        $fresh = User::withoutGlobalScopes()->findOrFail($user->id);
        $reputationPayload = $reputation->profileReputationPayload($fresh);

        return response()->json([
            'message' => 'Profile updated.',
            'data' => array_merge([
                'id' => $fresh->id,
                'name' => $fresh->name,
                'email' => $fresh->email,
                'phone' => $fresh->phone,
                'avatar' => $fresh->avatar,
            ], $reputationPayload),
        ]);
    }

    public function invitations(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $request->query('status', 'pending');

        $query = GuestVendorInvitation::query()
            ->where('guest_user_id', $user->id)
            ->with(['tenant:id,name,brand_name', 'inviter:id,name'])
            ->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $items = $query->take(100)->get()->map(fn (GuestVendorInvitation $row) => $row->toGuestListArray());

        return response()->json(['data' => $items]);
    }

    public function acceptInvitation(Request $request, GuestVendorInvitation $invitation, GuestVendorInvitationService $service): JsonResponse
    {
        $assigned = $service->accept($invitation, $request->user());

        return response()->json([
            'message' => 'You joined the vendor successfully.',
            'data' => [
                'user' => $assigned->only(['id', 'name', 'email', 'role', 'tenant_id', 'status']),
                'role' => $assigned->role,
            ],
        ]);
    }

    public function declineInvitation(Request $request, GuestVendorInvitation $invitation, GuestVendorInvitationService $service): JsonResponse
    {
        $service->decline($invitation, $request->user());

        return response()->json(['message' => 'Invitation declined.']);
    }

    private function pendingInvitationsQuery(User $user)
    {
        return GuestVendorInvitation::query()
            ->where('guest_user_id', $user->id)
            ->where('status', GuestVendorInvitation::STATUS_PENDING);
    }
}
