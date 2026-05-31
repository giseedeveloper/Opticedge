<?php

namespace App\Support;

use App\Models\Purchase;

class PurchaseInvoiceNumber
{
    /**
     * Vendor prefix: uppercase letters and digits from distributor name (no spaces/symbols).
     */
    public static function vendorPrefix(?string $distributorName): string
    {
        $s = trim((string) $distributorName);
        if ($s === '') {
            return 'UNKNOWN';
        }
        $s = preg_replace('/[^a-zA-Z0-9]/', '', $s);
        $s = strtoupper($s);

        return $s !== '' ? $s : 'UNKNOWN';
    }

    /**
     * Base invoice: {VendorPrefix}-{6 random chars}, e.g. WILLY-PSUT&8
     */
    public static function baseName(?string $distributorName, int $maxLength = 255): string
    {
        $suffix = '-' . self::randomSuffix(6);
        $maxPrefixLen = max(1, $maxLength - strlen($suffix));
        $prefix = self::vendorPrefix($distributorName);
        if (strlen($prefix) > $maxPrefixLen) {
            $prefix = substr($prefix, 0, $maxPrefixLen);
        }

        return $prefix . $suffix;
    }

    /**
     * Unique invoice name; regenerates random suffix if collision.
     */
    public static function unique(?string $distributorName): string
    {
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $name = self::baseName($distributorName);
            if (! Purchase::where('name', $name)->exists()) {
                return $name;
            }
        }

        $prefix = self::vendorPrefix($distributorName);
        do {
            $name = $prefix . '-' . self::randomSuffix(8);
        } while (Purchase::where('name', $name)->exists());

        return $name;
    }

    private static function randomSuffix(int $length): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789&';
        $max = strlen($chars) - 1;
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, $max)];
        }

        return $result;
    }
}
