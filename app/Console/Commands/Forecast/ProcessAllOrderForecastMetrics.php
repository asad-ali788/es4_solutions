<?php

namespace App\Console\Commands\Forecast;

use Illuminate\Console\Command;
use App\Jobs\Forecast\ProcessOrderForecastMetricsJob;

class ProcessAllOrderForecastMetrics extends Command
{
    protected $signature = 'app:process-forecast-metrics';
    protected $description = 'Process ASP, Sold, ACOS, TACOS metrics at SKU level in batches';

    public function handle()
    {
        ProcessOrderForecastMetricsJob::dispatchSync();
        $this->info("🚀 Dispatched single job to process all SKUs.");
    }
}
