<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantStrict;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use BelongsToTenantStrict;

    protected $fillable = ['key', 'value', 'tenant_id'];
}
