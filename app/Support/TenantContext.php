<?php

namespace App\Support;

class TenantContext
{
    private static ?int $tenantId = null;

    private static bool $bypass = false;

    public static function set(?int $tenantId): void
    {
        self::$tenantId = $tenantId;
    }

    public static function id(): ?int
    {
        return self::$tenantId;
    }

    public static function bypass(bool $bypass = true): void
    {
        self::$bypass = $bypass;
    }

    public static function shouldBypass(): bool
    {
        return self::$bypass;
    }

    public static function clear(): void
    {
        self::$tenantId = null;
        self::$bypass = false;
    }
}
