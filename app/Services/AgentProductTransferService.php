<?php

namespace App\Services;

use App\Models\AgentAssignment;
use App\Models\AgentProductListAssignment;
use App\Models\AgentProductTransfer;
use App\Models\AgentProductTransferItem;
use App\Models\ProductListItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AgentProductTransferService
{
    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    public function productListIdsInPendingOutgoingTransfer(int $agentId)
    {
        return AgentProductTransferItem::query()
            ->whereHas('transfer', function ($q) use ($agentId) {
                $q->where('status', AgentProductTransfer::STATUS_PENDING)
                    ->where('from_agent_id', $agentId);
            })
            ->pluck('product_list_id');
    }

    public function isProductListLockedForSale(int $productListId, int $agentId): bool
    {
        return AgentProductTransferItem::query()
            ->where('product_list_id', $productListId)
            ->whereHas('transfer', function ($q) use ($agentId) {
                $q->where('status', AgentProductTransfer::STATUS_PENDING)
                    ->where('from_agent_id', $agentId);
            })
            ->exists();
    }

    public function isProductListInAnyPendingTransfer(int $productListId): bool
    {
        return AgentProductTransferItem::query()
            ->where('product_list_id', $productListId)
            ->whereHas('transfer', fn ($q) => $q->where('status', AgentProductTransfer::STATUS_PENDING))
            ->exists();
    }

    public function acceptByRecipient(AgentProductTransfer $transfer, User $recipient, ?string $note = null): void
    {
        if (! $transfer->isPending()) {
            throw new \InvalidArgumentException('Transfer is not pending.');
        }
        if ((int) $transfer->to_agent_id !== (int) $recipient->id) {
            throw new \InvalidArgumentException('Only the receiving agent can accept this transfer.');
        }

        $this->completeTransfer($transfer, $recipient, $note);
    }

    public function declineByRecipient(AgentProductTransfer $transfer, User $recipient, ?string $note = null): void
    {
        if (! $transfer->isPending()) {
            throw new \InvalidArgumentException('Transfer is not pending.');
        }
        if ((int) $transfer->to_agent_id !== (int) $recipient->id) {
            throw new \InvalidArgumentException('Only the receiving agent can decline this transfer.');
        }

        $transfer->update([
            'status' => AgentProductTransfer::STATUS_REJECTED,
            'admin_note' => $note,
            'decided_at' => now(),
            'decided_by' => $recipient->id,
        ]);
    }

    public function approve(AgentProductTransfer $transfer, User $admin, ?string $adminNote = null): void
    {
        if (! $transfer->isPending()) {
            throw new \InvalidArgumentException('Transfer is not pending.');
        }

        $this->completeTransfer($transfer, $admin, $adminNote);
    }

    private function completeTransfer(AgentProductTransfer $transfer, User $decidedBy, ?string $note = null): void
    {
        DB::transaction(function () use ($transfer, $decidedBy, $note) {
            $transfer->load('items');
            $fromId = (int) $transfer->from_agent_id;
            $toId = (int) $transfer->to_agent_id;

            $byProduct = [];
            foreach ($transfer->items as $ti) {
                $item = ProductListItem::lockForUpdate()->find($ti->product_list_id);
                if (! $item || $item->isSold()) {
                    throw new \InvalidArgumentException('A device was sold or removed; cannot complete transfer.');
                }
                $assign = AgentProductListAssignment::where('product_list_id', $item->id)->lockForUpdate()->first();
                if (! $assign || (int) $assign->agent_id !== $fromId) {
                    throw new \InvalidArgumentException('Assignment no longer matches this transfer.');
                }
                $pid = (int) $item->product_id;
                $byProduct[$pid] = ($byProduct[$pid] ?? 0) + 1;
            }

            foreach ($transfer->items as $ti) {
                AgentProductListAssignment::where('product_list_id', $ti->product_list_id)->update(['agent_id' => $toId]);
            }

            foreach ($byProduct as $productId => $count) {
                $this->adjustAgentAssignmentQuantity($fromId, $productId, -$count);
                $this->adjustAgentAssignmentQuantity($toId, $productId, $count);
            }

            $transfer->update([
                'status' => AgentProductTransfer::STATUS_APPROVED,
                'admin_note' => $note,
                'decided_at' => now(),
                'decided_by' => $decidedBy->id,
            ]);
        });
    }

    private function adjustAgentAssignmentQuantity(int $agentId, int $productId, int $delta): void
    {
        if ($delta === 0) {
            return;
        }

        $row = AgentAssignment::where('agent_id', $agentId)
            ->where('product_id', $productId)
            ->where('assignment_type', AgentAssignment::TYPE_IMEI)
            ->lockForUpdate()
            ->first();

        if ($delta < 0) {
            if (! $row) {
                throw new \InvalidArgumentException('Sender assignment out of sync.');
            }
            $newAssigned = (int) $row->quantity_assigned + $delta;
            $sold = (int) $row->quantity_sold;
            if ($newAssigned < $sold) {
                throw new \InvalidArgumentException('Cannot reduce assigned below sold count.');
            }
            if ($newAssigned <= 0 && $sold === 0) {
                $row->delete();
            } elseif ($newAssigned <= 0) {
                throw new \InvalidArgumentException('Cannot remove assignment while sales are recorded.');
            } else {
                $row->update(['quantity_assigned' => $newAssigned]);
            }

            return;
        }

        if ($row) {
            $row->update(['quantity_assigned' => (int) $row->quantity_assigned + $delta]);
        } else {
            AgentAssignment::create([
                'agent_id' => $agentId,
                'product_id' => $productId,
                'assignment_type' => AgentAssignment::TYPE_IMEI,
                'quantity_assigned' => $delta,
                'quantity_sold' => 0,
            ]);
        }
    }

    public function reject(AgentProductTransfer $transfer, User $admin, ?string $adminNote = null): void
    {
        if (! $transfer->isPending()) {
            throw new \InvalidArgumentException('Transfer is not pending.');
        }
        $transfer->update([
            'status' => AgentProductTransfer::STATUS_REJECTED,
            'admin_note' => $adminNote,
            'decided_at' => now(),
            'decided_by' => $admin->id,
        ]);
    }

    public function cancelOwn(AgentProductTransfer $transfer, User $agent): void
    {
        if (! $transfer->isPending()) {
            throw new \InvalidArgumentException('Transfer is not pending.');
        }
        if ((int) $transfer->from_agent_id !== (int) $agent->id) {
            throw new \InvalidArgumentException('Not your transfer.');
        }
        $transfer->update([
            'status' => AgentProductTransfer::STATUS_CANCELLED,
            'decided_at' => now(),
            'decided_by' => null,
        ]);
    }
}
