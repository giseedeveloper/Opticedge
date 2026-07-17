<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractTerminationRequest extends Model
{
    public const STATUS_AWAITING_MAJOR = 'awaiting_major';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const MAJOR_PENDING = 'pending';

    public const MAJOR_APPROVED = 'approved';

    public const MAJOR_REJECTED = 'rejected';

    public const MAJOR_SKIPPED = 'skipped';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'major_user_id',
        'role_at_request',
        'status',
        'major_status',
        'reason',
        'admin_note',
        'major_note',
        'decided_at',
        'decided_by',
        'major_decided_at',
        'major_decided_by',
        'snapshot',
        'force_initiated',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'decided_at' => 'datetime',
            'major_decided_at' => 'datetime',
            'force_initiated' => 'boolean',
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

    public function majorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'major_user_id');
    }

    public function decidedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function majorDecidedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'major_decided_by');
    }

    public function isAwaitingMajor(): bool
    {
        return $this->status === self::STATUS_AWAITING_MAJOR;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_AWAITING_MAJOR, self::STATUS_PENDING], true);
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

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_AWAITING_MAJOR => 'Pending admin review',
            self::STATUS_PENDING => 'Pending admin review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toListArray(): array
    {
        $this->loadMissing([
            'user:id,name,email,phone',
            'tenant:id,name,brand_name',
            'decidedByUser:id,name',
            'majorUser:id,name,role',
            'majorDecidedByUser:id,name',
        ]);

        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => self::statusLabel($this->status),
            'major_status' => $this->major_status,
            'role_at_request' => $this->role_at_request,
            'role_label' => self::roleLabel($this->role_at_request),
            'reason' => $this->reason,
            'admin_note' => $this->admin_note,
            'major_note' => $this->major_note,
            'force_initiated' => (bool) $this->force_initiated,
            'vendor_name' => $this->tenant?->brand_name ?: $this->tenant?->name,
            'tenant_id' => $this->tenant_id,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ] : null,
            'major_user' => $this->majorUser ? [
                'id' => $this->majorUser->id,
                'name' => $this->majorUser->name,
                'role' => $this->majorUser->role,
            ] : null,
            'decided_at' => $this->decided_at?->toISOString(),
            'decided_by_name' => $this->decidedByUser?->name,
            'major_decided_at' => $this->major_decided_at?->toISOString(),
            'major_decided_by_name' => $this->majorDecidedByUser?->name,
            'created_at' => $this->created_at?->toISOString(),
            'can_cancel' => $this->isOpen(),
            'can_approve' => $this->isPending() || $this->isAwaitingMajor(),
            'can_reject' => $this->isPending() || $this->isAwaitingMajor(),
            'can_major_approve' => false,
            'can_major_reject' => false,
        ];
    }
}
