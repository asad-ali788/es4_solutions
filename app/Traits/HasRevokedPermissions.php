<?php

namespace App\Traits;

use Spatie\Permission\Models\Permission;

trait HasRevokedPermissions
{
    protected bool $revokedLoaded = false;
    protected array $revokedSet = [];

    public function revokedPermissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'user_denied_permissions',
            'user_id',
            'permission_id'
        );
    }

    /**
     * Load all revoked permissions once (optionally filtered by guard)
     */
    protected function loadRevokedSet(?string $guard = null): void
    {
        if ($this->revokedLoaded) {
            return;
        }

        $this->loadMissing('revokedPermissions:id,name,guard_name');

        $guard = $guard ?? $this->getDefaultGuardName();

        $this->revokedSet = $this->revokedPermissions
            ->when($guard, fn($query) => $query->where('guard_name', $guard))
            ->pluck('name')
            ->flip()
            ->map(fn() => true)
            ->all();

        $this->revokedLoaded = true;
    }

    /**
     * O(1) in-memory check once warmed
     */
    public function hasRevokedPermission(string $name, ?string $guard = null): bool
    {
        $this->loadRevokedSet($guard);
        return isset($this->revokedSet[$name]);
    }

    /**
     * Override hasPermissionTo: deny if revoked, otherwise defer to Spatie
     */
    // inside HasRevokedPermissions trait
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        $guard = $guardName ?? $this->getDefaultGuardName();

        $this->loadRevokedSet($guard);

        $permName = $permission instanceof \Spatie\Permission\Models\Permission
            ? $permission->name
            : (string) $permission;

        if (isset($this->revokedSet[$permName])) {
            return false; // hard deny from in-memory set
        }

        // delegate the "allow" check to Spatie's original (aliased) method
        return $this->spatieHasPermissionTo($permission, $guardName);
    }
}
