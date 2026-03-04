<?php

namespace App\Jobs\Ads;

use App\Models\AmzKeywordRecommendation;
use App\Models\AmzAdsKeywords;
use App\Models\AmzAdsKeywordSb;
use App\Services\Api\AmazonAdsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\AmzPerformanceChangeLog;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;

class AmzKeywordPerformanceUpdatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $userId;

    public function __construct($userId = null)
    {

        $this->userId = $userId;
    }
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $amazonAdsService = app(AmazonAdsService::class);

            $pendingUpdates = AmzKeywordRecommendation::where('run_status', 'dispatched')->get();

            foreach ($pendingUpdates as $pendingUpdate) {

                // Determine profile ID
                $profileId = match (strtoupper($pendingUpdate->country)) {
                    'US' => config('amazon_ads.profiles.US'),
                    'CA' => config('amazon_ads.profiles.CA'),
                    default => throw new Exception("Unhandled country: {$pendingUpdate->country}"),
                };

                // Choose bid priority: manual > ai_suggested > suggested
                $bid = $pendingUpdate->manual_bid ?? $pendingUpdate->ai_suggested_bid ?? $pendingUpdate->suggested_bid;
                if ($bid === null) {
                    Log::warning("⚠️ No valid bid found for keyword {$pendingUpdate->keyword_id}, skipping.");
                    $pendingUpdate->update(['run_status' => 'failed']);
                    continue;
                }

                // Capture old bid from pendingUpdate, fallback to keyword if needed
                $oldBid = $pendingUpdate->bid ?? null;
                if (!$oldBid) {
                    // If no old_bid in pending update, fallback to keyword table
                    $keyword = strtoupper($pendingUpdate->campaign_types) === 'SP'
                        ? AmzAdsKeywords::where('country', $pendingUpdate->country)
                        ->where('keyword_id', $pendingUpdate->keyword_id)
                        ->first()
                        : AmzAdsKeywordSb::where('country', $pendingUpdate->country)
                        ->where('keyword_id', $pendingUpdate->keyword_id)
                        ->first();

                    $oldBid = $keyword ? $keyword->bid : null; // Fallback to keyword's bid
                }

                // Sponsored Products (SP)
                if (strtoupper($pendingUpdate->campaign_types) === 'SP') {
                    $payload = [
                        'keywords' => [[
                            'keywordId' => $pendingUpdate->keyword_id,
                            'bid'       => (float) $bid,
                            'state'     => 'ENABLED',
                        ]],
                    ];

                    $response = $amazonAdsService->updateKeywords($payload, $profileId);

                    if (!empty($response) && ($response['success'] ?? false) === true) {
                        // Update old_bid only in the AmzKeywordRecommendation table
                        $pendingUpdate->update(['run_status' => 'done', 'old_bid' => $oldBid, 'bid' => $bid]);

                        $keyword = AmzAdsKeywords::where('country', $pendingUpdate->country)
                            ->where('keyword_id', $pendingUpdate->keyword_id)
                            ->first();

                        if ($keyword) {
                            $keyword->update([
                                'bid'           => $bid,
                                'keyword_state' => 'enabled',
                                'updated_at'    => now(),
                            ]);
                        } else {
                            Log::warning("⚠️ SP Keyword {$pendingUpdate->keyword_id} not found in amz_ads_keywords.");
                        }

                        AmzPerformanceChangeLog::create([
                            'change_type' => 'keyword',
                            'campaign_id' => (int)($pendingUpdate->campaign_id ?? 0),
                            'keyword_id'  => (int)$pendingUpdate->keyword_id,
                            'target_id'   => null,
                            'country'     => $pendingUpdate->country,
                            'old_value'   => $oldBid,
                            'new_value'   => $bid,
                            'type'        => 'SP', // or use strtoupper($pendingUpdate->campaign_types)
                            'user_id'     => $this->userId,
                            'executed_at' => now(),
                            'date'        => Carbon::parse($pendingUpdate->date ?? now())->toDateString(),
                        ]);
                    } else {
                        $pendingUpdate->update(['run_status' => 'failed']);
                        Log::error("❌ Failed to update SP keyword {$pendingUpdate->keyword_id}", $response ?? []);
                    }
                }

                // Sponsored Brands (SB)
                elseif (strtoupper($pendingUpdate->campaign_types) === 'SB') {
                    $adGroup = DB::table('amz_ads_keyword_performance_report_sb')
                        ->select('ad_group_id')
                        ->where('keyword_id', $pendingUpdate->keyword_id)
                        ->orderByDesc('c_date')
                        ->first();

                    if (!$adGroup) {
                        Log::warning("⚠️ No ad_group_id found for SB keyword {$pendingUpdate->keyword_id}");
                        $pendingUpdate->update(['run_status' => 'failed']);
                        continue;
                    }

                    $payload = [[
                        'keywordId'  => (string) $pendingUpdate->keyword_id,
                        'adGroupId'  => (string) $adGroup->ad_group_id,
                        'state'      => 'enabled',
                        'bid'        => (float) $bid,
                    ]];

                    $response = $amazonAdsService->updateSBKeywords($payload, $profileId);

                    if (!empty($response) && ($response['success'] ?? false) === true) {
                        $pendingUpdate->update(['old_bid' => $oldBid, 'run_status' => 'done', 'bid' => $bid]);

                        $keyword = AmzAdsKeywordSb::where('country', $pendingUpdate->country)
                            ->where('keyword_id', $pendingUpdate->keyword_id)
                            ->first();

                        if ($keyword) {
                            $keyword->update([
                                'bid'           => $bid,
                                'keyword_state' => 'enabled',
                                'updated_at'    => now(),
                            ]);
                        } else {
                            Log::warning("⚠️ SB Keyword {$pendingUpdate->keyword_id} not found in amz_ads_keyword_sb.");
                        }

                        AmzPerformanceChangeLog::create([
                            'change_type' => 'keyword',
                            'campaign_id' => (int)($pendingUpdate->campaign_id ?? 0),
                            'keyword_id'  => (int)$pendingUpdate->keyword_id,
                            'target_id'   => null,
                            'country'     => $pendingUpdate->country,
                            'old_value'   => $oldBid,
                            'new_value'   => $bid,
                            'type'        => 'SB',
                            'user_id'     => $this->userId,
                            'executed_at' => now(),
                            'date'        => Carbon::parse($pendingUpdate->date ?? now())->toDateString(),
                        ]);
                    } else {
                        $pendingUpdate->update(['run_status' => 'failed']);
                        Log::error("❌ Failed to update SB keyword {$pendingUpdate->keyword_id}", $response ?? []);
                    }
                }

                sleep(2); // prevent API rate limit
            }
        } catch (Exception $e) {
            Log::error("❌ Exception in AmzKeywordPerformanceUpdatesJob: {$e->getMessage()}");
        }
    }
}
