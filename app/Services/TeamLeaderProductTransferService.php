<?php

namespace App\Services;

use App\Models\ProductListItem;
use App\Models\TeamLeaderProductListAssignment;
use App\Models\TeamLeaderProductTransfer;
use App\Models\TeamLeaderProductTransferItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TeamLeaderProductTransferService
{
    /**
     * @param  array<int, int>  $productListIds
     */
    public function createByRegionalManager(User $regionalManager, User $teamLeader, int $productId, array $productListIds, ?string $message = null): TeamLeaderProductTransfer
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
        if ($ids === []) {
            throw new \InvalidArgumentException('Select at least one device.');
        }

        return DB::transaction(function () use ($regionalManager, $teamLeader, $productId, $ids, $message) {
            foreach ($ids as $listId) {
                $item = ProductListItem::lockForUpdate()->find($listId);
                $this->assertEligibleForTransfer($item, $productId, (int) $regionalManager->id);
            }

            $transfer = TeamLeaderProductTransfer::create([
                'from_regional_manager_id' => $regionalManager->id,
                'to_team_leader_id' => $teamLeader->id,
                'status' => TeamLeaderProductTransfer::STATUS_PENDING,
                'message' => $message,
            ]);

            foreach ($ids as $listId) {
                TeamLeaderProductTransferItem::create([
                    'team_leader_product_transfer_id' => $transfer->id,
                    'product_list_id' => $listId,
                ]);
            }

            return $transfer->load('items');
        });
    }

    public function isProductListInAnyPendingTransfer(int $productListId): bool
    {
        return TeamLeaderProductTransferItem::query()
            ->where('product_list_id', $productListId)
            ->whereHas('transfer', fn ($q) => $q->where('status', TeamLeaderProductTransfer::STATUS_PENDING))
            ->exists();
    }

    public function acceptByRecipient(TeamLeaderProductTransfer $transfer, User $recipient, ?string $note = null): void
    {
        if (! $transfer->isPending()) {
            throw new \InvalidArgumentException('Transfer is not pending.');
        }
        if ((int) $transfer->to_team_leader_id !== (int) $recipient->id) {
            throw new \InvalidArgumentException('Only the receiving team leader can accept this transfer.');
        }

        $this->completeTransfer($transfer, $recipient, $note);
    }

    public function declineByRecipient(TeamLeaderProductTransfer $transfer, User $recipient, ?string $note = null): void
    {
        if (! $transfer->isPending()) {
            throw new \InvalidArgumentException('Transfer is not pending.');
        }
        if ((int) $transfer->to_team_leader_id !== (int) $recipient->id) {
            throw new \InvalidArgumentException('Only the receiving team leader can decline this transfer.');
        }

        $transfer->update([
            'status' => TeamLeaderProductTransfer::STATUS_REJECTED,
            'admin_note' => $note,
            'decided_at' => now(),
            'decided_by' => $recipient->id,
        ]);
    }

    public function cancelByRegionalManager(TeamLeaderProductTransfer $transfer, User $regionalManager): void
    {
        if (! $transfer->isPending()) {
            throw new \InvalidArgumentException('Transfer is not pending.');
        }
        if ((int) $transfer->from_regional_manager_id !== (int) $regionalManager->id) {
            throw new \InvalidArgumentException('Not your transfer request.');
        }

        $transfer->update([
            'status' => TeamLeaderProductTransfer::STATUS_CANCELLED,
            'decided_at' => now(),
            'decided_by' => $regionalManager->id,
        ]);
    }

    private function completeTransfer(TeamLeaderProductTransfer $transfer, User $decidedBy, ?string $note = null): void
    {
        DB::transaction(function () use ($transfer, $decidedBy, $note) {
            $transfer->load('items');
            $fromRmId = (int) $transfer->from_regional_manager_id;
            $toTlId = (int) $transfer->to_team_leader_id;

            foreach ($transfer->items as $ti) {
                $item = ProductListItem::lockForUpdate()->find($ti->product_list_id);
                if (! $item || $item->isSold()) {
                    throw new \InvalidArgumentException('A device was sold or removed; cannot complete transfer.');
                }
                if ($item->teamLeaderProductListAssignment) {
                    throw new \InvalidArgumentException('One or more devices are already with a team leader.');
                }
                if ($item->agentProductListAssignment) {
                    throw new \InvalidArgumentException('One or more devices are already with an agent.');
                }
                $rmAssign = $item->regionalManagerProductListAssignment;
                if (! $rmAssign || (int) $rmAssign->regional_manager_id !== $fromRmId) {
                    throw new \InvalidArgumentException('One or more devices are no longer held by the regional manager.');
                }
                if ($this->isProductListInAnyPendingTransferExcept($item->id, $transfer->id)) {
                    throw new \InvalidArgumentException('One or more devices are locked by another pending request.');
                }
            }

            foreach ($transfer->items as $ti) {
                TeamLeaderProductListAssignment::create([
                    'team_leader_id' => $toTlId,
                    'product_list_id' => $ti->product_list_id,
                ]);
            }

            $transfer->update([
                'status' => TeamLeaderProductTransfer::STATUS_APPROVED,
                'admin_note' => $note,
                'decided_at' => now(),
                'decided_by' => $decidedBy->id,
            ]);
        });
    }

    private function isProductListInAnyPendingTransferExcept(int $productListId, int $exceptTransferId): bool
    {
        return TeamLeaderProductTransferItem::query()
            ->where('product_list_id', $productListId)
            ->whereHas('transfer', function ($q) use ($exceptTransferId) {
                $q->where('status', TeamLeaderProductTransfer::STATUS_PENDING)
                    ->where('id', '!=', $exceptTransferId);
            })
            ->exists();
    }

    private function assertEligibleForTransfer(?ProductListItem $item, int $productId, int $regionalManagerId): void
    {
        if (! $item || ! $item->isCatalogProduct($productId)) {
            throw new \InvalidArgumentException('One or more IMEIs do not belong to the selected product.');
        }
        if ($item->isSold()) {
            throw new \InvalidArgumentException('One or more devices are already sold.');
        }
        if ($item->teamLeaderProductListAssignment) {
            throw new \InvalidArgumentException('One or more devices are already with a team leader.');
        }
        if ($item->agentProductListAssignment) {
            throw new \InvalidArgumentException('One or more devices are already with an agent.');
        }
        $rmAssign = $item->regionalManagerProductListAssignment;
        if (! $rmAssign || (int) $rmAssign->regional_manager_id !== $regionalManagerId) {
            throw new \InvalidArgumentException('One or more devices were not given to you by admin.');
        }
        if ($this->isProductListInAnyPendingTransfer($item->id)) {
            throw new \InvalidArgumentException('One or more devices are already in a pending transfer request.');
        }
    }
}
