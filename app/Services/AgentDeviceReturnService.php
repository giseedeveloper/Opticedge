<?php

namespace App\Services;

use App\Models\AgentDeviceReturn;
use App\Models\AgentDeviceReturnItem;
use App\Models\ProductListItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AgentDeviceReturnService
{
    /**
     * @param  array<int, int>  $productListIds
     */
    public function createByAgent(User $agent, int $productId, array $productListIds, ?string $message = null): AgentDeviceReturn
    {
        if ($agent->role !== 'agent') {
            throw new \InvalidArgumentException('You are not an agent.');
        }

        $teamLeaderId = (int) ($agent->team_leader_id ?? 0);
        if ($teamLeaderId <= 0) {
            throw new \InvalidArgumentException('You are not assigned to a team leader.');
        }

        $ids = array_values(array_unique(array_map('intval', $productListIds)));
        if ($ids === []) {
            throw new \InvalidArgumentException('Select at least one device.');
        }

        return DB::transaction(function () use ($agent, $teamLeaderId, $productId, $ids, $message) {
            foreach ($ids as $listId) {
                $item = ProductListItem::lockForUpdate()->find($listId);
                $this->assertEligibleForReturn($item, $productId, (int) $agent->id);
            }

            $return = AgentDeviceReturn::create([
                'from_agent_id' => $agent->id,
                'to_team_leader_id' => $teamLeaderId,
                'status' => AgentDeviceReturn::STATUS_PENDING,
                'message' => $message,
            ]);

            foreach ($ids as $listId) {
                AgentDeviceReturnItem::create([
                    'agent_device_return_id' => $return->id,
                    'product_list_id' => $listId,
                ]);
            }

            $loaded = $return->load('items');
            $recipient = User::find($teamLeaderId);
            if ($recipient) {
                app(NotificationDispatchService::class)->returnIncoming(
                    $recipient,
                    $agent,
                    (int) $loaded->id,
                    $loaded->items->count(),
                    'agent_to_team_leader',
                );
            }

            return $loaded;
        });
    }

    public function isProductListInAnyPendingReturn(int $productListId): bool
    {
        return AgentDeviceReturnItem::query()
            ->where('product_list_id', $productListId)
            ->whereHas('returnRequest', fn ($q) => $q->where('status', AgentDeviceReturn::STATUS_PENDING))
            ->exists();
    }

    public function acceptByRecipient(AgentDeviceReturn $return, User $recipient, ?string $note = null): void
    {
        if (! $return->isPending()) {
            throw new \InvalidArgumentException('Return request is not pending.');
        }
        if ((int) $return->to_team_leader_id !== (int) $recipient->id) {
            throw new \InvalidArgumentException('Only the receiving team leader can accept this return.');
        }

        DB::transaction(function () use ($return, $recipient, $note) {
            $return->load(['items', 'fromAgent']);
            $ids = $return->items->pluck('product_list_id')->all();

            foreach ($ids as $listId) {
                if ($this->isProductListInAnyPendingReturnExcept($listId, $return->id)) {
                    throw new \InvalidArgumentException('One or more devices are locked by another pending return request.');
                }
            }

            app(DeviceHierarchyAssignmentService::class)->returnFromAgentToTeamLeader(
                $return->fromAgent,
                $ids
            );

            $return->update([
                'status' => AgentDeviceReturn::STATUS_APPROVED,
                'recipient_note' => $note,
                'decided_at' => now(),
                'decided_by' => $recipient->id,
            ]);
        });

        $requester = User::find($return->from_agent_id);
        if ($requester) {
            app(NotificationDispatchService::class)->returnAccepted(
                $requester,
                $recipient,
                (int) $return->id,
                'agent_to_team_leader',
            );
        }
    }

    public function declineByRecipient(AgentDeviceReturn $return, User $recipient, ?string $note = null): void
    {
        if (! $return->isPending()) {
            throw new \InvalidArgumentException('Return request is not pending.');
        }
        if ((int) $return->to_team_leader_id !== (int) $recipient->id) {
            throw new \InvalidArgumentException('Only the receiving team leader can decline this return.');
        }

        $return->update([
            'status' => AgentDeviceReturn::STATUS_REJECTED,
            'recipient_note' => $note,
            'decided_at' => now(),
            'decided_by' => $recipient->id,
        ]);

        $requester = User::find($return->from_agent_id);
        if ($requester) {
            app(NotificationDispatchService::class)->returnDeclined(
                $requester,
                $recipient,
                (int) $return->id,
                'agent_to_team_leader',
            );
        }
    }

    public function cancelByAgent(AgentDeviceReturn $return, User $agent): void
    {
        if (! $return->isPending()) {
            throw new \InvalidArgumentException('Return request is not pending.');
        }
        if ((int) $return->from_agent_id !== (int) $agent->id) {
            throw new \InvalidArgumentException('Not your return request.');
        }

        $return->update([
            'status' => AgentDeviceReturn::STATUS_CANCELLED,
            'decided_at' => now(),
            'decided_by' => $agent->id,
        ]);

        $recipient = User::find($return->to_team_leader_id);
        if ($recipient) {
            app(NotificationDispatchService::class)->returnCancelled(
                $recipient,
                $agent,
                (int) $return->id,
                'agent_to_team_leader',
            );
        }
    }

    private function isProductListInAnyPendingReturnExcept(int $productListId, int $exceptReturnId): bool
    {
        return AgentDeviceReturnItem::query()
            ->where('product_list_id', $productListId)
            ->whereHas('returnRequest', function ($q) use ($exceptReturnId) {
                $q->where('status', AgentDeviceReturn::STATUS_PENDING)
                    ->where('id', '!=', $exceptReturnId);
            })
            ->exists();
    }

    private function assertEligibleForReturn(?ProductListItem $item, int $productId, int $agentId): void
    {
        if (! $item || ! $item->isCatalogProduct($productId)) {
            throw new \InvalidArgumentException('One or more IMEIs do not belong to the selected product.');
        }
        if ($item->isSold()) {
            throw new \InvalidArgumentException('One or more devices are already sold.');
        }
        $assign = $item->agentProductListAssignment;
        if (! $assign || (int) $assign->agent_id !== $agentId) {
            throw new \InvalidArgumentException('One or more devices are not assigned to you.');
        }
        if ($this->isProductListInAnyPendingReturn($item->id)) {
            throw new \InvalidArgumentException('One or more devices are already in a pending return request.');
        }
    }
}
