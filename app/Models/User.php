<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
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
        'profile_image',
        'branch_id',
        'region_id',
        'regional_manager_id',
        'team_leader_id',
        'notes',
        'how_did_you_hear',
        'referred_by',
        'google_id',
        'avatar',
        'experience_bio',
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
    public function getProfileImageUrlAttribute(): ?string
    {
        if (empty($this->profile_image)) {
            return null;
        }

        return asset('storage/'.$this->profile_image);
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

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin' && $this->tenant_id === null;
    }

    public function isTenantAdmin(): bool
    {
        return in_array($this->role, ['admin', 'subadmin'], true) && $this->tenant_id !== null;
    }

    public function isGuest(): bool
    {
        return $this->role === 'guest';
    }

    /**
     * Unassigned self-registered users waiting for a vendor admin to pick them up.
     */
    public function isUnassignedGuest(): bool
    {
        return $this->isGuest() && $this->tenant_id === null;
    }

    /**
     * Field roles (team leader, regional manager, agent) cannot edit their own name/email.
     * Tenant admins update those users from the admin directory.
     */
    public function canUpdateOwnProfileInformation(): bool
    {
        return ! in_array($this->role, ['teamleader', 'regional_manager', 'agent'], true);
    }

    /**
     * User roles allowed when listing or filtering users by `role` (admin directory tabs, API).
     */
    public static function customerDirectoryRoleFilters(): array
    {
        return ['dealer', 'customer', 'agent', 'teamleader', 'regional_manager', 'subadmin'];
    }

    /**
     * Eager-load relations used by listBranchName() / listRegionName().
     */
    public function scopeWithLocationRelations(Builder $query): Builder
    {
        $with = [];

        if (Schema::hasColumn('users', 'branch_id')) {
            $with[] = 'branch:id,name';
        }
        if (Schema::hasColumn('users', 'region_id')) {
            $with[] = 'region:id,name';
        }
        if (Schema::hasColumn('users', 'regional_manager_id')) {
            $with[] = 'regionalManager:id,name,region_id';
            $with[] = 'regionalManager.region:id,name';
        }
        if (Schema::hasColumn('users', 'team_leader_id')) {
            $with[] = 'teamLeader:id,name,region_id,branch_id,regional_manager_id';
            $with[] = 'teamLeader.region:id,name';
            $with[] = 'teamLeader.branch:id,name';
            $with[] = 'teamLeader.regionalManager:id,name,region_id';
            $with[] = 'teamLeader.regionalManager.region:id,name';
        }

        return $with === [] ? $query : $query->with($with);
    }

    public function listBranchName(): ?string
    {
        return $this->branch?->name ?? $this->teamLeader?->branch?->name;
    }

    public function listRegionName(): ?string
    {
        return $this->region?->name
            ?? $this->teamLeader?->region?->name
            ?? $this->regionalManager?->region?->name
            ?? $this->teamLeader?->regionalManager?->region?->name;
    }

    /**
     * Filter users by name, email, phone, or business name (admin directory search).
     */
    public function scopeDirectorySearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        $like = '%'.$search.'%';

        return $query->where(function ($q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like);

            if (Schema::hasColumn('users', 'business_name')) {
                $q->orWhere('business_name', 'like', $like);
            }
        });
    }

    /**
     * Columns allowed for admin directory sorting.
     *
     * @return list<string>
     */
    public static function directorySortColumns(): array
    {
        $columns = ['name', 'email', 'phone', 'role', 'status', 'created_at'];

        if (Schema::hasColumn('users', 'business_name')) {
            $columns[] = 'business_name';
        }

        return $columns;
    }

    /**
     * Default sort for a directory list (optional role context).
     *
     * @return array{sort: string, direction: string}
     */
    public static function defaultDirectorySort(?string $role = null): array
    {
        return ['sort' => 'name', 'direction' => 'asc'];
    }

    /**
     * Resolve validated sort params from a request.
     *
     * @return array{sort: string, direction: string}
     */
    public static function resolveDirectorySort(?string $sort, ?string $direction, ?string $role = null): array
    {
        $defaults = self::defaultDirectorySort($role);
        $allowed = self::directorySortColumns();

        if (! in_array($sort, $allowed, true) || ! Schema::hasColumn('users', $sort)) {
            $sort = $defaults['sort'];
        }

        $direction = strtolower((string) $direction);
        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = $defaults['direction'];
        }

        return ['sort' => $sort, 'direction' => $direction];
    }

    /**
     * Apply validated sort order to a user directory query.
     */
    public function scopeDirectoryOrder(Builder $query, ?string $sort, ?string $direction, ?string $role = null): Builder
    {
        $resolved = self::resolveDirectorySort($sort, $direction, $role);

        return $query
            ->orderBy($resolved['sort'], $resolved['direction'])
            ->orderBy('id', $resolved['direction']);
    }

    /**
     * @return array<string, mixed>
     */
    public function toDirectoryListArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status ?? 'active',
            'phone' => $this->phone,
            'profile_image' => $this->profile_image,
            'profile_image_url' => $this->profile_image_url,
            'business_name' => $this->business_name,
            'branch_id' => $this->branch_id,
            'branch_name' => $this->listBranchName(),
            'region_id' => $this->region_id,
            'region_name' => $this->listRegionName(),
            'team_leader_id' => $this->team_leader_id,
            'team_leader_name' => $this->teamLeader?->name,
            'regional_manager_id' => $this->regional_manager_id,
            'regional_manager_name' => $this->regionalManager?->name,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
