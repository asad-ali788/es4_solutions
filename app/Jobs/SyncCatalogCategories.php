<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable as BusDispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use App\Models\ProductAsins;
use App\Models\ListCatalogCategories;
use Illuminate\Support\Facades\Log;
use SellingPartnerApi\Seller\SellerConnector;

class SyncCatalogCategories implements ShouldQueue
{
    use BusDispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): string
    {
        Log::channel('spApi')->info('✅ SyncCatalogCategories Started.');
        $connector = app(SellerConnector::class);
        $marketplaceId = 'ATVPDKIKX0DER';
        $itemApi       = $connector->catalogItemsV0();

        //  Get ASINs that are NOT already synced
        $existingAsins = ListCatalogCategories::pluck('asin')->toArray();
        $asinsToSync   = ProductAsins::whereNotIn('asin1', $existingAsins)
            ->pluck('asin1')
            ->filter()
            ->unique();

        foreach ($asinsToSync as $i => $asin) {
            try {
                $response = $itemApi->listCatalogCategories($marketplaceId, $asin, null, null);
                $data     = $response->json();

                if (!empty($data['payload'][0])) {
                    $categoryTree = $data['payload'][0];
                    $catalogCategories = $this->getCatalogCategories($categoryTree);

                    ListCatalogCategories::updateOrCreate(
                        [
                            'marketplace_id' => $marketplaceId,
                            'asin'           => $asin,
                        ],
                        [
                            'catalog_categories' => $catalogCategories,
                            'seller_sku'         => null,
                        ]
                    );

                    // Log::channel('spApi')->info("✅ Synced category for ASIN: {$asin}");
                } else {
                    // Log::channel('spApi')->warning("⚠️ No category found for ASIN: {$asin}");
                }

                // Respect SP-API rate limits: 2 burst, then 1/sec
                if ($i >= 2) {
                    sleep(1);
                }
            } catch (\Throwable $e) {
                Log::channel('spApi')->error("❌ Error syncing ASIN {$asin}: " . $e->getMessage());
                sleep(10);
            }
            echo "📦 Syncing ASIN: {$asin}\n";
        }
        Log::channel('spApi')->info('✅ SyncCatalogCategories completed.');

        return "✅ Catalog category sync job completed.";
    }

    protected function getCatalogCategories(array $category): string
    {
        $names = [];

        while (isset($category['ProductCategoryName'])) {
            $names[] = $category['ProductCategoryName'];
            $category = $category['parent'] ?? [];
        }

        return implode(' -> ', $names);
    }
}
