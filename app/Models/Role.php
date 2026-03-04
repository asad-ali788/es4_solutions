<?php

namespace App\Models;

use App\Enum\Permissions\DefaultRoleEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

class Role extends SpatieRole implements RoleContract
{
    //
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = 'roles';

    protected $fillable = [
        'name',
        'guard_name',
        'group_id',
    ];

    // NOTE: The real spatie package was used to modify the role creation.

    protected static function findByParam(array $params = []): ?RoleContract
    {
        $query = static::query();
        if (app(PermissionRegistrar::class)->teams) {
            $teamsKey = app(PermissionRegistrar::class)->teamsKey;

            $query->where(
                fn ($q) => $q->whereNull($teamsKey)
                    ->orWhere($teamsKey, $params[$teamsKey] ?? getPermissionsTeamId())
            );

            // unset($params[$teamsKey]); // This is the change
        }

        foreach ($params as $key => $value) {
            $query->where($key, $value);
        }

        return $query->first();
    }

    public function scopeDefaultGroup(Builder $query): void
    {
        $query->where('group_id', config('permission.global_group_id'));
    }

    public function scopeDefaultGuard(Builder $query): void
    {
        $query->where('guard_name', 'web');
    }

    public function scopeDefaultRoleEnv(Builder $query): void
    {
        $query->where('guard_name', 'web');
    }

    public function scopeExcludeDefaultRole(Builder $query): void
    {
        $query->whereNotIn('name', DefaultRoleEnum::cases());
    }

    public static function fetchAllDefaultStaticRoles(): array
    {
        return [
            DefaultRoleEnum::labels(),
        ];
    }

    public static function fetchAllDefaultStaticRoleKeys(): array
    {
        return [
            DefaultRoleEnum::cases(),
        ];
    }

    public function getCreatedAtAttribute($value): string
    {
        return Carbon::parse($value)->format('d-M-Y');
    }

    public function getUpdatedAtAttribute($value): string
    {
        return Carbon::parse($value)->format('d-M-Y');
    }
}
