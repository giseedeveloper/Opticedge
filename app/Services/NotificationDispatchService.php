<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Support\NotificationRoutes;
use App\Support\NotificationType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationDispatchService
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $channels
     */
    public function notifyUser(User $user, string $type, string $title, string $body, array $payload = [], array $channels = ['database', 'fcm']): void
    {
        if (! filled($payload['route'] ?? null)) {
            $payload['route'] = NotificationRoutes::forUser($user, $type, $payload);
        }

        if (! filled($payload['web_url'] ?? null)) {
            $payload['web_url'] = NotificationRoutes::webForUser($user, $type, $payload);
        }

        Log::info('Notification dispatched to user', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_name' => $user->name,
            'user_role' => $user->role,
            'notification_type' => $type,
            'channels' => $channels,
            'title' => $title,
        ]);

        $user->notify(new AppNotification($type, $title, $body, $payload, $channels));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function notifyUsers(iterable $users, string $type, string $title, string $body, array $payload = [], array $channels = ['database', 'fcm']): void
    {
        foreach ($users as $user) {
            if ($user instanceof User) {
                $this->notifyUser($user, $type, $title, $body, $payload, $channels);
            }
        }
    }

    public function notifyTenantAdmins(?int $tenantId, string $type, string $title, string $body, array $payload = [], array $channels = ['database', 'fcm']): void
    {
        if (! $tenantId) {
            return;
        }

        $admins = User::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('role', ['admin', 'subadmin'])
            ->where('status', 'active')
            ->get();

        $this->notifyUsers($admins, $type, $title, $body, $payload, $channels);
    }

    public function afterCommit(callable $callback): void
    {
        if (DB::transactionLevel() > 0) {
            DB::afterCommit($callback);

            return;
        }

        $callback();
    }

    public function orderCreated(Order $order): void
    {
        $order->loadMissing('user');
        $buyer = $order->user;
        $orderLabel = '#'.$order->id;

        $this->afterCommit(function () use ($order, $buyer, $orderLabel) {
            $this->notifyTenantAdmins(
                $order->tenant_id,
                NotificationType::ORDER_CREATED,
                'New shop order',
                "Order {$orderLabel} was placed".($buyer ? ' by '.$buyer->name : '.'),
                [
                    'entity_type' => 'order',
                    'entity_id' => $order->id,
                ],
            );

            if ($buyer) {
                $this->notifyUser(
                    $buyer,
                    NotificationType::ORDER_CREATED,
                    'Order received',
                    "Your order {$orderLabel} has been placed.",
                    [
                        'entity_type' => 'order',
                        'entity_id' => $order->id,
                    ],
                    ['database', 'fcm'],
                );
            }
        });
    }

    public function orderPaymentSuccess(Order $order): void
    {
        $order->loadMissing('user');
        $buyer = $order->user;
        $orderLabel = '#'.$order->id;

        $this->afterCommit(function () use ($order, $buyer, $orderLabel) {
            if ($buyer) {
                $this->notifyUser(
                    $buyer,
                    NotificationType::ORDER_PAYMENT_SUCCESS,
                    'Payment confirmed',
                    "Payment for order {$orderLabel} was successful.",
                    [
                        'entity_type' => 'order',
                        'entity_id' => $order->id,
                    ],
                );
            }

            $this->notifyTenantAdmins(
                $order->tenant_id,
                NotificationType::ORDER_PAYMENT_SUCCESS,
                'Order paid',
                "Order {$orderLabel} payment was confirmed.",
                [
                    'entity_type' => 'order',
                    'entity_id' => $order->id,
                ],
            );
        });
    }

    public function orderPaymentFailed(Order $order, ?string $reason = null): void
    {
        $order->loadMissing('user');
        $buyer = $order->user;
        $orderLabel = '#'.$order->id;
        $detail = $reason ? " {$reason}" : '';

        $this->afterCommit(function () use ($order, $buyer, $orderLabel, $detail) {
            if ($buyer) {
                $this->notifyUser(
                    $buyer,
                    NotificationType::ORDER_PAYMENT_FAILED,
                    'Payment failed',
                    "Payment for order {$orderLabel} failed.{$detail}",
                    [
                        'entity_type' => 'order',
                        'entity_id' => $order->id,
                    ],
                );
            }
        });
    }

    public function orderStatusChanged(Order $order, string $oldStatus, string $newStatus): void
    {
        if ($oldStatus === $newStatus) {
            return;
        }

        $order->loadMissing('user');
        $buyer = $order->user;
        $orderLabel = '#'.$order->id;
        $statusLabel = ucfirst($newStatus);

        $this->afterCommit(function () use ($order, $buyer, $orderLabel, $statusLabel) {
            if ($buyer) {
                $this->notifyUser(
                    $buyer,
                    NotificationType::ORDER_STATUS_CHANGED,
                    'Order update',
                    "Order {$orderLabel} is now: {$statusLabel}.",
                    [
                        'entity_type' => 'order',
                        'entity_id' => $order->id,
                        'meta' => ['status' => $statusLabel],
                    ],
                );
            }
        });
    }

    public function registrationPending(User $user, string $roleLabel): void
    {
        $this->afterCommit(function () use ($user, $roleLabel) {
            $this->notifyTenantAdmins(
                $user->tenant_id,
                NotificationType::USER_REGISTRATION_PENDING,
                'New registration',
                "{$user->name} registered as {$roleLabel} and needs approval.",
                [
                    'entity_type' => 'user',
                    'entity_id' => $user->id,
                ],
            );

            if ($user->role === 'dealer') {
                $this->notifyUser(
                    $user,
                    NotificationType::USER_REGISTRATION_PENDING,
                    'Registration submitted',
                    'Your dealer registration is pending admin approval.',
                    [
                        'entity_type' => 'user',
                        'entity_id' => $user->id,
                    ],
                    ['database'],
                );
            }
        });
    }

    public function dealerApproved(User $user): void
    {
        $this->afterCommit(function () use ($user) {
            $this->notifyUser(
                $user,
                NotificationType::USER_DEALER_APPROVED,
                'Account approved',
                'Your dealer account has been approved. You can now sign in and shop.',
                [
                    'entity_type' => 'user',
                    'entity_id' => $user->id,
                ],
                ['database', 'fcm', 'mail'],
            );
        });
    }

    public function dealerRejected(User $user): void
    {
        $this->afterCommit(function () use ($user) {
            $this->notifyUser(
                $user,
                NotificationType::USER_DEALER_REJECTED,
                'Registration declined',
                'Your dealer registration was not approved. Contact support for help.',
                [
                    'entity_type' => 'user',
                    'entity_id' => $user->id,
                ],
                ['database', 'fcm', 'mail'],
            );
        });
    }

    public function userActivated(User $user): void
    {
        $this->afterCommit(function () use ($user) {
            $this->notifyUser(
                $user,
                NotificationType::USER_ACTIVATED,
                'Account activated',
                'Your account is now active. You can sign in.',
                [
                    'entity_type' => 'user',
                    'entity_id' => $user->id,
                ],
                ['database', 'fcm', 'mail'],
            );
        });
    }

    public function userDeactivated(User $user): void
    {
        $this->afterCommit(function () use ($user) {
            $this->notifyUser(
                $user,
                NotificationType::USER_DEACTIVATED,
                'Account deactivated',
                'Your account has been deactivated. Contact your administrator.',
                [
                    'entity_type' => 'user',
                    'entity_id' => $user->id,
                ],
                ['database', 'fcm'],
            );
        });
    }

    public function transferIncoming(User $recipient, User $sender, int $transferId, int $deviceCount, string $scope): void
    {
        $this->afterCommit(function () use ($recipient, $sender, $transferId, $deviceCount, $scope) {
            $countLabel = $deviceCount === 1 ? '1 device' : "{$deviceCount} devices";
            $this->notifyUser(
                $recipient,
                NotificationType::TRANSFER_INCOMING,
                'Incoming transfer',
                "{$sender->name} sent {$countLabel} ({$scope}).",
                [
                    'entity_type' => 'transfer',
                    'entity_id' => $transferId,
                    'meta' => ['scope' => $scope],
                ],
            );
        });
    }

    public function transferAccepted(User $initiator, User $recipient, int $transferId, string $scope): void
    {
        $this->afterCommit(function () use ($initiator, $recipient, $transferId, $scope) {
            $this->notifyUser(
                $initiator,
                NotificationType::TRANSFER_ACCEPTED,
                'Transfer accepted',
                "{$recipient->name} accepted your {$scope} transfer request.",
                [
                    'entity_type' => 'transfer',
                    'entity_id' => $transferId,
                    'meta' => ['scope' => $scope],
                ],
            );
        });
    }

    public function transferDeclined(User $initiator, User $recipient, int $transferId, string $scope): void
    {
        $this->afterCommit(function () use ($initiator, $recipient, $transferId, $scope) {
            $this->notifyUser(
                $initiator,
                NotificationType::TRANSFER_DECLINED,
                'Transfer declined',
                "{$recipient->name} declined your {$scope} transfer request.",
                [
                    'entity_type' => 'transfer',
                    'entity_id' => $transferId,
                    'meta' => ['scope' => $scope],
                ],
            );
        });
    }

    public function transferCancelled(User $recipient, User $initiator, int $transferId, string $scope): void
    {
        $this->afterCommit(function () use ($recipient, $initiator, $transferId, $scope) {
            $this->notifyUser(
                $recipient,
                NotificationType::TRANSFER_CANCELLED,
                'Transfer cancelled',
                "{$initiator->name} cancelled the {$scope} transfer request.",
                [
                    'entity_type' => 'transfer',
                    'entity_id' => $transferId,
                    'meta' => ['scope' => $scope],
                ],
            );
        });
    }

    public function returnIncoming(User $recipient, User $requester, int $returnId, int $deviceCount, string $scope): void
    {
        $this->afterCommit(function () use ($recipient, $requester, $returnId, $deviceCount, $scope) {
            $countLabel = $deviceCount === 1 ? '1 device' : "{$deviceCount} devices";
            $this->notifyUser(
                $recipient,
                NotificationType::RETURN_INCOMING,
                'Return request',
                "{$requester->name} requested to return {$countLabel} ({$scope}).",
                [
                    'entity_type' => 'return',
                    'entity_id' => $returnId,
                    'meta' => ['scope' => $scope],
                ],
            );
        });
    }

    public function returnAccepted(User $requester, User $recipient, int $returnId, string $scope): void
    {
        $this->afterCommit(function () use ($requester, $recipient, $returnId, $scope) {
            $this->notifyUser(
                $requester,
                NotificationType::RETURN_ACCEPTED,
                'Return accepted',
                "{$recipient->name} accepted your {$scope} return request.",
                [
                    'entity_type' => 'return',
                    'entity_id' => $returnId,
                    'meta' => ['scope' => $scope],
                ],
            );
        });
    }

    public function returnDeclined(User $requester, User $recipient, int $returnId, string $scope): void
    {
        $this->afterCommit(function () use ($requester, $recipient, $returnId, $scope) {
            $this->notifyUser(
                $requester,
                NotificationType::RETURN_DECLINED,
                'Return declined',
                "{$recipient->name} declined your {$scope} return request.",
                [
                    'entity_type' => 'return',
                    'entity_id' => $returnId,
                    'meta' => ['scope' => $scope],
                ],
            );
        });
    }

    public function returnCancelled(User $recipient, User $requester, int $returnId, string $scope): void
    {
        $this->afterCommit(function () use ($recipient, $requester, $returnId, $scope) {
            $this->notifyUser(
                $recipient,
                NotificationType::RETURN_CANCELLED,
                'Return cancelled',
                "{$requester->name} cancelled the {$scope} return request.",
                [
                    'entity_type' => 'return',
                    'entity_id' => $returnId,
                    'meta' => ['scope' => $scope],
                ],
            );
        });
    }

    public function devicesAssigned(User $agent, User $assigner, int $count): void
    {
        $this->afterCommit(function () use ($agent, $assigner, $count) {
            $label = $count === 1 ? '1 device was' : "{$count} devices were";
            $this->notifyUser(
                $agent,
                NotificationType::DEVICES_ASSIGNED,
                'Devices assigned',
                "{$assigner->name} assigned {$label} to you.",
                [
                    'entity_type' => 'assignment',
                    'entity_id' => $agent->id,
                    'meta' => ['count' => $count],
                ],
            );
        });
    }

    /**
     * Notify all tenant admins for RM→admin returns (no single recipient user on create).
     */
    public function returnIncomingAdmins(?int $tenantId, User $requester, int $returnId, int $deviceCount): void
    {
        $this->afterCommit(function () use ($tenantId, $requester, $returnId, $deviceCount) {
            $countLabel = $deviceCount === 1 ? '1 device' : "{$deviceCount} devices";
            $this->notifyTenantAdmins(
                $tenantId,
                NotificationType::RETURN_INCOMING,
                'Return request',
                "{$requester->name} requested to return {$countLabel} to admin.",
                [
                    'entity_type' => 'return',
                    'entity_id' => $returnId,
                    'meta' => ['scope' => 'regional_manager_to_admin'],
                ],
            );
        });
    }

    /**
     * @return Collection<int, User>
     */
    public function tenantAdmins(?int $tenantId): Collection
    {
        if (! $tenantId) {
            return collect();
        }

        return User::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('role', ['admin', 'subadmin'])
            ->where('status', 'active')
            ->get();
    }
}
