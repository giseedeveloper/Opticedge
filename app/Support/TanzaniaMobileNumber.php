<?php

namespace App\Support;

use InvalidArgumentException;

class TanzaniaMobileNumber
{
    /**
     * Normalize Tanzanian mobile numbers to 255XXXXXXXXX (12 digits).
     * Accepts local (06/07…) and international (2556/2557…) formats.
     */
    public static function normalize(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone) ?? '';

        if ($clean === '') {
            throw new InvalidArgumentException(self::invalidMessage());
        }

        // 0678165524 / 0781655524 → drop leading 0
        if (str_starts_with($clean, '0') && strlen($clean) === 10) {
            $clean = substr($clean, 1);
        }

        // 678165524 / 781655524 → prepend country code
        if (strlen($clean) === 9 && preg_match('/^[67]\d{8}$/', $clean)) {
            $clean = '255'.$clean;
        }

        if (! preg_match('/^255[67]\d{8}$/', $clean)) {
            throw new InvalidArgumentException(self::invalidMessage());
        }

        return $clean;
    }

    public static function invalidMessage(): string
    {
        return 'Invalid phone number. Use format 06XXXXXXXX, 07XXXXXXXX, 2556XXXXXXXX, or 2557XXXXXXXX.';
    }
}
