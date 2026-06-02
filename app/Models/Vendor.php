<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantStrict;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use BelongsToTenantStrict, HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'office_name',
        'location',
        'tenant_id',
    ];
}

