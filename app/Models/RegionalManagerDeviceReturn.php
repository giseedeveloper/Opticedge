<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegionalManagerDeviceReturn extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'from_regional_manager_id',
        'status',
        'message',
        'recipient_note',
        'decided_at',
        'decided_by',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    public function fromRegionalManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_regional_manager_id');
    }

    public function decidedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RegionalManagerDeviceReturnItem::class, 'regional_manager_device_return_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
