<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserVendorTenure extends Model
{
    public const SOURCE_INVITATION = 'invitation';

    public const SOURCE_DIRECT_ASSIGN = 'direct_assign';

    public const SOURCE_BACKFILL = 'backfill';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'role',
        'started_at',
        'ended_at',
        'source',
        'invitation_id',
        'termination_request_id',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
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

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(GuestVendorInvitation::class, 'invitation_id');
    }

    public function terminationRequest(): BelongsTo
    {
        return $this->belongsTo(ContractTerminationRequest::class, 'termination_request_id');
    }

    public function isOpen(): bool
    {
        return $this->ended_at === null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toHistoryArray(): array
    {
        $this->loadMissing(['tenant:id,name,brand_name']);

        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'vendor_name' => $this->tenant?->brand_name ?: $this->tenant?->name,
            'role' => $this->role,
            'role_label' => GuestVendorInvitation::roleLabel($this->role),
            'started_at' => $this->started_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'is_current' => $this->isOpen(),
            'source' => $this->source,
        ];
    }
}
