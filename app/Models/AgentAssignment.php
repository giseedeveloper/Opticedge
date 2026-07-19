<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantStrict;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AgentAssignment extends Model
{
    use BelongsToTenantStrict;

    public const TYPE_IMEI = 'imei';
    public const TYPE_TOTAL = 'total';

    protected $fillable = [
        'agent_id',
        'tenant_id',
        'product_id',
        'purchase_id',
        'assignment_type',
        'quantity_assigned',
        'quantity_sold',
    ];

    protected $attributes = [
        'assignment_type' => self::TYPE_IMEI,
    ];

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class)->withTrashed();
    }

    /** Quantity still available to sell */
    public function getQuantityRemainingAttribute(): int
    {
        return max(0, (int) $this->quantity_assigned - (int) $this->quantity_sold);
    }

    public function isImei(): bool
    {
        return $this->assignment_type === self::TYPE_IMEI;
    }

    public function isTotal(): bool
    {
        return $this->assignment_type === self::TYPE_TOTAL;
    }

    public function scopeImei(Builder $query): Builder
    {
        return $query->where('assignment_type', self::TYPE_IMEI);
    }

    public function scopeTotal(Builder $query): Builder
    {
        return $query->where('assignment_type', self::TYPE_TOTAL);
    }
}
