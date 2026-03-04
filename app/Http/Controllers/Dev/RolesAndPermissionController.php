<?php

namespace App\Http\Controllers\Dev;

use App\Enum\Permissions\DeveloperEnum;
use App\Enum\Permissions\UserEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use App\Models\Permission;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Exception;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\Log;

class RolesAndPermissionController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize(UserEnum::UserRole);
        $search = $request->input('search');
        $roles  = Role::withCount('permissions')
            ->whereNotIn('name', ['administrator', 'developer'])
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();
        $permissionLabels = Permission::fetchAllStaticPermissions();
        return view('pages.admin.user.rolesAndPermission.index', compact('roles', 'search'));
    }


    public function create()
    {
        $this->authorize(UserEnum::UserRoleCreate);
        $role               = new Role();
        $isCreateMode       = true;
        $allPermissionNames = Permission::pluck('name', 'id');
        $permissionLabels   = Permission::fetchAllStaticPermissions();
        $selected           = [];

        return view('pages.admin.user.rolesAndPermission.form', compact(
            'role',
            'isCreateMode',
            'permissionLabels',
            'selected',
            'allPermissionNames'
        ));
    }

    public function store(Request $request)
    {
        $this->authorize(UserEnum::UserRoleCreate);
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'permissions' => ['array'],
        ]);
        $permissions = array_values(array_unique($data['permissions'] ?? []));
        DB::transaction(function () use ($data, $permissions) {
            $role = Role::create([
                'name'       => $data['name'],
                'guard_name' => 'web',
            ]);
            // One-shot sync for role permissions
            $role->syncPermissions($permissions);
        });
        return redirect()->route('admin.roles.index')->with('success', 'Role created and permissions synced.');
    }

    public function edit(Role $role)
    {
        $this->authorize(UserEnum::UserRoleUpdate);
        $isCreateMode       = false;
        $allPermissionNames = Permission::pluck('name', 'id');
        $permissionLabels   = Permission::fetchAllStaticPermissions();
        $selected           = $role->permissions->pluck('name')->all();

        return view('pages.admin.user.rolesAndPermission.form', compact(
            'role',
            'isCreateMode',
            'permissionLabels',
            'selected',
            'allPermissionNames'
        ));
    }

    public function update(Request $request, Role $role)
    {
        $this->authorize(UserEnum::UserRoleUpdate);
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'permissions' => ['array'],
        ]);
        $permissions = array_values(array_unique($data['permissions'] ?? []));

        DB::transaction(function () use ($role, $data, $permissions) {
            $role->update(['name' => $data['name']]);
            $role->syncPermissions($permissions);
        });

        return redirect()->route('admin.roles.index')->with('success', 'Role updated and permissions synced.');
    }

    public function destroy(Role $role)
    {
        $this->authorize(UserEnum::UserRoleDelete);
        if (in_array(strtolower($role->name), ['administrator', 'developer', 'md'])) {
            return back()->with('error', 'This role is protected and cannot be deleted.');
        }
        $guest = Role::firstOrCreate([
            'name'       => 'guest',
            'guard_name' => $role->guard_name,
        ]);
        $users = $role->users()->get();
        foreach ($users as $user) {
            $user->removeRole($role);
            // Make sure they have 'guest'
            if (!$user->hasRole($guest->name)) {
                $user->assignRole($guest);
            }
        }
        $role->delete();
        // Clear Spatie cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role deleted. Affected users were moved to the Guest role.');
    }


    // Permissions module section

    public function permissions(Request $request)
    {
        $this->authorize(DeveloperEnum::Developer);
        try {
            $search = $request->input('search', '');
            $query = SpatiePermission::query();

            if ($search !== '') {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('label', 'like', "%{$search}%");
            }
            $permissions = $query->orderBy('name')->paginate()->appends(['search' => $search]);

            return view('pages.admin.user.rolesAndPermission.permission.index', compact('permissions'));
        } catch (Exception $e) {
            Log::error("Permissions index failed by user ID " . auth()->id() . ": " . $e->getMessage());
            return back()->with('error', 'Something went wrong! Try again.');
        }
    }


    public function permissionCreate(Request $request)
    {
        $this->authorize(DeveloperEnum::Developer);

        try {
            $guards = array_keys(config('auth.guards'));
            $permission = null;
            return view('pages.admin.user.rolesAndPermission.permission.form', compact('permission', 'guards'));
        } catch (Exception $e) {
            Log::error("Permission create failed by user ID " . auth()->id() . ": " . $e->getMessage());
            return back()->with('error', 'Something went wrong! Try again.');
        }
    }


    public function permissionEdit(Request $request, $id)
    {
        $this->authorize(DeveloperEnum::Developer);

        try {
            $permission = SpatiePermission::findOrFail($id);
            return view('pages.admin.user.rolesAndPermission.permission.form', compact('permission'));
        } catch (Exception $e) {
            Log::error("Permission edit failed by user ID " . auth()->id() . ": " . $e->getMessage());
            return back()->with('error', 'Something went wrong! Try again.');
        }
    }


    public function permissionStore(Request $request, $id = null)
    {
        $this->authorize(DeveloperEnum::Developer);

        try {
            $data = $request->validate([
                'name'  => ['required', 'string', 'max:60', 'unique:permissions,name'],
                'label' => ['nullable', 'string', 'max:60'],
            ]);
            $data['guard'] = 'web';

            $permission = SpatiePermission::create($data);
            $this->attachToCoreRoles($permission);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            Log::info("Permission created by user ID " . auth()->id());

            return redirect()
                ->route('admin.roles.permissions.index')
                ->with('success', 'Permission created and assigned to Administrator & Developer.');
        } catch (Exception $e) {
            Log::error("Permission create failed by user ID " . auth()->id() . ": " . $e->getMessage());
            return back()->with('error', 'Something went wrong! Try again.');
        }
    }

    public function permissionUpdate(Request $request, $id)
    {
        $this->authorize(DeveloperEnum::Developer);

        try {
            $permission = SpatiePermission::findOrFail($id);

            $data = $request->validate([
                'name'  => ['required', 'string', 'max:60', Rule::unique('permissions', 'name')->ignore($permission->id)],
                'label' => ['nullable', 'string', 'max:60'],
            ]);
            $data['guard'] = 'web';

            $permission->update($data);
            $this->attachToCoreRoles($permission);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            Log::info("Permission updated by user ID " . auth()->id());

            return redirect()
                ->route('admin.roles.permissions.index')
                ->with('success', 'Permission updated.');
        } catch (Exception $e) {
            Log::error("Permission update failed by user ID " . auth()->id() . ": " . $e->getMessage());
            return back()->with('error', 'Something went wrong! Try again.');
        }
    }

    protected function attachToCoreRoles(SpatiePermission $permission): void
    {
        foreach (['Administrator', 'Developer'] as $roleName) {
            if ($role = Role::where('name', $roleName)->first()) {
                $role->givePermissionTo($permission);
            }
        }
    }
    public function permissionsDestroy($id)
    {
        $this->authorize(DeveloperEnum::Developer);

        try {
            $permission = Permission::findOrFail($id);
            $permission->roles()->detach();
            $permission->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            Log::info("Permission deleted by user ID " . auth()->id());

            return redirect()
                ->route('admin.roles.permissions.index')
                ->with('success', 'Permission deleted successfully.');
        } catch (Exception $e) {
            Log::error("Permission delete failed by user ID " . auth()->id() . ": " . $e->getMessage());
            return back()->with('error', 'Something went wrong! Try again.');
        }
    }
}
