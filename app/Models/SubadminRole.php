<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenantStrict;
use Illuminate\Database\Eloquent\Model;

class SubadminRole extends Model
{
    use BelongsToTenantStrict;

    protected $fillable = [
        'name',
        'system_key',
        'description',
        'tenant_id',
    ];

    public function permissions()
    {
        return $this->hasMany(SubadminRolePermission::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'subadmin_role_id');
    }
}
