<?php

namespace App\Console\Commands\Forecast;

use Illuminate\Console\Command;
use App\Jobs\Forecast\ProcessSubMonthForecastMetricsAsinJob;

class ProcessSubMonthForecastMetricsAsin extends Command
{
    protected $signature = 'app:process-sub-month-forecast-metrics-asin';
    protected $description = 'Update forecast metrics for ASINs (sub-month only)';

    public function handle()
    {
        ProcessSubMonthForecastMetricsAsinJob::dispatch();
        $this->info("🚀 Dispatched job to process ASIN-level sub-month forecast.");
    }
}
