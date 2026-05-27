<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use BelongsToTenant, HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'role',
        'status',
        'ability',
        'subadmin_role_id',
        'business_name',
        'phone',
        'branch_id',
        'region_id',
        'regional_manager_id',
        'team_leader_id',
        'notes',
        'how_did_you_hear',
        'referred_by',
    ];

    /** Friend/referrer who made this user (dealer) join – gets commission on first dealer purchase */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /** Dealers referred by this user (when this user is the "seller" who gets commission) */
    public function referredDealers()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    /** Direct manager for team leaders (regional manager user). */
    public function regionalManager()
    {
        return $this->belongsTo(User::class, 'regional_manager_id');
    }

    /** Team leaders reporting to this regional manager. */
    public function managedTeamLeaders()
    {
        return $this->hasMany(User::class, 'regional_manager_id');
    }

    /** Team leader this agent reports to (agents only). */
    public function teamLeader()
    {
        return $this->belongsTo(User::class, 'team_leader_id');
    }

    /** Agents reporting to this user when they are a team leader. */
    public function managedAgents()
    {
        return $this->hasMany(User::class, 'team_leader_id');
    }

    public function subadminRole()
    {
        return $this->belongsTo(SubadminRole::class, 'subadmin_role_id');
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin' && $this->tenant_id === null;
    }

    public function isTenantAdmin(): bool
    {
        return in_array($this->role, ['admin', 'subadmin'], true) && $this->tenant_id !== null;
    }

    /**
     * User roles allowed when listing or filtering users by `role` (admin directory tabs, API).
     */
    public static function customerDirectoryRoleFilters(): array
    {
        return ['dealer', 'customer', 'agent', 'teamleader', 'regional_manager'];
    }
}
