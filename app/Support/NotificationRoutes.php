<?php

namespace App\Support;

use App\Models\User;

final class NotificationRoutes
{
    /**
     * Resolve a Flutter/mobile named route for the recipient role.
     *
     * @param  array<string, mixed>  $context
     */
    public static function forUser(User $user, string $type, array $context = []): ?string
    {
        $role = $user->role;

        if (str_starts_with($type, 'order.')) {
            return in_array($role, ['admin', 'subadmin'], true)
                ? '/admin/orders'
                : (in_array($role, ['customer', 'dealer', 'teamleader', 'regional_manager'], true)
                    ? '/shop/orders'
                    : null);
        }

        if (str_starts_with($type, 'user.')) {
            return match ($role) {
                'dealer' => '/shop/dashboard',
                'agent' => '/agent/dashboard',
                'admin', 'subadmin' => '/admin/all-users',
                default => null,
            };
        }

        if (str_starts_with($type, 'transfer.')) {
            return match ($role) {
                'admin', 'subadmin' => '/admin/stock/device-transfers',
                'regional_manager' => '/regional-manager/transfers',
                'teamleader' => '/team-leader/transfers',
                'agent' => '/agent/transfers',
                default => null,
            };
        }

        if (str_starts_with($type, 'return.')) {
            return match ($role) {
                'admin', 'subadmin' => '/admin/stock/device-returns',
                'regional_manager' => '/regional-manager/return-requests',
                'teamleader' => '/team-leader/return-requests',
                'agent' => '/agent/return-requests',
                default => null,
            };
        }

        if ($type === NotificationType::DEVICES_ASSIGNED) {
            return $role === 'agent' ? '/agent/dashboard' : null;
        }

        if (str_starts_with($type, 'guest.')) {
            return $role === 'guest' ? '/guest/requests' : null;
        }

        if (str_starts_with($type, 'contract_termination.')) {
            return match ($role) {
                'admin', 'subadmin' => '/admin/contract-terminations',
                'regional_manager' => '/regional-manager/contract-termination',
                'teamleader' => '/team-leader/contract-termination',
                'agent' => '/agent/contract-termination',
                'guest' => '/guest/requests',
                default => null,
            };
        }

        return null;
    }

    /**
     * Resolve a web URL for the recipient role (Blade portals).
     *
     * @param  array<string, mixed>  $context
     */
    public static function webForUser(User $user, string $type, array $context = []): ?string
    {
        $role = $user->role;

        if (str_starts_with($type, 'order.')) {
            if (in_array($role, ['admin', 'subadmin'], true)) {
                return self::safeRoute('admin.orders.index');
            }
            if (in_array($role, ['customer', 'dealer'], true)) {
                return self::safeRoute('orders.index');
            }
            if ($role === 'teamleader') {
                return self::safeRoute('team-leader.orders');
            }
            if ($role === 'regional_manager') {
                return self::safeRoute('orders.index');
            }

            return null;
        }

        if (str_starts_with($type, 'user.')) {
            return match ($role) {
                'admin', 'subadmin' => self::safeRoute('admin.dealers.index'),
                'dealer' => self::safeRoute('shop'),
                'agent' => self::safeRoute('agent.dashboard'),
                default => null,
            };
        }

        if (str_starts_with($type, 'transfer.')) {
            return match ($role) {
                'admin', 'subadmin' => self::safeRoute('admin.stock.device-transfers'),
                'regional_manager' => self::safeRoute('regional-manager.transfers.index'),
                'teamleader' => self::safeRoute('team-leader.transfers.index'),
                'agent' => self::safeRoute('agent.transfers.index'),
                default => null,
            };
        }

        if (str_starts_with($type, 'return.')) {
            return match ($role) {
                'admin', 'subadmin' => self::safeRoute('admin.stock.device-returns'),
                'regional_manager' => self::safeRoute('regional-manager.return-requests.incoming'),
                'teamleader' => self::safeRoute('team-leader.return-requests.incoming'),
                'agent' => self::safeRoute('agent.return-requests'),
                default => null,
            };
        }

        if ($type === NotificationType::DEVICES_ASSIGNED) {
            return $role === 'agent' ? self::safeRoute('agent.dashboard') : null;
        }

        if (str_starts_with($type, 'guest.')) {
            return $role === 'guest' ? self::safeRoute('guest.waiting') : null;
        }

        if (str_starts_with($type, 'contract_termination.')) {
            return match ($role) {
                'admin', 'subadmin' => self::safeRoute('admin.contract-terminations.index'),
                default => null,
            };
        }

        return null;
    }

    private static function safeRoute(string $name, mixed ...$params): ?string
    {
        try {
            return route($name, $params);
        } catch (\Throwable) {
            return null;
        }
    }
}
