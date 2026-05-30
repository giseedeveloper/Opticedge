<?php

namespace App\Support;

/**
 * Parse pasted IMEI / serial lists from admin forms (newlines, spaces, commas, glued digits).
 */
final class ImeiListParser
{
    public const MAX_LENGTH = 512;

    /**
     * @return list<string>
     */
    public static function parse(string $raw): array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        $out = [];

        foreach (explode("\n", $raw) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // First split on commas/semicolons, then whitespace (so "a,b c" works)
            $segments = preg_split('/[,;]+/u', $line, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($segments as $seg) {
                $seg = trim($seg);
                if ($seg === '') {
                    continue;
                }
                $parts = preg_split('/\s+/u', $seg, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($parts as $p) {
                    $p = trim($p, " \t,.;");
                    if ($p === '') {
                        continue;
                    }
                    foreach (self::splitGluedNumericCodes($p) as $code) {
                        $out[] = $code;
                    }
                }
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * One IMEI per line only (no comma, space, or semicolon splitting).
     *
     * @return list<string>
     */
    public static function parseOnePerLine(string $raw): array
    {
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        $out = [];

        foreach (explode("\n", $raw) as $line) {
            $line = trim($line);
            if ($line !== '') {
                $out[] = $line;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * If scanners paste many IMEIs with no separator, digits-only string may be 15*n digits.
     *
     * @return list<string>
     */
    private static function splitGluedNumericCodes(string $token): array
    {
        if (! preg_match('/^\d+$/', $token)) {
            return [$token];
        }

        $len = strlen($token);
        if ($len <= 17) {
            return [$token];
        }

        // Standard IMEI length is 15; split only when length is a multiple of 15 and long enough to be two+ codes
        if ($len >= 30 && $len % 15 === 0) {
            $chunks = [];
            for ($i = 0; $i < $len; $i += 15) {
                $chunks[] = substr($token, $i, 15);
            }

            return $chunks;
        }

        return [$token];
    }

    /**
     * @param  list<string>  $imeis
     * @return list<string> error messages (empty if OK)
     */
    public static function lengthErrors(array $imeis): array
    {
        $errors = [];
        foreach ($imeis as $imei) {
            if (strlen($imei) > self::MAX_LENGTH) {
                $errors[] = 'IMEI/serial too long ('.strlen($imei).' chars, max '.self::MAX_LENGTH.'): '.self::truncateForMessage($imei);
            }
        }

        return $errors;
    }

    private static function truncateForMessage(string $s): string
    {
        if (strlen($s) <= 40) {
            return $s;
        }

        return substr($s, 0, 20).'…'.substr($s, -8);
    }
}
