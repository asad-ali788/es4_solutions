<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLoginLogs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver; // or Imagick if installed

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $logs = UserLoginLogs::with('user')->where('user_id', $user->id)
            ->latest('logged_in_at')
            ->limit(50)
            ->paginate(20);

        return view('pages.admin.profile.profile', compact('user', 'logs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . auth()->id(),
            'mobile'        => 'nullable|numeric',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user         = auth()->user();
        $user->name   = $request->name;
        $user->email  = $request->email;
        $user->mobile = $request->mobile;

        if ($request->hasFile('profile_image')) {
            $image    = $request->file('profile_image');
            $filename = uniqid() . '.' . $image->getClientOriginalExtension();

            // Create ImageManager instance with GD driver
            $manager = new ImageManager(new Driver());

            // Read, resize, compress
            $resized = $manager->read($image)
                ->cover(300, 300)   // crop/resize
                ->encodeByExtension($image->getClientOriginalExtension(), quality: 75);

            // Save to storage/app/public/profile_images
            Storage::disk('public')->put("profile_images/{$filename}", (string) $resized);

            // Update DB
            $user->profile = "profile_images/{$filename}";
            $user->save();
        }
        $user->save();

        return back()->with('success', 'Profile updated successfully!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'confirmed', 'min:8'],
        ]);

        $user = Auth::user();
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }
        $user->password = Hash::make($request->password);
        $user->save();

        return back()->with('success', 'Password updated successfully.');
    }

    public function destroy($id)
    {
        $user          = User::where('id', $id)->first();
        $user->profile = null;
        $user->save();
        return back()->with('success', 'Profile Deleted successfully.');
    }
}
