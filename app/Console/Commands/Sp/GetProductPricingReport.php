<?php

namespace App\Console\Commands\Sp;

use Illuminate\Console\Command;
use App\Models\ProductAsins;
use App\Jobs\ProcessProductPricing;
use Illuminate\Support\Facades\Log;

class GetProductPricingReport extends Command
{
    protected $signature = 'app:get-product-pricing-report';
    protected $description = 'Fetch product pricing Report';

    // How many ASINs per queued job (1500 / 300 = 5 jobs per marketplace)
    private const DISPATCH_CHUNK_SIZE = 300;

    public function handle()
    {
        Log::channel('spApi')->info('✅ GetProductPricingReport Started.');

        $marketplaceIds = config('marketplaces.marketplace_ids');
        // e.g. ['US' => 'ATVPDKIKX0DER', 'CA' => 'A2EUQ1WTGCTBG2', 'MX' => 'A1AM78C64UM0Y8']

        // asin1 only
        $allAsins = ProductAsins::pluck('asin1')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($allAsins)) {
            $this->warn("⚠️ No ASINs found in ProductAsins table.");
            Log::channel('spApi')->warning('⚠️ No ASINs found in ProductAsins table.');
            return Command::SUCCESS;
        }

        $totalAsins = count($allAsins);
        $this->info("Found {$totalAsins} unique ASINs to process.");

        foreach ($marketplaceIds as $country => $marketplaceId) {

            $chunkIndex = 0;

            foreach (array_chunk($allAsins, self::DISPATCH_CHUNK_SIZE) as $chunk) {
                $chunkIndex++;

                $this->info("▶️  Running pricing job sync for {$country}, chunk #{$chunkIndex} (" . count($chunk) . " ASINs)");

                // For CLI testing: run synchronously so you see all output
                ProcessProductPricing::dispatch($marketplaceId, $country, $chunk)->onQueue('long-running');

                $msg = "✅ Finished pricing job for {$country}, chunk #{$chunkIndex}";
                $this->info($msg);
                Log::channel('spApi')->info($msg);
            }
        }

        Log::channel('spApi')->info('✅ GetProductPricingReport Completed (all jobs dispatched).');
        $this->info("🎉 All pricing jobs completed (sync run).");

        return Command::SUCCESS;
    }
}
