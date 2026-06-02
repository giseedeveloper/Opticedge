<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantStrict;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use BelongsToTenantStrict;

    protected $fillable = [
        'activity',
        'amount',
        'cash_used',
        'payment_option_id',
        'date',
        'tenant_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public const CASH_SYSTEM = 'system';
    public const CASH_CASH = 'cash';

    public static function cashOptions(): array
    {
        return [
            self::CASH_SYSTEM => 'System',
            self::CASH_CASH => 'Cash',
        ];
    }

    public function paymentOption()
    {
        return $this->belongsTo(PaymentOption::class);
    }
}
