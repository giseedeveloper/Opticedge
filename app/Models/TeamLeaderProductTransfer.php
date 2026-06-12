<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeamLeaderProductTransfer extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'from_regional_manager_id',
        'to_team_leader_id',
        'status',
        'message',
        'admin_note',
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

    public function toTeamLeader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_team_leader_id');
    }

    public function decidedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TeamLeaderProductTransferItem::class, 'team_leader_product_transfer_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
