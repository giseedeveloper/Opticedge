<?php

namespace App\Services;

use App\Models\ProductListItem;
use App\Models\RegionalManagerProductListAssignment;
use App\Models\RegionalManagerProductTransfer;
use App\Models\RegionalManagerProductTransferItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegionalManagerProductTransferService
{
    /**
     * @param  array<int, int>  $productListIds
     */
    public function createByAdmin(User $admin, User $regionalManager, int $productId, array $productListIds, ?string $message = null): RegionalManagerProductTransfer
    {
        if ($regionalManager->role !== 'regional_manager') {
            throw new \InvalidArgumentException('Selected user is not a regional manager.');
        }

        $ids = array_values(array_unique(array_map('intval', $productListIds)));
        if ($ids === []) {
            throw new \InvalidArgumentException('Select at least one device.');
        }

        return DB::transaction(function () use ($admin, $regionalManager, $productId, $ids, $message) {
            foreach ($ids as $listId) {
                $item = ProductListItem::lockForUpdate()->find($listId);
                $this->assertEligibleForTransfer($item, $productId);
            }

            $transfer = RegionalManagerProductTransfer::create([
                'created_by_admin_id' => $admin->id,
                'to_regional_manager_id' => $regionalManager->id,
                'status' => RegionalManagerProductTransfer::STATUS_PENDING,
                'message' => $message,
            ]);

            foreach ($ids as $listId) {
                RegionalManagerProductTransferItem::create([
                    'regional_manager_product_transfer_id' => $transfer->id,
                    'product_list_id' => $listId,
                ]);
            }

            $loaded = $transfer->load('items');
            app(NotificationDispatchService::class)->transferIncoming(
                $regionalManager,
                $admin,
                (int) $loaded->id,
                $loaded->items->count(),
                'admin_to_regional_manager',
            );

            return $loaded;
        });
    }

    public function isProductListInAnyPendingTransfer(int $productListId): bool
    {
        return RegionalManagerProductTransferItem::query()
            ->where('product_list_id', $productListId)
            ->whereHas('transfer', fn ($q) => $q->where('status', RegionalManagerProductTransfer::STATUS_PENDING))
            ->exists();
    }

    public function acceptByRecipient(RegionalManagerProductTransfer $transfer, User $recipient, ?string $note = null): void
    {
        if (! $transfer->isPending()) {
            throw new \InvalidArgumentException('Transfer is not pending.');
        }
        if ((int) $transfer->to_regional_manager_id !== (int) $recipient->id) {
            throw new \InvalidArgumentException('Only the receiving regional manager can accept this transfer.');
        }

        $this->completeTransfer($transfer, $recipient, $note);

        $initiator = User::find($transfer->created_by_admin_id);
        if ($initiator) {
            app(NotificationDispatchService::class)->transferAccepted(
                $initiator,
                $recipient,
                (int) $transfer->id,
                'admin_to_regional_manager',
            );
        }
    }

    public function declineByRecipient(RegionalManagerProductTransfer $transfer, User $recipient, ?string $note = null): void
    {
        if (! $transfer->isPending()) {
            throw new \InvalidArgumentException('Transfer is not pending.');
        }
        if ((int) $transfer->to_regional_manager_id !== (int) $recipient->id) {
            throw new \InvalidArgumentException('Only the receiving regional manager can decline this transfer.');
        }

        $transfer->update([
            'status' => RegionalManagerProductTransfer::STATUS_REJECTED,
            'admin_note' => $note,
            'decided_at' => now(),
            'decided_by' => $recipient->id,
        ]);

        $initiator = User::find($transfer->created_by_admin_id);
        if ($initiator) {
            app(NotificationDispatchService::class)->transferDeclined(
                $initiator,
                $recipient,
                (int) $transfer->id,
                'admin_to_regional_manager',
            );
        }
    }

    public function cancelByAdmin(RegionalManagerProductTransfer $transfer, User $admin): void
    {
        if (! $transfer->isPending()) {
            throw new \InvalidArgumentException('Transfer is not pending.');
        }
        if ((int) $transfer->created_by_admin_id !== (int) $admin->id) {
            throw new \InvalidArgumentException('Not your transfer request.');
        }

        $transfer->update([
            'status' => RegionalManagerProductTransfer::STATUS_CANCELLED,
            'decided_at' => now(),
            'decided_by' => $admin->id,
        ]);

        $recipient = User::find($transfer->to_regional_manager_id);
        if ($recipient) {
            app(NotificationDispatchService::class)->transferCancelled(
                $recipient,
                $admin,
                (int) $transfer->id,
                'admin_to_regional_manager',
            );
        }
    }

    private function completeTransfer(RegionalManagerProductTransfer $transfer, User $decidedBy, ?string $note = null): void
    {
        DB::transaction(function () use ($transfer, $decidedBy, $note) {
            $transfer->load('items');
            $toRmId = (int) $transfer->to_regional_manager_id;

            foreach ($transfer->items as $ti) {
                $item = ProductListItem::lockForUpdate()->find($ti->product_list_id);
                if (! $item || $item->isSold()) {
                    throw new \InvalidArgumentException('A device was sold or removed; cannot complete transfer.');
                }
                if ($item->regionalManagerProductListAssignment || $item->teamLeaderProductListAssignment || $item->agentProductListAssignment) {
                    throw new \InvalidArgumentException('One or more devices are already assigned in the hierarchy.');
                }
                if ($this->isProductListInAnyPendingTransferExcept($item->id, $transfer->id)) {
                    throw new \InvalidArgumentException('One or more devices are locked by another pending request.');
                }
            }

            foreach ($transfer->items as $ti) {
                RegionalManagerProductListAssignment::create([
                    'regional_manager_id' => $toRmId,
                    'product_list_id' => $ti->product_list_id,
                ]);
            }

            $transfer->update([
                'status' => RegionalManagerProductTransfer::STATUS_APPROVED,
                'admin_note' => $note,
                'decided_at' => now(),
                'decided_by' => $decidedBy->id,
            ]);
        });
    }

    private function isProductListInAnyPendingTransferExcept(int $productListId, int $exceptTransferId): bool
    {
        return RegionalManagerProductTransferItem::query()
            ->where('product_list_id', $productListId)
            ->whereHas('transfer', function ($q) use ($exceptTransferId) {
                $q->where('status', RegionalManagerProductTransfer::STATUS_PENDING)
                    ->where('id', '!=', $exceptTransferId);
            })
            ->exists();
    }

    private function assertEligibleForTransfer(?ProductListItem $item, int $productId): void
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
        if ($this->isProductListInAnyPendingTransfer($item->id)) {
            throw new \InvalidArgumentException('One or more devices are already in a pending transfer request.');
        }
    }
}
