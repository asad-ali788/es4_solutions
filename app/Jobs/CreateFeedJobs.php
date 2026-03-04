<?php

namespace App\Jobs;

use App\Models\FeedLog;
use App\Models\PriceUpdateQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable as BusDispatchable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use SellingPartnerApi\Seller\SellerConnector;
use SellingPartnerApi\Seller\ListingsItemsV20210801\Dto\ListingsItemPatchRequest;
use SellingPartnerApi\Seller\ListingsItemsV20210801\Dto\PatchOperation;

class CreateFeedJobs implements ShouldQueue
{
    use BusDispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected SellerConnector $connector
    ) {}

    /**
     * Execute the job.
     * NOTE: Kept the same signature so your dispatch code doesn’t break,
     * but we don’t need $feedType/$marketplaceIds anymore.
     */
    public function handle($marketplaceIds = null)
    {
        $pendingItems = PriceUpdateQueue::where('status', 'submitted')->get();

        if ($pendingItems->isEmpty()) {
            // return '🚫 No submitted items in queue for Listings PATCH';
            return;
        }

        $sellerId    = config('services.spapi.key');

        $listingsApi = $this->connector->listingsItemsV20210801();

        $processed = 0;
        foreach ($pendingItems as $item) {
            // Default/fallbacks
            $mp       = $item->marketplace_id ?: (is_array($marketplaceIds) ? $marketplaceIds[0] : 'ATVPDKIKX0DER');
            $currency = $item->currency ?: 'USD';
            $price    = (float) $item->new_price;

            // Ensure Amazon’s rule: end time > start time
            $now     = now();
            $startAt = $item->start_at ? Carbon::parse($item->start_at) : $now;
            $endAt   = $item->end_at && Carbon::parse($item->end_at)->gt($startAt)
                ? Carbon::parse($item->end_at)
                : (clone $startAt)->addDays(7);

            try {
                // Build the exact shape that worked for you:
                // discounted_price -> schedule [start_at, end_at], price alongside schedule
                $patches = [
                    new PatchOperation(
                        'replace',
                        '/attributes/purchasable_offer',
                        [[
                            'marketplace_id'   => $mp,
                            'audience'         => 'ALL',
                            'currency'         => $currency,
                            'discounted_price' => [[
                                'schedule'       => [[
                                    'start_at'       => $startAt->toIso8601String(),
                                    'end_at'         => $endAt->toIso8601String(),
                                    'value_with_tax' => $price,
                                ]],
                            ]],
                        ]]
                    ),
                ];

                $body = new ListingsItemPatchRequest('PRODUCT', $patches);

                $resp = $listingsApi->patchListingsItem(
                    $sellerId,
                    $item->sku,
                    $body,
                    [$mp]
                );

                $json   = $resp->json();
                $status = strtoupper((string)($json['status'] ?? ''));
                
                if ($status === 'ACCEPTED' || empty($json['issues'] ?? [])) {
                    $item->update([
                        'status'      => 'success',
                        'feed_id'     => null,
                        'reference'  => json_encode($json, JSON_PRETTY_PRINT),
                        'processed_at' => now(),
                    ]);
                    $processed++;
                    Log::info("✅ Listings PATCH accepted for {$item->sku} @ {$mp} = {$price} {$currency}");
                } else {
                    $item->update([
                        'status'     => 'failed',
                        'reference' => json_encode($json['issues'], JSON_PRETTY_PRINT),
                        'attempts'   => ($item->attempts ?? 0) + 1,
                    ]);

                    Log::warning("❌ INVALID for {$item->sku} @ {$mp}", ['issues' => $json['issues'] ?? $json]);
                }
            } catch (\Throwable $e) {
                $item->update([
                    'status'     => 'failed',
                    'reference' => $e->getMessage(),
                    'attempts'   => ($item->attempts ?? 0) + 1,
                ]);

                Log::error("💥 PATCH error for {$item->sku} @ {$mp}: {$e->getMessage()}");
            }

            // Throttle a bit if you want (uncomment to be safe)
            usleep(400000); // 400ms
        }

        return "✅ Listings PATCH submitted for {$processed} items.";
    }
}
