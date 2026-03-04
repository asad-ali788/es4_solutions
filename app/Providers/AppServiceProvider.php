<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('viewPulse', function (User $user) {
            return $user->hasAnyRole(['administrator', 'developer']);
        });
        LogViewer::auth(function ($request) {
            $user = $request->user();

            return $user && $user->hasAnyRole(['administrator', 'developer']);
        });
        if (!env('ALLOW_DESTRUCTIVE_MIGRATIONS', false)) {
            $destructiveCommands = [
                'migrate:fresh',
                'migrate:refresh',
                'migrate:reset',
            ];
            foreach ($destructiveCommands as $command) {
                Artisan::command($command, function () use ($command) {
                    $this->error(str_repeat('=', 80));
                    $this->error(" 🚨  BLOCKED: The command [{$command}] is DISABLED! 🚨 ");
                    $this->error(" Running this command can DESTROY DATA. ");
                    $this->error(" If you really need it, enable ALLOW_DESTRUCTIVE_MIGRATIONS in .env ");
                    $this->error(str_repeat('=', 80));

                    return 1; // exit code non-zero (like a failure)
                });
            }
        }
    }
}
