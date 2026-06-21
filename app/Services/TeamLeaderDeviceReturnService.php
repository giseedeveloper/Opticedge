<?php

namespace App\Services;

use App\Models\ProductListItem;
use App\Models\TeamLeaderDeviceReturn;
use App\Models\TeamLeaderDeviceReturnItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TeamLeaderDeviceReturnService
{
    /**
     * @param  array<int, int>  $productListIds
     */
    public function createByTeamLeader(User $teamLeader, int $productId, array $productListIds, ?string $message = null): TeamLeaderDeviceReturn
    {
        if ($teamLeader->role !== 'teamleader') {
            throw new \InvalidArgumentException('You are not a team leader.');
        }

        $regionalManagerId = (int) ($teamLeader->regional_manager_id ?? 0);
        if ($regionalManagerId <= 0) {
            throw new \InvalidArgumentException('You are not assigned to a regional manager.');
        }

        $ids = array_values(array_unique(array_map('intval', $productListIds)));
        if ($ids === []) {
            throw new \InvalidArgumentException('Select at least one device.');
        }

        return DB::transaction(function () use ($teamLeader, $regionalManagerId, $productId, $ids, $message) {
            foreach ($ids as $listId) {
                $item = ProductListItem::lockForUpdate()->find($listId);
                $this->assertEligibleForReturn($item, $productId, (int) $teamLeader->id);
            }

            $return = TeamLeaderDeviceReturn::create([
                'from_team_leader_id' => $teamLeader->id,
                'to_regional_manager_id' => $regionalManagerId,
                'status' => TeamLeaderDeviceReturn::STATUS_PENDING,
                'message' => $message,
            ]);

            foreach ($ids as $listId) {
                TeamLeaderDeviceReturnItem::create([
                    'team_leader_device_return_id' => $return->id,
                    'product_list_id' => $listId,
                ]);
            }

            $loaded = $return->load('items');
            $recipient = User::find($regionalManagerId);
            if ($recipient) {
                app(NotificationDispatchService::class)->returnIncoming(
                    $recipient,
                    $teamLeader,
                    (int) $loaded->id,
                    $loaded->items->count(),
                    'team_leader_to_regional_manager',
                );
            }

            return $loaded;
        });
    }

    public function isProductListInAnyPendingReturn(int $productListId): bool
    {
        return TeamLeaderDeviceReturnItem::query()
            ->where('product_list_id', $productListId)
            ->whereHas('returnRequest', fn ($q) => $q->where('status', TeamLeaderDeviceReturn::STATUS_PENDING))
            ->exists();
    }

    public function acceptByRecipient(TeamLeaderDeviceReturn $return, User $recipient, ?string $note = null): void
    {
        if (! $return->isPending()) {
            throw new \InvalidArgumentException('Return request is not pending.');
        }
        if ((int) $return->to_regional_manager_id !== (int) $recipient->id) {
            throw new \InvalidArgumentException('Only the receiving regional manager can accept this return.');
        }

        DB::transaction(function () use ($return, $recipient, $note) {
            $return->load(['items', 'fromTeamLeader']);
            $ids = $return->items->pluck('product_list_id')->all();

            foreach ($ids as $listId) {
                if ($this->isProductListInAnyPendingReturnExcept($listId, $return->id)) {
                    throw new \InvalidArgumentException('One or more devices are locked by another pending return request.');
                }
            }

            app(DeviceHierarchyAssignmentService::class)->returnFromTeamLeaderToRegionalManager(
                $return->fromTeamLeader,
                $ids,
                (int) $recipient->id,
            );

            $return->update([
                'status' => TeamLeaderDeviceReturn::STATUS_APPROVED,
                'recipient_note' => $note,
                'decided_at' => now(),
                'decided_by' => $recipient->id,
            ]);
        });

        $requester = User::find($return->from_team_leader_id);
        if ($requester) {
            app(NotificationDispatchService::class)->returnAccepted(
                $requester,
                $recipient,
                (int) $return->id,
                'team_leader_to_regional_manager',
            );
        }
    }

    public function declineByRecipient(TeamLeaderDeviceReturn $return, User $recipient, ?string $note = null): void
    {
        if (! $return->isPending()) {
            throw new \InvalidArgumentException('Return request is not pending.');
        }
        if ((int) $return->to_regional_manager_id !== (int) $recipient->id) {
            throw new \InvalidArgumentException('Only the receiving regional manager can decline this return.');
        }

        $return->update([
            'status' => TeamLeaderDeviceReturn::STATUS_REJECTED,
            'recipient_note' => $note,
            'decided_at' => now(),
            'decided_by' => $recipient->id,
        ]);

        $requester = User::find($return->from_team_leader_id);
        if ($requester) {
            app(NotificationDispatchService::class)->returnDeclined(
                $requester,
                $recipient,
                (int) $return->id,
                'team_leader_to_regional_manager',
            );
        }
    }

    public function cancelByTeamLeader(TeamLeaderDeviceReturn $return, User $teamLeader): void
    {
        if (! $return->isPending()) {
            throw new \InvalidArgumentException('Return request is not pending.');
        }
        if ((int) $return->from_team_leader_id !== (int) $teamLeader->id) {
            throw new \InvalidArgumentException('Not your return request.');
        }

        $return->update([
            'status' => TeamLeaderDeviceReturn::STATUS_CANCELLED,
            'decided_at' => now(),
            'decided_by' => $teamLeader->id,
        ]);

        $recipient = User::find($return->to_regional_manager_id);
        if ($recipient) {
            app(NotificationDispatchService::class)->returnCancelled(
                $recipient,
                $teamLeader,
                (int) $return->id,
                'team_leader_to_regional_manager',
            );
        }
    }

    private function isProductListInAnyPendingReturnExcept(int $productListId, int $exceptReturnId): bool
    {
        return TeamLeaderDeviceReturnItem::query()
            ->where('product_list_id', $productListId)
            ->whereHas('returnRequest', function ($q) use ($exceptReturnId) {
                $q->where('status', TeamLeaderDeviceReturn::STATUS_PENDING)
                    ->where('id', '!=', $exceptReturnId);
            })
            ->exists();
    }

    private function assertEligibleForReturn(?ProductListItem $item, int $productId, int $teamLeaderId): void
    {
        if (! $item || ! $item->isCatalogProduct($productId)) {
            throw new \InvalidArgumentException('One or more IMEIs do not belong to the selected product.');
        }
        if ($item->isSold()) {
            throw new \InvalidArgumentException('One or more devices are already sold.');
        }
        if ($item->agentProductListAssignment) {
            throw new \InvalidArgumentException('One or more devices are still with an agent. They must return them first.');
        }
        $tlAssign = $item->teamLeaderProductListAssignment;
        if (! $tlAssign || (int) $tlAssign->team_leader_id !== $teamLeaderId) {
            throw new \InvalidArgumentException('One or more devices are not assigned to you.');
        }
        if ($this->isProductListInAnyPendingReturn($item->id)) {
            throw new \InvalidArgumentException('One or more devices are already in a pending return request.');
        }
    }
}
