<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Enum\Permissions\UserEnum;
use App\Models\UserDeniedPermission;
use Exception;
use Illuminate\Support\Facades\DB;

class UserPermissionController extends Controller
{
    /**
     * Display all users with their roles and permission modules.
     */
    public function index(Request $request)
    {
        try {
            $this->authorize(UserEnum::UserPermissions);
            $search = $request->input('search');
            $users = User::query()
                ->with('roles', 'permissions')
                ->when($search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->whereKeyNot(Auth::id())
                ->paginate(10)
                ->withQueryString();
            $permissionLabels = Permission::fetchAllStaticPermissions();
            return view('pages.admin.user.permission.index', [
                'users'            => $users,
                'permissionLabels' => $permissionLabels,
                'search'           => $search,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to load user permissions index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Something went wrong while loading the user Permissions.');
        }
    }

    /**
     * Show edit form for a user’s permissions.
     */
    public function edit($id)
    {
        $this->authorize(UserEnum::UserPermissionUpdate);

        $user             = User::findOrFail($id);
        $roles            = Role::pluck('name', 'id');
        $permissions      = Permission::pluck('name', 'id');
        $permissionLabels = Permission::fetchAllStaticPermissions();
        return view('pages.admin.user.permission.form', [
            'user'              => $user,
            'roles'             => $roles,
            'permissions'       => $permissions,
            'permissionLabels'  => $permissionLabels,
            'isCreateMode'      => false,
        ]);
    }

    /**
     * Update a user’s permission assignments.
     */
    public function update(Request $request, User $user)
    {
        $this->authorize(UserEnum::UserPermissionUpdate);

        $validated = $request->validate([
            'permissions' => 'array',
        ]);
        try {
            $allowed = array_values(array_unique($validated['permissions'] ?? []));
            // What roles already give
            $roleGranted = $user->getPermissionsViaRoles()->pluck('name')->all();

            // Direct we should keep: allowed − roleGranted
            $directShould = array_values(array_diff($allowed, $roleGranted));

            // Deny we should keep: roleGranted − allowed
            $denyShould = array_values(array_diff($roleGranted, $allowed));

            DB::transaction(function () use ($user, $directShould, $denyShould) {
                UserDeniedPermission::where('user_id', $user->id)->delete();
                //Spatie: set user direct perms in one go
                $user->syncPermissions($directShould);

                //Sync the deny table exactly to $denyShould
                $denyIds = Permission::whereIn('name', $denyShould)
                    ->pluck('id')->all();

                // Upsert/restore current denies
                if ($denyIds) {
                    $now = now();
                    $rows = array_map(fn($pid) => [
                        'user_id'       => $user->id,
                        'permission_id' => $pid,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ], $denyIds);

                    DB::table('user_denied_permissions')->insert($rows);
                }
            });
            return redirect()->route('admin.user.permissions.index')->with('success', 'User permissions updated successfully.');
        } catch (Exception $e) {
            Log::error('User permissions updated failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(),]);
            return back()->withInput()->with('error', 'Something went wrong while updating access.');
        }
    }

   
}
