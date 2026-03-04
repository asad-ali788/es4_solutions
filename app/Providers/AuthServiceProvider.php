<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class AuthServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
    }
}
