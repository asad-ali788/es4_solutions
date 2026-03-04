<?php

namespace App\Providers;

use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Logout::class => [
            \App\Listeners\LogLogout::class,
        ],
        Failed::class => [
            \App\Listeners\LogFailedLogin::class,
        ],
    ];
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
