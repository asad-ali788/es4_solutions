<?php

namespace App\Listeners;

use App\Models\UserLoginLogs;
use Illuminate\Auth\Events\Failed;
use Jenssegers\Agent\Agent;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        // Unknown user → don't save
        if (!$event->user) {
            return;
        }

        $request = request();

        $agent = new Agent();
        $agent->setUserAgent($request->userAgent());

        $ip        = $request->ip();
        $ua        = $request->userAgent();
        $sessionId = $request->session()->getId();
        $userId    = $event->user->id;

        /**
         * Prevent duplicate failed logs
         */
        $exists = UserLoginLogs::where('is_success', false)
            ->where('user_id', $userId)
            ->where('ip_address', $ip)
            ->where('user_agent', $ua)
            ->where('logged_in_at', '>=', now()->subSeconds(10))
            ->exists();

        if ($exists) {
            return;
        }

        UserLoginLogs::create([
            'user_id'      => $userId,
            'ip_address'   => $ip,
            'user_agent'   => $ua,
            'session_id'   => $sessionId,
            'browser'      => $agent->browser() ?: null,
            'platform'     => $agent->platform() ?: null,
            'logged_in_at' => now(),
            'is_success'   => false,
        ]);
    }
}
