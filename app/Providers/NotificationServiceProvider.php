<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Notification;
use Auth;
use Illuminate\Support\Facades\Cache;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        View::composer('components.admin.header', function ($view) {
            if (!Auth::check()) {
                Auth::logout();
                return to_route('login');
            }
            $user = Auth::user();
            $cacheKey = 'notifications:' . $user->id;

            [$unreadNotifications, $unreadCount] = Cache::remember($cacheKey, 300, function () use ($user) {
                $query = Notification::where('read_status', 0)
                    ->orderBy('created_date', 'desc');
                // Allow 'administrator' and 'developer' to see all
                if (!$user->hasAnyRole(['administrator', 'developer'])) {
                    $query->where('assigned_user_id', $user->id);
                }
                return [
                    $query->limit(10)->get(),
                    $query->count(),
                ];
            });

            $view->with(compact('unreadNotifications', 'unreadCount'));
        });
    }
}
