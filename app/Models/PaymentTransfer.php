<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantStrict;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransfer extends Model
{
    use BelongsToTenantStrict;

    protected $table = 'payment_transfers';

    protected $fillable = [
        'from_channel_id',
        'to_channel_id',
        'amount',
        'description',
        'user_id',
        'tenant_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the channel that transferred money
     */
    public function fromChannel(): BelongsTo
    {
        return $this->belongsTo(PaymentOption::class, 'from_channel_id');
    }

    /**
     * Get the channel that received money
     */
    public function toChannel(): BelongsTo
    {
        return $this->belongsTo(PaymentOption::class, 'to_channel_id');
    }

    /**
     * Get the user who made the transfer
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
