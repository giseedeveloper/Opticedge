<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantStrict;
use Illuminate\Database\Eloquent\Model;

class CustomerNeed extends Model
{
    use BelongsToTenantStrict;

    protected $fillable = [
        'agent_id',
        'team_leader_id',
        'tenant_id',
        'category_id',
        'product_id',
        'customer_name',
        'customer_phone',
        'branch_id',
    ];

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function teamLeader()
    {
        return $this->belongsTo(User::class, 'team_leader_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
