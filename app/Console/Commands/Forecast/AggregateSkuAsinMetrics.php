<?php

namespace App\Console\Commands\Forecast;

use Illuminate\Console\Command;
use App\Jobs\Forecast\ProcessMonthlySkuAsinMetrics;

class AggregateSkuAsinMetrics extends Command
{
    protected $signature = 'app:aggregate-sku-asin-metrics {month?}';
    protected $description = 'Dispatch job to aggregate SKU + ASIN Ads monthly performance';

    public function handle()
    {
        $month = $this->argument('month') ?? now()->subMonth()->format('Y-m');

        $this->info("📤 Dispatching MONTHLY job for {$month}");

        ProcessMonthlySkuAsinMetrics::dispatch($month);

        $this->info('✅ Monthly aggregation job dispatched');
    }
}
