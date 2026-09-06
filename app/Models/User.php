<?php

namespace App\Models;

use App\Events\AuditEvent;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'name',
    'email',
    'phone',
    'password',
    'user_type',
    'status',
    'email_verified_at',
    'failed_login_attempts',
    'locked_until',
    'last_login_at',
    'last_login_ip',
    'password_changed_at',
    'two_factor_confirmed_at',
    'disabled_at',
    'disabled_by',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const TYPE_STAFF = 'staff';

    public const TYPE_CUSTOMER = 'customer';

    public const STATUS_INVITED = 'invited';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_LOCKED = 'locked';

    public const STATUS_DISABLED = 'disabled';

    public const DASHBOARD_ROLE_SLUGS = [
        Role::SUPER_ADMIN,
        Role::ADMIN,
        Role::SALES_STAFF,
        Role::INVENTORY_STAFF,
        Role::FINANCE_STAFF,
        Role::PRODUCTION_STAFF,
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withPivot(['assigned_by_user_id', 'assigned_at'])
            ->withTimestamps();
    }

    public function hasRole(string|array $roles): bool
    {
        $slugs = is_array($roles) ? $roles : [$roles];

        return $this->roles()->whereIn('slug', $slugs)->exists();
    }

    public function hasAnyRole(string|array $roles): bool
    {
        return $this->hasRole($roles);
    }

    public function hasPermissionTo(string|Permission $permission): bool
    {
        $slug = $permission instanceof Permission ? $permission->slug : $permission;

        if ($this->hasRole(Role::SUPER_ADMIN)) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', fn ($query) => $query->where('slug', $slug))
            ->exists();
    }

    public function assignRole(Role|string $role, ?User $assignedBy = null): void
    {
        $role = $role instanceof Role
            ? $role
            : Role::query()->where('slug', $role)->firstOrFail();

        $this->roles()->syncWithoutDetaching([
            $role->id => [
                'assigned_by_user_id' => $assignedBy?->id,
                'assigned_at' => now(),
            ],
        ]);

        $userId = $this->id;
        $userEmail = $this->email;
        $roleSlug = $role->slug;
        $roleName = $role->name;
        $roleId = $role->id;
        $assignedById = $assignedBy?->id;
        $actor = $assignedBy ?? Auth::user();

        DB::afterCommit(static function () use ($userId, $userEmail, $roleSlug, $roleName, $roleId, $assignedById, $actor): void {
            event(new AuditEvent('users.role_assigned', $actor, [
                'subject_type' => 'user',
                'subject_id' => $userId,
                'subject_public_id' => $userEmail,
                'role_id' => $roleId,
                'new_values' => [
                    'role_slug' => $roleSlug,
                    'role_name' => $roleName,
                ],
                'metadata' => [
                    'assigned_by_user_id' => $assignedById,
                ],
            ]));
        });
    }

    public function syncRoles(array $roles, ?User $assignedBy = null): void
    {
        $ids = Role::query()->whereIn('slug', $roles)->pluck('id')->all();

        $sync = [];

        foreach ($ids as $id) {
            $sync[$id] = [
                'assigned_by_user_id' => $assignedBy?->id,
                'assigned_at' => now(),
            ];
        }

        $this->roles()->sync($sync);

        $userId = $this->id;
        $userEmail = $this->email;
        $assignedById = $assignedBy?->id;
        $actor = $assignedBy ?? Auth::user();

        DB::afterCommit(static function () use ($userId, $userEmail, $roles, $assignedById, $actor): void {
            event(new AuditEvent('users.permission_updated', $actor, [
                'subject_type' => 'user',
                'subject_id' => $userId,
                'subject_public_id' => $userEmail,
                'new_values' => [
                    'roles_synced' => $roles,
                ],
                'metadata' => [
                    'assigned_by_user_id' => $assignedById,
                ],
            ]));
        });
    }

    public function canAccessDashboard(): bool
    {
        return $this->user_type === self::TYPE_STAFF
            && $this->status === self::STATUS_ACTIVE
            && $this->hasVerifiedEmail()
            && ! $this->isDashboardLocked()
            && $this->hasAnyRole(self::DASHBOARD_ROLE_SLUGS);
    }

    public function canRequestDashboardPasswordReset(): bool
    {
        return $this->user_type === self::TYPE_STAFF
            && in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_INVITED], true)
            && ! $this->isDashboardLocked()
            && $this->hasAnyRole(self::DASHBOARD_ROLE_SLUGS);
    }

    public function isDashboardLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED
            || ($this->locked_until !== null && $this->locked_until->isFuture());
    }

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
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'disabled_at' => 'datetime',
        ];
    }

    /**
     * Get the user's name initials.
     */
    public function initials(): string
    {
        $words = preg_split('/\s+/', trim($this->name ?? ''));
        $words = array_filter($words);
        if (empty($words)) {
            return 'US';
        }

        return count($words) >= 2
            ? mb_strtoupper(mb_substr($words[0], 0, 1, 'UTF-8').mb_substr(end($words), 0, 1, 'UTF-8'), 'UTF-8')
            : mb_strtoupper(mb_substr($words[0], 0, 2, 'UTF-8'), 'UTF-8');
    }
}
