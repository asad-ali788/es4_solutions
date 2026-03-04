<?php

namespace App\Console\Commands\Forecast;

use Illuminate\Console\Command;
use App\Jobs\Forecast\ProcessDailySkuAsinMetrics;

class SyncDailyAdsProductPerformance extends Command
{
    protected $signature = 'app:sync-daily-ads-product-performance {date?}';
    protected $description = 'Aggregate DAILY SKU + ASIN Ads performance';

    public function handle()
    {
        $date = $this->argument('date') ?? now()->subDay()->toDateString();
        
        $this->info("📤 Dispatching DAILY job for {$date}");

        ProcessDailySkuAsinMetrics::dispatch($date);

        $this->info('✅ Daily aggregation job dispatched');
    }
}
