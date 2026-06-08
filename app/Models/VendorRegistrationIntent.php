<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorRegistrationIntent extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PAYMENT_PENDING = 'payment_pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const TYPE_REGISTRATION = 'registration';

    public const TYPE_RENEWAL = 'renewal';

    protected $fillable = [
        'intent_type',
        'package_id',
        'vendor_name',
        'brand_name',
        'slug',
        'admin_name',
        'email',
        'phone',
        'password',
        'payment_phone',
        'status',
        'tenant_id',
        'user_id',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isRenewal(): bool
    {
        return ($this->intent_type ?? self::TYPE_REGISTRATION) === self::TYPE_RENEWAL;
    }
}
