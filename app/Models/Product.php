<?php

namespace App\Models;

use App\Models\Concerns\HasPlatformCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class Product extends Model
{
    use HasPlatformCatalog;

    protected static ?string $resolvedTable = null;

    protected $fillable = [
        'category_id',
        'name',
        'brand',
        'price',
        'rating',
        'stock_quantity',
        'description',
        'images',
        'is_platform',
        'created_by_tenant_id',
    ];

    protected $casts = [
        'images' => 'array',
        'is_platform' => 'boolean',
    ];

    public function createdByTenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'created_by_tenant_id');
    }

    public function getTable()
    {
        if (static::$resolvedTable !== null) {
            return static::$resolvedTable;
        }

        // Support both legacy schema (`products`) and renamed schema (`models`).
        static::$resolvedTable = Schema::hasTable('models') ? 'models' : 'products';

        return static::$resolvedTable;
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function productListItems()
    {
        return $this->hasMany(ProductListItem::class, 'product_id');
    }

    /**
     * Models the regional manager currently holds from admin (unsold, not yet with team leader or agent).
     */
    public static function inRegionalManagerCustodyForTeamLeaderAssignment(int $regionalManagerId)
    {
        $productIds = ProductListItem::catalogProductIdsInRegionalManagerCustody($regionalManagerId);

        return static::query()
            ->with('category')
            ->when(
                $productIds->isEmpty(),
                fn ($q) => $q->whereRaw('1 = 0'),
                fn ($q) => $q->whereIn('id', $productIds->all())
            )
            ->orderBy('name');
    }

    /**
     * Models the team leader currently holds from regional manager (unsold, not yet with an agent).
     */
    public static function inTeamLeaderCustodyForAgentAssignment(int $teamLeaderId)
    {
        $productIds = ProductListItem::catalogProductIdsInTeamLeaderCustody($teamLeaderId);

        return static::query()
            ->with('category')
            ->when(
                $productIds->isEmpty(),
                fn ($q) => $q->whereRaw('1 = 0'),
                fn ($q) => $q->whereIn('id', $productIds->all())
            )
            ->orderBy('name');
    }

    /**
     * Models the agent currently holds (unsold, returnable to team leader).
     */
    public static function returnableByAgentToTeamLeader(int $agentId)
    {
        $productIds = ProductListItem::catalogProductIdsReturnableByAgent($agentId);

        return static::query()
            ->with('category')
            ->when(
                $productIds->isEmpty(),
                fn ($q) => $q->whereRaw('1 = 0'),
                fn ($q) => $q->whereIn('id', $productIds->all())
            )
            ->orderBy('name');
    }

    /**
     * Models the team leader can return to regional manager (in TL custody, not with an agent).
     */
    public static function returnableByTeamLeaderToRegionalManager(int $teamLeaderId)
    {
        return static::inTeamLeaderCustodyForAgentAssignment($teamLeaderId);
    }

    /**
     * Models the regional manager can return to admin (in RM custody, not with TL or agent).
     */
    public static function returnableByRegionalManagerToAdmin(int $regionalManagerId)
    {
        return static::inRegionalManagerCustodyForTeamLeaderAssignment($regionalManagerId);
    }
}
