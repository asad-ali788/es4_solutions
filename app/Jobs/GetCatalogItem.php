<?php

namespace App\Jobs;

use App\Models\ProductAdditionalDetail;
use App\Models\ProductAsins;
use App\Models\ProductContainerInfo;
use App\Models\ProductListing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable as BusDispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use SellingPartnerApi\Seller\SellerConnector;
use Saloon\Exceptions\Request\Statuses\NotFoundException;

class GetCatalogItem implements ShouldQueue
{
    use BusDispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $connector = app(SellerConnector::class);
        $itemApi = $connector->catalogItemsV20220401();
        Log::channel('spApi')->info('✅ GetCatalogItem Started');
        $asins   = ProductAsins::where('catalog_item_status',true)->chunk(100, function ($asins) use ($itemApi) {
            foreach ($asins as $row) {
                $productId      = $row->product_id;
                $asin           = $row->asin1;
                $marketplaceIds = config('marketplaces.marketplace_ids');
                echo "📦 ASIN: {$asin}\n";
                foreach ($marketplaceIds as $marketplaceId) {
                    try {
                        $response  = $itemApi->getCatalogItem(
                            $asin,         // ASIN
                            [$marketplaceId],   // Marketplace ID(s)
                            ['summaries', 'attributes', 'images', 'productTypes'], // Optional: includedData
                        );
                        $data = $response->json();
                        $attributes              = $data['attributes'] ?? [];
                        $bullets                 = $attributes['bullet_point'] ?? [];
                        // Get up to 5 bullet points
                        $mappedValues = [];

                        foreach (range(0, 4) as $i) {
                            $mappedValues["bullet_point_" . ($i + 1)] = $bullets[$i]['value'] ?? null;
                        }
                        $mappedValues['title_amazon'] = $attributes['item_name'][0]['value'] ?? null;
                        //  pdate Product Listing
                        $listing = ProductListing::updateOrCreate(
                            [
                                'products_id' => $productId,
                            ],
                            $mappedValues
                        );
                        // update the container info table
                        $item_dimensions         = $attributes['item_dimensions'][0] ?? [];
                        $item_package_dimensions = $attributes['item_package_dimensions'][0] ?? [];

                        ProductContainerInfo::updateOrCreate(
                            [
                                'product_listings_id' => $listing->id,
                            ],
                            [
                                'item_size_length_cm' => $this->normalizeToCm($item_dimensions['length'] ?? null),
                                'item_size_width_cm'  => $this->normalizeToCm($item_dimensions['width'] ?? null),
                                'item_size_height_cm' => $this->normalizeToCm($item_dimensions['height'] ?? null),

                                'ctn_size_length_cm' => $this->normalizeToCm($item_package_dimensions['length'] ?? null),
                                'ctn_size_width_cm'  => $this->normalizeToCm($item_package_dimensions['width'] ?? null),
                                'ctn_size_height_cm' => $this->normalizeToCm($item_package_dimensions['height'] ?? null),
                            ]
                        );

                        // Update images from the api
                        $imageList = $data['images'][0]['images'] ?? [];
                        $imageUrls = $this->extractTopVariantImages($imageList);
                        ProductAdditionalDetail::updateOrCreate(
                            [
                                'product_listings_id' => $listing->id,
                            ],
                            [
                                'image1' => $imageUrls[0] ?? null,
                                'image2' => $imageUrls[1] ?? null,
                                'image3' => $imageUrls[2] ?? null,
                                'image4' => $imageUrls[3] ?? null,
                                'image5' => $imageUrls[4] ?? null,
                                'image6' => $imageUrls[5] ?? null,
                            ]
                        );
                        // save the catalog_item_status as ture to track of updated products
                        $row->catalog_item_status = true;
                        $row->updated_at = now();
                        $row->save();

                        echo "⏳ Sleeping to respect API rate limits...\n";
                        sleep(5);
                    } catch (NotFoundException $e) {
                        // Log::channel('spApi')->warning("❌Get Catalog Item - ASIN not found: {$asin} — Skipping.");
                        echo "❌ ASIN not found: {$e->getMessage()} — Skipping.\n";
                        sleep(1);
                        continue;
                    } catch (\Throwable $e) {
                        Log::channel('spApi')->error("🔥Get Catalog Item - Failed to sync ASIN {$asin}: {$e->getMessage()}");
                        echo "🔥 Error syncing ASIN {$asin}: {$e->getMessage()}\n";
                        sleep(10);
                        continue;
                    }
                }
            }
        });
        Log::channel('spApi')->info('✅ GetCatalogItem Completed');
    }

    public function normalizeToCm(?array $dimension): ?float
    {
        if (!isset($dimension['value'], $dimension['unit'])) {
            return null;
        }

        return match (strtolower($dimension['unit'])) {
            'inches'      => round($dimension['value'] * 2.54, 2),
            'centimeters' => round($dimension['value'], 2),
            default       => null,
        };
    }

    /**
     * Extracts top variant images (one per variant, highest resolution) and returns up to 6 image URLs.
     *
     * @param array $imagesData Raw image data from Amazon SP API.
     * @return array Ordered array of up to 6 image URLs [image1, image2, ..., image6].
     */
    public function extractTopVariantImages(array $images): array
    {
        $grouped = [];

        foreach ($images as $img) {
            $variant = $img['variant'] ?? null;
            if (!$variant || empty($img['link'])) {
                continue;
            }

            // If we already have an image, compare size
            if (!isset($grouped[$variant])) {
                $grouped[$variant] = $img;
            } else {
                $existing = $grouped[$variant];
                $existingSize = ($existing['width'] ?? 0) * ($existing['height'] ?? 0);
                $currentSize  = ($img['width'] ?? 0) * ($img['height'] ?? 0);

                if ($currentSize > $existingSize) {
                    $grouped[$variant] = $img;
                }
            }
        }

        // Return up to 6 image URLs sorted by variant key
        return array_values(array_map(fn($img) => $img['link'] ?? null, array_slice($grouped, 0, 6)));
    }
}
