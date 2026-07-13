<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRating extends Model
{
    public const SOURCE_TERMINATION = 'termination';

    public const SOURCE_MANUAL = 'manual';

    protected $fillable = [
        'rated_user_id',
        'rater_user_id',
        'tenant_id',
        'score',
        'comment',
        'tenure_id',
        'source',
    ];

    public function ratedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rated_user_id');
    }

    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function tenure(): BelongsTo
    {
        return $this->belongsTo(UserVendorTenure::class, 'tenure_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        $this->loadMissing(['tenant:id,name,brand_name', 'rater:id,name']);

        return [
            'id' => $this->id,
            'score' => (int) $this->score,
            'comment' => $this->comment,
            'vendor_name' => $this->tenant?->brand_name ?: $this->tenant?->name,
            'tenant_id' => $this->tenant_id,
            'rater_name' => $this->rater?->name,
            'source' => $this->source,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
