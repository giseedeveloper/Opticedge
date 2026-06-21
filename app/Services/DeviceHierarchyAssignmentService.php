<?php

namespace App\Services;

use App\Models\ProductListItem;
use App\Models\RegionalManagerProductListAssignment;
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
     * Team leader → regional manager (remove TL custody; RM row remains).
     *
     * @param  array<int, int>  $productListIds
     */
    public function returnFromTeamLeaderToRegionalManager(User $teamLeader, array $productListIds): int
    {
        if ($teamLeader->role !== 'teamleader') {
            throw new \InvalidArgumentException('You are not a team leader.');
        }

        $ids = array_values(array_unique(array_map('intval', $productListIds)));
        if ($ids === []) {
            throw new \InvalidArgumentException('Select at least one device.');
        }

        return DB::transaction(function () use ($teamLeader, $ids) {
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
                $returned++;
            }

            return $returned;
        });
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
