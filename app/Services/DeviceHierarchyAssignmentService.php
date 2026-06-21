<?php

namespace App\Services;

use App\Models\AgentDeviceReturn;
use App\Models\AgentProductListAssignment;
use App\Models\ProductListItem;
use App\Models\RegionalManagerProductListAssignment;
use App\Models\TeamLeaderDeviceReturn;
use App\Models\TeamLeaderProductListAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeviceHierarchyAssignmentService
{
    /**
     * Admin → regional manager (warehouse pool).
     *
     * @param  array<int, int>  $productListIds
     */
    public function assignToRegionalManager(User $regionalManager, int $productId, array $productListIds): int
    {
        if ($regionalManager->role !== 'regional_manager') {
            throw new \InvalidArgumentException('Selected user is not a regional manager.');
        }

        $ids = array_values(array_unique(array_map('intval', $productListIds)));

        return DB::transaction(function () use ($regionalManager, $productId, $ids) {
            $added = 0;

            foreach ($ids as $listId) {
                $item = ProductListItem::lockForUpdate()->find($listId);
                $this->assertEligibleWarehouseDevice($item, $productId);

                RegionalManagerProductListAssignment::create([
                    'regional_manager_id' => $regionalManager->id,
                    'product_list_id' => $item->id,
                ]);
                $added++;
            }

            if ($added === 0) {
                throw new \InvalidArgumentException('No devices were assigned.');
            }

            return $added;
        });
    }

    /**
     * Regional manager → team leader (devices already held by this RM).
     *
     * @param  array<int, int>  $productListIds
     */
    public function assignToTeamLeader(User $regionalManager, User $teamLeader, int $productId, array $productListIds): int
    {
        if ($regionalManager->role !== 'regional_manager') {
            throw new \InvalidArgumentException('You are not a regional manager.');
        }
        if ($teamLeader->role !== 'teamleader') {
            throw new \InvalidArgumentException('Selected user is not a team leader.');
        }
        if ((int) $teamLeader->regional_manager_id !== (int) $regionalManager->id) {
            throw new \InvalidArgumentException('Team leader does not report to you.');
        }

        $ids = array_values(array_unique(array_map('intval', $productListIds)));

        return DB::transaction(function () use ($regionalManager, $teamLeader, $productId, $ids) {
            $added = 0;

            foreach ($ids as $listId) {
                $item = ProductListItem::lockForUpdate()->find($listId);
                $this->assertHeldByRegionalManager($item, $productId, (int) $regionalManager->id);
                if ($item->teamLeaderProductListAssignment) {
                    throw new \InvalidArgumentException('One or more devices are already with a team leader.');
                }
                if ($item->agentProductListAssignment) {
                    throw new \InvalidArgumentException('One or more devices are already with an agent.');
                }

                TeamLeaderProductListAssignment::create([
                    'team_leader_id' => $teamLeader->id,
                    'product_list_id' => $item->id,
                ]);
                $added++;
            }

            if ($added === 0) {
                throw new \InvalidArgumentException('No devices were assigned.');
            }

            return $added;
        });
    }

    /**
     * Team leader → agent (devices already held by this team leader).
     *
     * @param  array<int, int>  $productListIds
     */
    public function assignToAgent(User $teamLeader, User $agent, int $productId, array $productListIds): int
    {
        if ($teamLeader->role !== 'teamleader') {
            throw new \InvalidArgumentException('You are not a team leader.');
        }
        if ($agent->role !== 'agent') {
            throw new \InvalidArgumentException('Selected user is not an agent.');
        }
        if ((int) $agent->team_leader_id !== (int) $teamLeader->id) {
            throw new \InvalidArgumentException('Agent does not report to you.');
        }

        return app(AgentProductAssignmentService::class)->assignToAgentFromTeamLeader(
            $teamLeader,
            $agent,
            $productId,
            $productListIds
        );
    }

    /**
     * Agent → team leader (remove agent custody; TL row remains).
     *
     * @param  array<int, int>  $productListIds
     */
    public function returnFromAgentToTeamLeader(User $agent, array $productListIds, ?int $teamLeaderId = null): int
    {
        if ($agent->role !== 'agent') {
            throw new \InvalidArgumentException('You are not an agent.');
        }

        return app(AgentProductAssignmentService::class)->returnDevicesToTeamLeader($agent, $productListIds, $teamLeaderId);
    }

    /**
     * Team leader → regional manager (remove TL custody; ensure RM custody).
     *
     * @param  array<int, int>  $productListIds
     */
    public function returnFromTeamLeaderToRegionalManager(User $teamLeader, array $productListIds, ?int $regionalManagerId = null): int
    {
        if ($teamLeader->role !== 'teamleader') {
            throw new \InvalidArgumentException('You are not a team leader.');
        }

        $regionalManagerId = $regionalManagerId ?? (int) ($teamLeader->regional_manager_id ?? 0);
        if ($regionalManagerId <= 0) {
            throw new \InvalidArgumentException('You are not assigned to a regional manager.');
        }

        $ids = array_values(array_unique(array_map('intval', $productListIds)));
        if ($ids === []) {
            throw new \InvalidArgumentException('Select at least one device.');
        }

        return DB::transaction(function () use ($teamLeader, $regionalManagerId, $ids) {
            $returned = 0;

            foreach ($ids as $listId) {
                $item = ProductListItem::lockForUpdate()->find($listId);
                if (! $item || $item->isSold()) {
                    throw new \InvalidArgumentException('One or more devices are invalid or already sold.');
                }
                if ($item->agentProductListAssignment) {
                    throw new \InvalidArgumentException('One or more devices are still with an agent. They must return them first.');
                }
                $tlAssign = $item->teamLeaderProductListAssignment;
                if (! $tlAssign || (int) $tlAssign->team_leader_id !== (int) $teamLeader->id) {
                    throw new \InvalidArgumentException('One or more devices are not assigned to you.');
                }
                $tlAssign->delete();
                $this->ensureRegionalManagerAssignment((int) $item->id, $regionalManagerId);
                $returned++;
            }

            return $returned;
        });
    }

    public function ensureRegionalManagerAssignment(int $productListId, int $regionalManagerId): void
    {
        if ($regionalManagerId <= 0) {
            return;
        }

        $existing = RegionalManagerProductListAssignment::query()
            ->where('product_list_id', $productListId)
            ->first();

        if ($existing) {
            if ((int) $existing->regional_manager_id !== $regionalManagerId) {
                $existing->update(['regional_manager_id' => $regionalManagerId]);
            }

            return;
        }

        RegionalManagerProductListAssignment::create([
            'regional_manager_id' => $regionalManagerId,
            'product_list_id' => $productListId,
        ]);
    }

    public function ensureTeamLeaderAssignment(int $productListId, int $teamLeaderId): void
    {
        if ($teamLeaderId <= 0) {
            return;
        }

        $existing = TeamLeaderProductListAssignment::query()
            ->where('product_list_id', $productListId)
            ->first();

        if ($existing) {
            if ((int) $existing->team_leader_id !== $teamLeaderId) {
                $existing->update(['team_leader_id' => $teamLeaderId]);
            }

            return;
        }

        TeamLeaderProductListAssignment::create([
            'team_leader_id' => $teamLeaderId,
            'product_list_id' => $productListId,
        ]);
    }

    /**
     * Backfill missing hierarchy assignment rows for unsold devices.
     *
     * @return array{regional_manager: int, team_leader: int, from_returns: int}
     */
    public function repairMissingAssignments(): array
    {
        $counts = [
            'regional_manager' => 0,
            'team_leader' => 0,
            'from_returns' => 0,
        ];

        ProductListItem::query()
            ->whereNull('sold_at')
            ->whereNull('agent_sale_id')
            ->whereNull('pending_sale_id')
            ->whereNull('agent_credit_id')
            ->whereHas('teamLeaderProductListAssignment')
            ->whereDoesntHave('regionalManagerProductListAssignment')
            ->with('teamLeaderProductListAssignment.teamLeader:id,regional_manager_id')
            ->orderBy('id')
            ->chunkById(200, function ($items) use (&$counts) {
                foreach ($items as $item) {
                    $rmId = (int) ($item->teamLeaderProductListAssignment?->teamLeader?->regional_manager_id ?? 0);
                    if ($rmId <= 0) {
                        continue;
                    }
                    $this->ensureRegionalManagerAssignment((int) $item->id, $rmId);
                    $counts['regional_manager']++;
                }
            });

        ProductListItem::query()
            ->whereNull('sold_at')
            ->whereNull('agent_sale_id')
            ->whereNull('pending_sale_id')
            ->whereNull('agent_credit_id')
            ->whereHas('agentProductListAssignment')
            ->with([
                'agentProductListAssignment.agent:id,team_leader_id',
                'teamLeaderProductListAssignment.teamLeader:id,regional_manager_id',
            ])
            ->orderBy('id')
            ->chunkById(200, function ($items) use (&$counts) {
                foreach ($items as $item) {
                    $agent = $item->agentProductListAssignment?->agent;
                    $teamLeaderId = (int) ($agent?->team_leader_id ?? 0);
                    if ($teamLeaderId <= 0) {
                        continue;
                    }

                    if (! $item->teamLeaderProductListAssignment) {
                        $this->ensureTeamLeaderAssignment((int) $item->id, $teamLeaderId);
                        $counts['team_leader']++;
                        $item->load('teamLeaderProductListAssignment.teamLeader:id,regional_manager_id');
                    }

                    if (! $item->regionalManagerProductListAssignment) {
                        $rmId = (int) ($item->teamLeaderProductListAssignment?->teamLeader?->regional_manager_id ?? 0);
                        if ($rmId <= 0) {
                            continue;
                        }
                        $this->ensureRegionalManagerAssignment((int) $item->id, $rmId);
                        $counts['regional_manager']++;
                    }
                }
            });

        ProductListItem::query()
            ->whereNull('sold_at')
            ->whereNull('agent_sale_id')
            ->whereNull('pending_sale_id')
            ->whereNull('agent_credit_id')
            ->whereDoesntHave('regionalManagerProductListAssignment')
            ->whereDoesntHave('teamLeaderProductListAssignment')
            ->whereDoesntHave('agentProductListAssignment')
            ->orderBy('id')
            ->chunkById(200, function ($items) use (&$counts) {
                foreach ($items as $item) {
                    $rmId = $this->resolveRegionalManagerIdFromApprovedReturns((int) $item->id);
                    if ($rmId <= 0) {
                        continue;
                    }
                    $this->ensureRegionalManagerAssignment((int) $item->id, $rmId);
                    $counts['from_returns']++;
                }
            });

        return $counts;
    }

    private function resolveRegionalManagerIdFromApprovedReturns(int $productListId): int
    {
        $tlReturn = TeamLeaderDeviceReturn::query()
            ->where('status', TeamLeaderDeviceReturn::STATUS_APPROVED)
            ->whereHas('items', fn ($q) => $q->where('product_list_id', $productListId))
            ->orderByDesc('decided_at')
            ->orderByDesc('id')
            ->value('to_regional_manager_id');

        if ($tlReturn) {
            return (int) $tlReturn;
        }

        $agentReturn = AgentDeviceReturn::query()
            ->where('status', AgentDeviceReturn::STATUS_APPROVED)
            ->whereHas('items', fn ($q) => $q->where('product_list_id', $productListId))
            ->with('fromAgent:id,team_leader_id')
            ->orderByDesc('decided_at')
            ->orderByDesc('id')
            ->first();

        if (! $agentReturn) {
            return 0;
        }

        $teamLeaderId = (int) ($agentReturn->fromAgent?->team_leader_id ?? 0);
        if ($teamLeaderId <= 0) {
            $teamLeaderId = (int) ($agentReturn->to_team_leader_id ?? 0);
        }

        if ($teamLeaderId <= 0) {
            return 0;
        }

        return (int) (User::query()->whereKey($teamLeaderId)->value('regional_manager_id') ?? 0);
    }

    /**
     * Regional manager → admin warehouse (remove RM custody).
     *
     * @param  array<int, int>  $productListIds
     */
    public function returnFromRegionalManagerToAdmin(User $regionalManager, array $productListIds): int
    {
        if ($regionalManager->role !== 'regional_manager') {
            throw new \InvalidArgumentException('You are not a regional manager.');
        }

        $ids = array_values(array_unique(array_map('intval', $productListIds)));
        if ($ids === []) {
            throw new \InvalidArgumentException('Select at least one device.');
        }

        return DB::transaction(function () use ($regionalManager, $ids) {
            $returned = 0;

            foreach ($ids as $listId) {
                $item = ProductListItem::lockForUpdate()->find($listId);
                if (! $item || $item->isSold()) {
                    throw new \InvalidArgumentException('One or more devices are invalid or already sold.');
                }
                if ($item->teamLeaderProductListAssignment) {
                    throw new \InvalidArgumentException('One or more devices are still with a team leader.');
                }
                if ($item->agentProductListAssignment) {
                    throw new \InvalidArgumentException('One or more devices are still with an agent.');
                }
                $rmAssign = $item->regionalManagerProductListAssignment;
                if (! $rmAssign || (int) $rmAssign->regional_manager_id !== (int) $regionalManager->id) {
                    throw new \InvalidArgumentException('One or more devices are not assigned to you.');
                }
                $rmAssign->delete();
                $returned++;
            }

            return $returned;
        });
    }

    private function assertEligibleWarehouseDevice(?ProductListItem $item, int $productId): void
    {
        if (! $item || ! $item->isCatalogProduct($productId)) {
            throw new \InvalidArgumentException('One or more IMEIs do not belong to the selected product.');
        }
        if ($item->isSold()) {
            throw new \InvalidArgumentException('One or more devices are already sold.');
        }
        if (! $item->isPurchasePaid()) {
            throw new \InvalidArgumentException('One or more devices are not from an eligible purchase.');
        }
        if ($item->regionalManagerProductListAssignment || $item->teamLeaderProductListAssignment || $item->agentProductListAssignment) {
            throw new \InvalidArgumentException('One or more devices are already assigned in the hierarchy.');
        }
        if (app(RegionalManagerProductTransferService::class)->isProductListInAnyPendingTransfer($item->id)) {
            throw new \InvalidArgumentException('One or more devices are already in a pending transfer request.');
        }
        if (app(TeamLeaderProductTransferService::class)->isProductListInAnyPendingTransfer($item->id)) {
            throw new \InvalidArgumentException('One or more devices are already in a pending transfer request.');
        }
    }

    private function assertHeldByRegionalManager(?ProductListItem $item, int $productId, int $regionalManagerId): void
    {
        if (! $item || ! $item->isCatalogProduct($productId)) {
            throw new \InvalidArgumentException('One or more IMEIs do not belong to the selected product.');
        }
        if ($item->isSold()) {
            throw new \InvalidArgumentException('One or more devices are already sold.');
        }
        $rmAssign = $item->regionalManagerProductListAssignment;
        if (! $rmAssign || (int) $rmAssign->regional_manager_id !== $regionalManagerId) {
            throw new \InvalidArgumentException('One or more devices were not given to you by admin.');
        }
        if (app(TeamLeaderProductTransferService::class)->isProductListInAnyPendingTransfer($item->id)) {
            throw new \InvalidArgumentException('One or more devices are in a pending transfer request.');
        }
    }
}
