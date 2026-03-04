<?php

namespace App\Console\Commands\Forecast;

use Illuminate\Console\Command;
use App\Jobs\Forecast\ProcessSubMonthForecastMetricsJob;

class ProcessSubMonthForecastMetrics extends Command
{
    protected $signature = 'app:process-forecast-sub-month';
    protected $description = 'Update forecast metrics for SKU (sub-month only)';

    public function handle()
    {
        ProcessSubMonthForecastMetricsJob::dispatch();
        $this->info("🚀 Dispatched job to process SKU-level sub-month forecast.");
    }
}
