<?php

namespace App\Services;

use App\Models\AgentProductListAssignment;
use App\Models\ProductListItem;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * Guards vendor exit: a field worker may not leave (or be removed) while holding
 * unsold devices, and must return them up the hierarchy to their major first.
 */
class WorkerCustodyGuardService
{
    /**
     * Major / supervisor for device return + termination approval.
     * agent → team leader; teamleader → regional manager; regional_manager → null (admin).
     */
    public function resolveMajor(User $user): ?User
    {
        if ($user->role === 'agent') {
            $id = (int) ($user->team_leader_id ?? 0);
            if ($id <= 0) {
                return null;
            }

            return User::withoutGlobalScopes()
                ->whereKey($id)
                ->where('role', 'teamleader')
                ->where('status', 'active')
                ->first();
        }

        if ($user->role === 'teamleader') {
            $id = (int) ($user->regional_manager_id ?? 0);
            if ($id <= 0) {
                return null;
            }

            return User::withoutGlobalScopes()
                ->whereKey($id)
                ->where('role', 'regional_manager')
                ->where('status', 'active')
                ->first();
        }

        return null;
    }

    public function majorRoleLabel(User $user): string
    {
        return match ($user->role) {
            'agent' => 'team leader',
            'teamleader' => 'regional manager',
            'regional_manager' => 'admin',
            default => 'supervisor',
        };
    }

    /**
     * Unsold devices that still prevent leaving the vendor.
     */
    public function blockingUnsoldDeviceCount(User $user): int
    {
        if (! Schema::hasTable('product_list')) {
            return 0;
        }

        return match ($user->role) {
            'agent' => $this->agentHeldCount((int) $user->id),
            'teamleader' => $this->teamLeaderHeldCount((int) $user->id)
                + $this->agentsUnderTeamLeaderHeldCount((int) $user->id),
            'regional_manager' => $this->regionalManagerHeldCount((int) $user->id)
                + $this->teamLeadersUnderRegionalManagerHeldCount((int) $user->id)
                + $this->agentsUnderRegionalManagerHeldCount((int) $user->id),
            default => 0,
        };
    }

    /**
     * @throws InvalidArgumentException
     */
    public function assertCanLeaveVendor(User $user): void
    {
        $count = $this->blockingUnsoldDeviceCount($user);
        if ($count <= 0) {
            return;
        }

        $major = $this->majorRoleLabel($user);
        $label = $count === 1 ? '1 unsold device' : "{$count} unsold devices";

        throw new InvalidArgumentException(
            "Cannot leave or be removed from the vendor while still holding {$label}. "
            ."Return all devices to your {$major} and wait for their acceptance first."
        );
    }

    private function agentHeldCount(int $agentId): int
    {
        if (! Schema::hasTable('agent_product_list_assignments')) {
            return 0;
        }

        return (int) ProductListItem::query()
            ->inAgentCustodyForReturnToTeamLeader($agentId)
            ->count();
    }

    private function teamLeaderHeldCount(int $teamLeaderId): int
    {
        if (! Schema::hasTable('team_leader_product_list_assignments')) {
            return 0;
        }

        return (int) ProductListItem::query()
            ->inTeamLeaderCustodyForAgentAssignment($teamLeaderId)
            ->count();
    }

    private function regionalManagerHeldCount(int $regionalManagerId): int
    {
        if (! Schema::hasTable('regional_manager_product_list_assignments')) {
            return 0;
        }

        return (int) ProductListItem::query()
            ->inRegionalManagerCustodyForTeamLeaderAssignment($regionalManagerId)
            ->count();
    }

    private function agentsUnderTeamLeaderHeldCount(int $teamLeaderId): int
    {
        if (! Schema::hasTable('agent_product_list_assignments') || ! Schema::hasColumn('users', 'team_leader_id')) {
            return 0;
        }

        $agentIds = User::withoutGlobalScopes()
            ->where('role', 'agent')
            ->where('team_leader_id', $teamLeaderId)
            ->pluck('id');

        if ($agentIds->isEmpty()) {
            return 0;
        }

        return (int) AgentProductListAssignment::query()
            ->whereIn('agent_id', $agentIds)
            ->whereHas('productListItem', fn ($q) => $q->whereNull('sold_at'))
            ->count();
    }

    private function teamLeadersUnderRegionalManagerHeldCount(int $regionalManagerId): int
    {
        if (! Schema::hasColumn('users', 'regional_manager_id')) {
            return 0;
        }

        $tlIds = User::withoutGlobalScopes()
            ->where('role', 'teamleader')
            ->where('regional_manager_id', $regionalManagerId)
            ->pluck('id');

        $total = 0;
        foreach ($tlIds as $tlId) {
            $total += $this->teamLeaderHeldCount((int) $tlId);
            $total += $this->agentsUnderTeamLeaderHeldCount((int) $tlId);
        }

        return $total;
    }

    private function agentsUnderRegionalManagerHeldCount(int $regionalManagerId): int
    {
        // Covered via team leaders under RM when hierarchy is complete.
        // Also catch agents linked directly via regional_manager_id if present.
        if (! Schema::hasColumn('users', 'regional_manager_id')
            || ! Schema::hasTable('agent_product_list_assignments')) {
            return 0;
        }

        $agentIds = User::withoutGlobalScopes()
            ->where('role', 'agent')
            ->where('regional_manager_id', $regionalManagerId)
            ->when(
                Schema::hasColumn('users', 'team_leader_id'),
                fn ($q) => $q->whereNull('team_leader_id')
            )
            ->pluck('id');

        if ($agentIds->isEmpty()) {
            return 0;
        }

        return (int) AgentProductListAssignment::query()
            ->whereIn('agent_id', $agentIds)
            ->whereHas('productListItem', fn ($q) => $q->whereNull('sold_at'))
            ->count();
    }
}
