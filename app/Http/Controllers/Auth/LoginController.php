<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Models\UserLoginLogs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Jenssegers\Agent\Agent;

class LoginController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        // Check if user exists regardless of status
        $user = User::where('email', $credentials['email'])->first();

        if ($user && !$user->status) {
            return back()->withErrors([
                'email' => 'Your account is no longer active. Please contact the admin.',
            ])->onlyInput('email');
        }
        // If user exists but NOT verified -> send password setup/reset link and stop
        if ($user && is_null($user->email_verified_at)) {
            $status = Password::sendResetLink(['email' => $user->email]);

            if ($status === Password::RESET_LINK_SENT) {
                return redirect()->route('password.notice')
                    ->with('email', $user->email)
                    ->with('status', 'Reset link sent successfully');
            }

            return back()->withErrors(['email' => __($status)])->onlyInput('email');
        }
        // Now attempt login only if active
        $credentials['status'] = 1;
        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            $this->userLoginLog($request);
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function passwordNotice()
    {
        $email = session('email');

        if (!$email) {
            return redirect()->route('login');
        }

        return view('auth.passwordNotice', compact('email'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login')->with('success', 'You’ve been logged out. See you soon! 👋');
    }

    public function passwordReset($token)
    {
        return view('auth.resetPassword', compact('token'));
    }
    /**
     * Handle the password update.
     */
    public function passwordStore(Request $request)
    {
        // Validate form data
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|confirmed|min:8',
            'token' => 'required'
        ]);

        // Try to reset password using Laravel’s Password Broker
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'email_verified_at' => $user->email_verified_at ?? now(), // mark verified if not yet
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('status', __($status));
        }

        return back()->withErrors(['email' => __($status)]);
    }
    // for showing the user name in the login page
    public function checkEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email']
        ]);
        $user = User::where('email', $request->email)->first();
        if ($request->isMethod('post')) {
            return response()->json([
                'exists' => (bool) $user,
                'name'   => $user?->name
            ]);
        }
        if ($user) {
            Auth::login($user);
            return response()->json([
                'exists' => true,
                'user'   => $user->name
            ]);
        }
        return response()->json(['exists' => false]);
    }

    public function userLoginLog($request)
    {
        $agent = new Agent();
        $agent->setUserAgent($request->userAgent());

        UserLoginLogs::firstOrCreate(
            [
                'user_id'    => Auth::id(),
                'session_id' => $request->session()->getId(),
            ],
            [
                'ip_address'   => $request->ip(),
                'user_agent'   => $request->userAgent(),
                'browser'      => $agent->browser(),
                'platform'     => $agent->platform(),
                'logged_in_at' => now(),
                'is_success'   => true,
            ]
        );
    }

    public function loginHistory(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $logs = UserLoginLogs::query()
            ->with('user')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    // browser/platform search
                    $qq->where('browser', 'like', "%{$search}%")
                        ->orWhere('platform', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%");

                    // user name/email search
                    $qq->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                });
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->appends(['search' => $search]);

        return view('pages.dev.devtools.loginHistory.index', compact('logs', 'search'));
    }

}
