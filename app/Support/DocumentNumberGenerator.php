<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class DocumentNumberGenerator
{
    public const TYPE_DISTRIBUTOR = 'DS';

    public const TYPE_DEALER = 'DL';

    /**
     * Next purchase invoice number, e.g. P26DS00001.
     */
    public static function nextPurchase(CarbonInterface|string|null $date = null): string
    {
        return self::next('P', self::TYPE_DISTRIBUTOR, $date);
    }

    /**
     * Next distribution sale invoice number, e.g. S26DL00001.
     */
    public static function nextDistributionSale(CarbonInterface|string|null $date = null): string
    {
        return self::next('S', self::TYPE_DEALER, $date);
    }

    public static function next(string $direction, string $typeCode, CarbonInterface|string|null $date = null): string
    {
        $when = $date instanceof CarbonInterface
            ? $date
            : ($date ? \Carbon\Carbon::parse($date) : now());

        $year = $when->format('y');
        $series = strtoupper($direction).$year.strtoupper($typeCode);

        return DB::transaction(function () use ($series) {
            $row = DB::table('document_number_sequences')
                ->where('series', $series)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                DB::table('document_number_sequences')->insert([
                    'series' => $series,
                    'last_number' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $next = 1;
            } else {
                $next = (int) $row->last_number + 1;
                DB::table('document_number_sequences')
                    ->where('series', $series)
                    ->update([
                        'last_number' => $next,
                        'updated_at' => now(),
                    ]);
            }

            return $series.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
        });
    }
}
