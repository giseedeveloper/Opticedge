<?php

namespace App\Support;

use Carbon\CarbonInterface;

class PurchaseInvoiceNumber
{
    /**
     * Sequential purchase invoice number: P{YY}DS##### (e.g. P26DS00001).
     */
    public static function unique(?string $distributorName = null, CarbonInterface|string|null $date = null): string
    {
        return DocumentNumberGenerator::nextPurchase($date);
    }
}
