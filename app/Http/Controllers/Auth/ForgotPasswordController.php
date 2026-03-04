<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\Auth\ForgotPassword as ForgotPasswordMail;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ForgotPasswordController extends Controller
{
    // Step 1: show email form
    public function index()
    {
        return view('auth.forgotPasswordEmail');
    }

    // Step 2: verify email & send OTP
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Email not found.']);
        }

        // Generate OTP (A–Z + 0–9)
        $otp = strtoupper(Str::random(6));

        $user->reset_otp = $otp;
        $user->reset_otp_expires_at = Carbon::now()->addMinutes(10);
        $user->save();

        $sent = false;
        $errorMessage = null;

        try {
            // attempt send
            Mail::to($user->email)->send(new ForgotPasswordMail($user, $otp));
            $sent = true;

            Log::info('📧 OTP mail accepted by transport', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'time'    => now()->toDateTimeString(),
            ]);
        } catch (TransportExceptionInterface $e) {
            $errorMessage = $e->getMessage();
            Log::error('📧 OTP mail transport error', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'error'   => $errorMessage,
            ]);
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
            Log::error('📧 OTP mail unexpected error', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'error'   => $errorMessage,
            ]);
        }

        // Redirect to OTP page with clear feedback
        return redirect()
            ->route('password.verify.otp.form', ['email' => $user->email])
            ->with($sent ? 'status' : 'mail_error', $sent
                ? 'OTP sent to your email.'
                : 'We could not send the OTP email. Please try again or contact support.');
    }


    // Step 3: show OTP form
    public function showVerifyOtpForm($email)
    {
        return view('auth.verifyOtp', compact('email'));
    }

    // Step 4: verify OTP and generate token
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)
            ->where('reset_otp', $request->otp)
            ->where('reset_otp_expires_at', '>=', now())
            ->first();

        if (!$user) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP.']);
        }

        // OTP verified → generate secure token
        $token = hash('sha256', Str::random(60));
        $user->reset_token = $token;
        $user->reset_token_expires_at = Carbon::now()->addMinutes(15);
        $user->reset_otp = null; // clear OTP
        $user->reset_otp_expires_at = null;
        $user->save();

        return redirect()->route('password.change.form', ['token' => $token])
            ->with('status', '📧 OTP verified successfully. You can now change your password.');
    }

    // Step 5: show password change form (secured)
    public function showChangePasswordForm($token)
    {
        $user = User::where('reset_token', $token)
            ->where('reset_token_expires_at', '>=', now())
            ->first();

        if (!$user) {
            abort(403, 'Invalid or expired password reset link.');
        }

        return view('auth.changePassword', compact('token'));
    }

    // Step 6: update password securely
    public function updatePassword(Request $request, $token)
    {
        $request->validate([
            'password' => 'required|confirmed|min:8',
        ]);

        $user = User::where('reset_token', $token)
            ->where('reset_token_expires_at', '>=', now())
            ->first();

        if (!$user) {
            return back()->withErrors(['token' => 'Invalid or expired token.']);
        }

        $user->password = bcrypt($request->password);
        $user->reset_token = null;
        $user->reset_token_expires_at = null;
        $user->save();

        return redirect()->route('login')->with('status', 'Password changed successfully. Please log in.');
    }
}
