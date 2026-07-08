<?php

namespace App\Services;

use App\Models\GuestVendorInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class GuestVendorInvitationService
{
    private const ASSIGNABLE_ROLES = ['agent', 'teamleader', 'regional_manager'];

    /**
     * @param  array<string, mixed>  $assignmentPayload
     */
    public function sendInvitation(
        User $guest,
        User $admin,
        int $tenantId,
        string $role,
        array $assignmentPayload,
        ?string $message = null,
    ): GuestVendorInvitation {
        $this->assertGuestEligible($guest);

        if (! in_array($role, self::ASSIGNABLE_ROLES, true)) {
            throw ValidationException::withMessages(['role' => ['Invalid role.']]);
        }

        $existing = GuestVendorInvitation::query()
            ->where('guest_user_id', $guest->id)
            ->where('tenant_id', $tenantId)
            ->where('status', GuestVendorInvitation::STATUS_PENDING)
            ->first();

        if ($existing) {
            $existing->update([
                'invited_by' => $admin->id,
                'proposed_role' => $role,
                'assignment_payload' => $assignmentPayload,
                'message' => $message,
            ]);

            $invitation = $existing->fresh();
            app(NotificationDispatchService::class)->guestInvitationReceived($guest, $invitation);

            return $invitation;
        }

        $invitation = GuestVendorInvitation::create([
            'guest_user_id' => $guest->id,
            'tenant_id' => $tenantId,
            'invited_by' => $admin->id,
            'proposed_role' => $role,
            'status' => GuestVendorInvitation::STATUS_PENDING,
            'assignment_payload' => $assignmentPayload,
            'message' => $message,
        ]);

        app(NotificationDispatchService::class)->guestInvitationReceived($guest, $invitation);

        return $invitation;
    }

    public function accept(GuestVendorInvitation $invitation, User $guest): User
    {
        $this->assertGuestEligible($guest);

        if ((int) $invitation->guest_user_id !== (int) $guest->id) {
            abort(403);
        }

        if (! $invitation->isPending()) {
            throw ValidationException::withMessages([
                'invitation' => ['This invitation is no longer available.'],
            ]);
        }

        $assigned = DB::transaction(function () use ($invitation, $guest) {
            $locked = GuestVendorInvitation::query()->lockForUpdate()->findOrFail($invitation->id);

            if (! $locked->isPending()) {
                throw ValidationException::withMessages([
                    'invitation' => ['This invitation is no longer available.'],
                ]);
            }

            $guestFresh = User::withoutGlobalScopes()->lockForUpdate()->findOrFail($guest->id);
            $this->assertGuestEligible($guestFresh);

            $payload = $locked->assignment_payload ?? [];
            $payload['role'] = $locked->proposed_role;
            $payload['tenant_id'] = $locked->tenant_id;
            $payload['status'] = 'active';
            $payload['phone'] = $payload['phone'] ?? $guestFresh->phone;

            if (Schema::hasColumn('users', 'ability')) {
                $payload['ability'] = 'fullaccess';
            }

            User::withoutGlobalScopes()->whereKey($guestFresh->id)->update($payload);

            $locked->update([
                'status' => GuestVendorInvitation::STATUS_ACCEPTED,
                'responded_at' => now(),
            ]);

            GuestVendorInvitation::query()
                ->where('guest_user_id', $guestFresh->id)
                ->where('id', '!=', $locked->id)
                ->where('status', GuestVendorInvitation::STATUS_PENDING)
                ->update([
                    'status' => GuestVendorInvitation::STATUS_CANCELLED,
                    'responded_at' => now(),
                ]);

            return User::withoutGlobalScopes()->findOrFail($guestFresh->id);
        });

        if ($assigned->role === 'agent') {
            app(NotificationDispatchService::class)->userActivated($assigned);
        }

        return $assigned;
    }

    public function decline(GuestVendorInvitation $invitation, User $guest): void
    {
        if ((int) $invitation->guest_user_id !== (int) $guest->id) {
            abort(403);
        }

        if (! $invitation->isPending()) {
            throw ValidationException::withMessages([
                'invitation' => ['This invitation is no longer available.'],
            ]);
        }

        $invitation->update([
            'status' => GuestVendorInvitation::STATUS_DECLINED,
            'responded_at' => now(),
        ]);
    }

    /**
     * Build assignment payload array from validated admin input.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function buildAssignmentPayload(string $role, array $validated, ?User $guest = null): array
    {
        $payload = [
            'phone' => $validated['phone'] ?? $guest?->phone,
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

        return $payload;
    }

    private function assertGuestEligible(User $user): void
    {
        if ($user->role !== 'guest' || $user->tenant_id !== null) {
            throw ValidationException::withMessages([
                'guest' => ['This user is no longer available as a guest.'],
            ]);
        }
    }
}
