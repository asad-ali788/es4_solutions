<?php

namespace App\Jobs;

use App\Models\ProductListing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SellingPartnerApi\Seller\SellerConnector;
use SellingPartnerApi\Seller\ListingsItemsV20210801\Dto\ListingsItemPatchRequest;
use SellingPartnerApi\Seller\ListingsItemsV20210801\Dto\PatchOperation;

class ProductUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $listingId) {}

    // Let the container inject the connector here:
    public function handle(SellerConnector $connector): void
    {
        $listing = ProductListing::with('product')->find($this->listingId);

        if (!$listing) {
            Log::warning("ProductUpdateJob: Listing not found [ID={$this->listingId}]");
            return;
        }

        // Skip if nothing flagged
        if (
            !$listing->title_change_status &&
            !$listing->bullets_change_status &&
            !$listing->description_change_status
        ) {
            Log::info("ProductUpdateJob: Nothing to sync for listing {$listing->id}");
            return;
        }

        // Resolve seller / marketplace / language
        $sellerId  = config('services.spapi.key');
        $sku       = $listing->product->sku;
        $mp        = config('marketplaces.marketplace_ids.'.$listing->country);
        $language  = 'en_US'; // en_US default

        $patches = [];

        // Title → item_name
        if ($listing->title_change_status && !empty($listing->title_amazon)) {
            $patches[] = new PatchOperation(
                'replace',
                '/attributes/item_name',
                [[
                    'value'    => Str::limit($listing->title_amazon, 200, ''),
                    'language' => $language,
                ]]
            );
        }

        // Bullets → bullet_point
        if ($listing->bullets_change_status) {
            $bullets = collect([
                $listing->bullet_point_1,
                $listing->bullet_point_2,
                $listing->bullet_point_3,
                $listing->bullet_point_4,
                $listing->bullet_point_5,
            ])->filter(fn($v) => filled($v))
                ->map(fn($v) => ['value' => Str::limit($v, 500, ''), 'language' => $language])
                ->values()->all();

            if (!empty($bullets)) {
                $patches[] = new PatchOperation(
                    'replace',
                    '/attributes/bullet_point',
                    $bullets
                );
            }
        }

        // Description → description
        if ($listing->description_change_status) {
            $desc = strip_tags($listing->description); // Amazon prefers plain text
            $patches[] = new PatchOperation(
                'replace',
                '/attributes/product_description',
                [[
                    'value'    => Str::limit($desc, 2000, ''), // keep sane
                    'language' => $language,
                ]]
            );
        }

        if (empty($patches)) {
            Log::info("ProductUpdateJob: No valid patches for listing {$listing->id}");
            return;
        }

        try {
            $listingsApi = $connector->listingsItemsV20210801();

            $body = new ListingsItemPatchRequest('PRODUCT', $patches);
            $resp  = $listingsApi->patchListingsItem($sellerId, $sku, $body, [$mp]);

            $json  = method_exists($resp, 'json') ? $resp->json() : json_decode((string)$resp, true);
            $status = strtoupper((string)($json['status'] ?? ''));

            if ($status === 'ACCEPTED' || empty($json['issues'] ?? [])) {
                // Clear flags & mark clean
                $listing->update([
                    'title_change_status'       => false,
                    'bullets_change_status'     => false,
                    'description_change_status' => false,
                    'sync_status'               => 'clean',
                ]);

                Log::info("✅ Listings PATCH accepted for {$sku} @ {$mp}");
            } else {
                Log::warning("⚠️ Listings PATCH issues for {$sku} @ {$mp}", ['resp' => $json]);
            }
        } catch (\Throwable $e) {
            Log::error("❌ ProductUpdateJob failed for {$sku} @ {$mp}: " . $e->getMessage(), [
                'listing_id' => $listing->id,
            ]);
        }
    }

}
