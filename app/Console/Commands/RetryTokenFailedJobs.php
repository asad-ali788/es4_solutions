<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class RetryTokenFailedJobs extends Command
{
    protected $signature = 'jobs:retry-token-failed';
    protected $description = 'Retry failed jobs with token refresh error';

    public function handle()
    {
        $pattern = "Exception: Unable to refresh token. 'access_token' not found in response";

        $failedJobIds = DB::table('failed_jobs')
            ->where('exception', 'like', "%{$pattern}%")
            ->pluck('uuid')
            ->toArray();

        if (empty($failedJobIds)) {
            $this->info('No failed jobs found for token refresh error.');
            return;
        }

        foreach ($failedJobIds as $uuid) {
            Artisan::call('queue:retry', ['id' => $uuid]);
            $this->info("Retried job UUID: {$uuid}");
        }

        $this->info('Retry process completed.');
    }
}
