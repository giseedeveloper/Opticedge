<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractTerminationRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'role_at_request',
        'status',
        'reason',
        'admin_note',
        'decided_at',
        'decided_by',
        'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'decided_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function decidedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public static function roleLabel(string $role): string
    {
        return match ($role) {
            'agent' => 'Agent',
            'teamleader' => 'Team leader',
            'regional_manager' => 'Regional manager',
            default => ucfirst(str_replace('_', ' ', $role)),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toListArray(): array
    {
        $this->loadMissing(['user:id,name,email,phone', 'tenant:id,name,brand_name', 'decidedByUser:id,name']);

        return [
            'id' => $this->id,
            'status' => $this->status,
            'role_at_request' => $this->role_at_request,
            'role_label' => self::roleLabel($this->role_at_request),
            'reason' => $this->reason,
            'admin_note' => $this->admin_note,
            'vendor_name' => $this->tenant?->brand_name ?: $this->tenant?->name,
            'tenant_id' => $this->tenant_id,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ] : null,
            'decided_at' => $this->decided_at?->toISOString(),
            'decided_by_name' => $this->decidedByUser?->name,
            'created_at' => $this->created_at?->toISOString(),
            'can_cancel' => $this->isPending(),
            'can_approve' => $this->isPending(),
            'can_reject' => $this->isPending(),
        ];
    }
}
