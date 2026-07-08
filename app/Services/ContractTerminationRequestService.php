<?php

namespace App\Services;

use App\Models\ContractTerminationRequest;
use App\Models\GuestVendorInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class ContractTerminationRequestService
{
    private const FIELD_ROLES = ['agent', 'teamleader', 'regional_manager'];

    public function create(User $requester, string $reason): ContractTerminationRequest
    {
        $this->assertFieldUser($requester);

        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Please provide a reason for ending your contract.');
        }

        if (strlen($reason) > 5000) {
            throw new InvalidArgumentException('Reason is too long (max 5000 characters).');
        }

        $existing = ContractTerminationRequest::query()
            ->where('user_id', $requester->id)
            ->where('tenant_id', $requester->tenant_id)
            ->where('status', ContractTerminationRequest::STATUS_PENDING)
            ->first();

        if ($existing) {
            throw new InvalidArgumentException('You already have a pending contract termination request.');
        }

        $request = ContractTerminationRequest::create([
            'user_id' => $requester->id,
            'tenant_id' => (int) $requester->tenant_id,
            'role_at_request' => (string) $requester->role,
            'status' => ContractTerminationRequest::STATUS_PENDING,
            'reason' => $reason,
            'snapshot' => $this->buildSnapshot($requester),
        ]);

        return $request->fresh(['user', 'tenant']);
    }

    public function cancel(ContractTerminationRequest $request, User $requester): void
    {
        if ((int) $request->user_id !== (int) $requester->id) {
            abort(403);
        }

        if (! $request->isPending()) {
            throw new InvalidArgumentException('This request can no longer be cancelled.');
        }

        $request->update([
            'status' => ContractTerminationRequest::STATUS_CANCELLED,
            'decided_at' => now(),
        ]);
    }

    public function approve(ContractTerminationRequest $request, User $admin, ?string $adminNote = null): User
    {
        $this->assertAdminForTenant($admin, (int) $request->tenant_id);

        if (! $request->isPending()) {
            throw new InvalidArgumentException('This request is no longer pending.');
        }

        return DB::transaction(function () use ($request, $admin, $adminNote) {
            $locked = ContractTerminationRequest::query()->lockForUpdate()->findOrFail($request->id);

            if (! $locked->isPending()) {
                throw new InvalidArgumentException('This request is no longer pending.');
            }

            $user = User::withoutGlobalScopes()->lockForUpdate()->findOrFail($locked->user_id);

            if ((int) $user->tenant_id !== (int) $locked->tenant_id) {
                throw new InvalidArgumentException('This user is no longer assigned to this vendor.');
            }

            $update = [
                'role' => 'guest',
                'tenant_id' => null,
                'status' => 'active',
                'branch_id' => null,
                'team_leader_id' => null,
                'regional_manager_id' => null,
                'region_id' => null,
                'business_name' => null,
            ];

            if (Schema::hasColumn('users', 'ability')) {
                $update['ability'] = null;
            }
            if (Schema::hasColumn('users', 'notes')) {
                $update['notes'] = null;
            }

            User::withoutGlobalScopes()->whereKey($user->id)->update($update);

            GuestVendorInvitation::query()
                ->where('guest_user_id', $user->id)
                ->where('status', GuestVendorInvitation::STATUS_PENDING)
                ->update([
                    'status' => GuestVendorInvitation::STATUS_CANCELLED,
                    'responded_at' => now(),
                ]);

            $locked->update([
                'status' => ContractTerminationRequest::STATUS_APPROVED,
                'admin_note' => $adminNote,
                'decided_at' => now(),
                'decided_by' => $admin->id,
            ]);

            return User::withoutGlobalScopes()->findOrFail($user->id);
        });
    }

    public function reject(ContractTerminationRequest $request, User $admin, ?string $adminNote = null): void
    {
        $this->assertAdminForTenant($admin, (int) $request->tenant_id);

        if (! $request->isPending()) {
            throw new InvalidArgumentException('This request is no longer pending.');
        }

        $request->update([
            'status' => ContractTerminationRequest::STATUS_REJECTED,
            'admin_note' => $adminNote,
            'decided_at' => now(),
            'decided_by' => $admin->id,
        ]);
    }

    private function assertFieldUser(User $user): void
    {
        if (! in_array($user->role, self::FIELD_ROLES, true)) {
            throw new InvalidArgumentException('Only field users can request contract termination.');
        }

        if ($user->tenant_id === null) {
            throw new InvalidArgumentException('You are not assigned to a vendor.');
        }

        if (($user->status ?? 'active') !== 'active') {
            throw new InvalidArgumentException('Your account is not active.');
        }
    }

    private function assertAdminForTenant(User $admin, int $tenantId): void
    {
        if (! in_array($admin->role, ['admin', 'subadmin'], true)) {
            abort(403);
        }

        if ((int) $admin->tenant_id !== $tenantId) {
            abort(403);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSnapshot(User $user): array
    {
        return array_filter([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'branch_id' => $user->branch_id,
            'region_id' => $user->region_id ?? null,
            'team_leader_id' => $user->team_leader_id ?? null,
            'regional_manager_id' => $user->regional_manager_id ?? null,
            'business_name' => $user->business_name ?? null,
        ], fn ($v) => $v !== null);
    }
}
