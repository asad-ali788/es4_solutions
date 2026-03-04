<?php

namespace App\Console\Commands\Forecast;

use Illuminate\Console\Command;
use App\Jobs\Forecast\ProcessOrderForecastMetricsAsinJob;

class ProcessAllOrderForecastMetricsAsin extends Command
{
    protected $signature = 'app:process-forecast-metrics-asin';
    protected $description = 'Process ASIN-level forecast metrics using monthly aggregated data';

    public function handle()
    {
        ProcessOrderForecastMetricsAsinJob::dispatchSync();
        $this->info("🚀 ASIN forecast job completed.");
    }
}
