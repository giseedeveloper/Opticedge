<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantStrict;
use Illuminate\Database\Eloquent\Model;

class Payable extends Model
{
    use BelongsToTenantStrict;

    protected $fillable = [
        'item_name',
        'amount',
        'date',
        'tenant_id',
    ];
}
