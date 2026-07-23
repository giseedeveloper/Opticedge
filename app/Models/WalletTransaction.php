<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    public const TYPE_TOPUP = 'topup';

    public const TYPE_PAYOUT = 'payout';

    public const TYPE_PAYOUT_REVERSAL = 'payout_reversal';

    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'tenant_id',
        'direction',
        'amount',
        'balance_after',
        'type',
        'reference_type',
        'reference_id',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
