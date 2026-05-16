<?php

namespace App\Support;

use App\Models\User;

class TeamLeaderRoutes
{
    public static function isTeamLeader(?User $user): bool
    {
        return $user && $user->role === 'teamleader';
    }

    public static function ordersIndex(?User $user = null): string
    {
        $user = $user ?? auth()->user();

        return self::isTeamLeader($user) ? 'team-leader.orders' : 'orders.index';
    }

    public static function ordersIndexUrl(?User $user = null): string
    {
        return route(self::ordersIndex($user), absolute: true);
    }

    public static function cartIndex(?User $user = null): string
    {
        $user = $user ?? auth()->user();

        return self::isTeamLeader($user) ? 'team-leader.cart' : 'cart.index';
    }

    public static function addressesIndex(?User $user = null): string
    {
        $user = $user ?? auth()->user();

        return self::isTeamLeader($user) ? 'team-leader.addresses.index' : 'addresses.index';
    }

    public static function addressesCreate(?User $user = null): string
    {
        $user = $user ?? auth()->user();

        return self::isTeamLeader($user) ? 'team-leader.addresses.create' : 'addresses.create';
    }

    public static function addressesEdit(?User $user = null): string
    {
        $user = $user ?? auth()->user();

        return self::isTeamLeader($user) ? 'team-leader.addresses.edit' : 'addresses.edit';
    }
}
