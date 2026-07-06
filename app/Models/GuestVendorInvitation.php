<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestVendorInvitation extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'guest_user_id',
        'tenant_id',
        'invited_by',
        'proposed_role',
        'status',
        'assignment_payload',
        'message',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'assignment_payload' => 'array',
            'responded_at' => 'datetime',
        ];
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guest_user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
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
    public function toGuestListArray(): array
    {
        $this->loadMissing(['tenant:id,name,brand_name', 'inviter:id,name']);

        $payload = $this->assignment_payload ?? [];
        $branchName = null;
        $regionName = null;

        if (! empty($payload['branch_id'])) {
            $branchName = Branch::withoutGlobalScopes()->find($payload['branch_id'])?->name;
        }
        if (! empty($payload['region_id'])) {
            $regionName = Region::withoutGlobalScopes()->find($payload['region_id'])?->name;
        }

        return [
            'id' => $this->id,
            'status' => $this->status,
            'proposed_role' => $this->proposed_role,
            'proposed_role_label' => self::roleLabel($this->proposed_role),
            'vendor_name' => $this->tenant?->brand_name ?: $this->tenant?->name,
            'tenant_id' => $this->tenant_id,
            'invited_by_name' => $this->inviter?->name,
            'message' => $this->message,
            'branch_name' => $branchName,
            'region_name' => $regionName,
            'business_name' => $payload['business_name'] ?? null,
            'created_at' => $this->created_at?->toISOString(),
            'responded_at' => $this->responded_at?->toISOString(),
        ];
    }
}
