<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamLeaderProductListAssignment extends Model
{
    protected $fillable = [
        'team_leader_id',
        'product_list_id',
    ];

    public function teamLeader()
    {
        return $this->belongsTo(User::class, 'team_leader_id');
    }

    public function productListItem()
    {
        return $this->belongsTo(ProductListItem::class, 'product_list_id');
    }
}
