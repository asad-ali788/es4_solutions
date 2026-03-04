<?php

namespace App\Console\Commands\Sp;

use Illuminate\Console\Command;
use App\Models\ProductAsins;
use App\Jobs\ProcessCompetitivePricing;
use Illuminate\Support\Facades\Log;

class GetCompetitivePricingReport extends Command
{
    protected $signature = 'app:get-competitive-pricing-report';
    protected $description = 'Dispatch competitive pricing jobs for each Amazon marketplace';

    // how many ASINs per job (1130 -> 300 = 4 jobs per marketplace)
    private const DISPATCH_CHUNK_SIZE = 300;

    public function handle()
    {
        Log::channel('spApi')->info('✅ GetCompetitivePricingReport Started.');

        $marketplaceIds = config('marketplaces.marketplace_ids');

        // If you really need asin1, asin2, asin3:
        $allAsins = ProductAsins::select('asin1', 'asin2', 'asin3')
            ->get()
            ->flatMap(fn($product) => array_filter([
                $product->asin1,
                $product->asin2,
                $product->asin3,
            ]))
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

                $this->info("▶️  Running competitive pricing job SYNC for {$country}, chunk #{$chunkIndex} (" . count($chunk) . " ASINs)");

                ProcessCompetitivePricing::dispatch($marketplaceId, $country, $chunk)->onQueue('long-running');

                $msg = "✅ Finished competitive pricing job for {$country}, chunk #{$chunkIndex}";
                $this->info($msg);
                Log::channel('spApi')->info($msg);
            }
        }

        Log::channel('spApi')->info('✅ GetCompetitivePricingReport Completed.');
        $this->info("🎉 All marketplace jobs dispatched successfully.");

        return Command::SUCCESS;
    }
}
