<?php

namespace App\Http\Controllers\Admin;

use App\Enum\Permissions\RoleEnum;
use App\Enum\Permissions\UserEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use App\Models\Role as Roles;
use Exception;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize(UserEnum::User);
        $query = User::with('roles', 'reportingManager')
            ->whereNotIn('id', [auth()->id()]);

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('email', 'like', '%' . $searchTerm . '%');
            });
        }

        $users = $query->paginate($request->input('per_page', 25));
        return view('pages.admin.user.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize(UserEnum::UserCreate);

        $authUser = auth()->user();
        $user = null;

        // Collection, not array_filter
        $roles = Role::query()->orderBy('name')->get(['id', 'name']);

        if (!$authUser->hasAnyRole(['administrator', 'developer', 'md'])) {
            $roles = $roles->reject(fn($r) => in_array(strtolower($r->name), ['administrator', 'developer', 'md']));
        }

        $reporting_to = User::select('id', 'name')->orderBy('name')->get();

        return view('pages.admin.user.users.form', compact('user', 'roles', 'reporting_to'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request)
    {
        $this->authorize(UserEnum::UserCreate);

        // Create user
        $user = User::create([
            'name'         => $request->name,
            'email'        => $request->email,
            'reporting_to' => $request->reporting_to ?? null,
            'password'     => Hash::make('Password@123'), // optionally send a reset link
        ]);

        // Assign role
        $role = Role::findOrFail($request->role_id);
        $user->syncRoles([$role]);

        // Keep role-based permissions only
        $user->syncPermissions([]); // remove if you want to clone role permissions
        Password::sendResetLink(['email' => $user->email]);
        return redirect()->route('admin.users.index')->with('success', 'User created successfully with role permissions.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $this->authorize(UserEnum::UserUpdate);

        $authUser = auth()->user();
        if (
            !$authUser->hasAnyRole(['administrator', 'developer', 'md']) &&
            $user->hasAnyRole(['administrator', 'developer', 'md'])
        ) {
            return back()->with('error', 'You are not authorized to edit this user.');
        }
        // Load roles from Spatie
        $roles = Role::query()->orderBy('name')->get(['id', 'name']);

        // Optionally hide elevated roles for non-privileged editors
        if (!$authUser->hasAnyRole(['administrator', 'developer', 'md'])) {
            $roles = $roles->reject(fn($r) => in_array(strtolower($r->name), ['administrator', 'developer', 'md']));
        }

        $reporting_to = User::select('id', 'name')->orderBy('name')->get();

        return view('pages.admin.user.users.form', compact('user', 'roles', 'reporting_to'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, User $user)
    {
        $this->authorize(UserEnum::UserUpdate);

        try {
            $newRole = Role::findOrFail($request->role_id);
            $oldRoleId = optional($user->roles->first())->id;
            // Update basic fields
            $user->update([
                'name'         => $request->name,
                'email'        => $request->email,
                'reporting_to' => $request->reporting_to ?? null,
            ]);
            $user->syncRoles([$newRole]);
            if ((int) $oldRoleId !== (int) $newRole->id) {
                // Remove any direct user permissions that could conflict with the new role
                $user->syncPermissions([]); // keep this if you want roles-only control
            }
            return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
        } catch (Exception $e) {
            Log::warning($e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'An error occurred while Updating. Please try again.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $this->authorize(UserEnum::UserDelete);

        if (Auth::id() === $user->id) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot delete your own account.');
        }

        if ($user->hasAnyRole(['developer', 'administrator'])) {
            return redirect()->route('admin.users.index')->with('error', 'You cannot delete a Developer or Administrator account.');
        }

        $user->syncRoles([]);
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    public function changeStatus($id)
    {
        $this->authorize(UserEnum::UserDisable);
        $user = User::findOrFail($id);
        $user->status = !$user->status;
        $user->save();

        return redirect()->back()->with('success', 'User status updated successfully.');
    }
}
