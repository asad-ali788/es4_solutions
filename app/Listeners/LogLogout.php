<?php

namespace App\Listeners;

use App\Models\UserLoginLogs;
use Illuminate\Auth\Events\Logout;

class LogLogout
{
    public function handle(Logout $event): void
    {
        $request = request();

        $logId = $request->session()->get('login_log_id');

        if ($logId) {
            UserLoginLogs::where('id', $logId)
                ->whereNull('logged_out_at')
                ->update(['logged_out_at' => now()]);
        }

        // Optionally clear
        $request->session()->forget('login_log_id');
    }
}
