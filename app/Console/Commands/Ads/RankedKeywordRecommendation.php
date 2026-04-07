<?php

namespace App\Console\Commands\Ads;

use App\Models\AmzAdsKeywords;
use App\Models\AmzCampaigns;
use App\Models\SpKeywordSugByAdgroup;
use App\Services\Api\AmazonAdsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RankedKeywordRecommendation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:ranked-keyword-recommendation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ADS: Fetch and Rank Keyword Performance Data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $amazonAdsService = app(AmazonAdsService::class);
        Log::channel('ads')->info('✅ Keyword Recommendation Started');

        // Stream campaigns instead of ->get()
        AmzCampaigns::where('campaign_state', 'ENABLED')
            ->where('targeting_type', 'MANUAL')
            ->select(['campaign_id', 'country']) // only what we need
            ->orderBy('campaign_id')
            ->chunkById(50, function ($campaigns) use ($amazonAdsService) {

                foreach ($campaigns as $campaign) {
                    $profileId = match (strtoupper($campaign->country)) {
                        'US' => config('amazon_ads.profiles.US'),
                        'CA' => config('amazon_ads.profiles.CA'),
                        default => null,
                    };

                    if (!$profileId) {
                        Log::channel('ads')->warning("⚠️ No profile ID for {$campaign->country}, skipping campaign {$campaign->campaign_id}");
                        continue;
                    }

                    // Stream only keywords NOT already processed:
                    // Avoid building a giant processedIds array; use NOT EXISTS
                    AmzAdsKeywords::query()
                        ->where('campaign_id', $campaign->campaign_id)
                        ->where('state', 'ENABLED')
                        ->select(['keyword_id', 'keyword_text', 'match_type', 'ad_group_id'])
                        ->whereNotExists(function ($q) use ($campaign) {
                            $q->from('amz_keyword_sp_sug_by_adgroup as s')
                                ->whereColumn('s.keyword_id', 'amz_ads_keywords.keyword_id')
                                ->where('s.campaign_id', $campaign->campaign_id)
                                ->where('s.is_processed', true);
                        })
                        ->orderBy('ad_group_id')
                        ->orderBy('keyword_id')
                        // process 1,000 keywords at a time; releases between chunks
                        ->chunk(1000, function ($rows) use ($campaign, $amazonAdsService, $profileId) {

                            // Group rows by ad_group_id in-CHUNK to limit memory
                            $byAdGroup = [];
                            foreach ($rows as $row) {
                                $byAdGroup[$row->ad_group_id][] = $row;
                            }

                            foreach ($byAdGroup as $adGroupId => $kwRows) {
                                // Split this ad group’s keywords into API batches of 100
                                $batches = array_chunk($kwRows, 100);

                                foreach ($batches as $batch) {
                                    // Build a map keyword_text => keyword_id to avoid per-item searches
                                    $lookup = [];
                                    foreach ($batch as $k) {
                                        $lookup[$k->keyword_text] = [
                                            'keyword_id' => $k->keyword_id,
                                            'match_type' => strtoupper($k->match_type),
                                        ];
                                    }

                                    $targets = array_map(function ($k) {
                                        return [
                                            "keyword"             => $k->keyword_text,
                                            "matchType"           => strtoupper($k->match_type),
                                            "userSelectedKeyword" => true,
                                        ];
                                    }, $batch);
                                    $payload = [
                                        "adGroupId"          => $adGroupId,
                                        "campaignId"         => $campaign->campaign_id,
                                        "recommendationType" => "KEYWORDS_FOR_ADGROUP",
                                        "targets"            => array_values($targets),
                                        "locale"             => "en_US",
                                        "maxRecommendations" => 0,
                                        "sortDimension"      => "DEFAULT",
                                    ];
                                    $data = $amazonAdsService->getRankedKeywordRecommendation($payload, $profileId);
                                    if (empty($data['success'])) {
                                        // Log error but don’t explode FDs with massive context
                                        // Log::channel('ads')->warning(
                                        //     "⚠️ API failed [Campaign {$campaign->campaign_id} | AdGroup {$adGroupId}] — skipping this batch"
                                        // );
                                        // brief pause to avoid hot-looping on errors
                                        usleep(250000);
                                        unset($lookup, $targets, $payload, $data);
                                        gc_collect_cycles();
                                        continue;
                                    }

                                    $response = !empty($data['response']) ? json_decode($data['response'], true) : null;
                                    if (!empty($response['keywordTargetList'])) {
                                        foreach ($response['keywordTargetList'] as $item) {
                                            // bidInfo may be missing or empty
                                            $bid = $item['bidInfo'][0]['suggestedBid'] ?? null;

                                            // use our map (no collection search)
                                            $fromLookup = $lookup[$item['keyword']] ?? null;

                                            try {
                                                SpKeywordSugByAdgroup::updateOrCreate(
                                                    [
                                                        'ad_group_id'  => $adGroupId,
                                                        'campaign_id'  => $campaign->campaign_id,
                                                        'keyword_text' => $item['keyword'],
                                                        'keyword_id'   => $fromLookup['keyword_id'] ?? null,
                                                        'match_type'   => $item['bidInfo'][0]['matchType'] ?? ($fromLookup['match_type'] ?? 'EXACT'),
                                                    ],
                                                    [
                                                        'bid_start'      => isset($bid['rangeStart'])  ? $bid['rangeStart']  / 100 : 0,
                                                        'bid_suggestion' => isset($bid['rangeMedian']) ? $bid['rangeMedian'] / 100 : 0,
                                                        'bid_end'        => isset($bid['rangeEnd'])    ? $bid['rangeEnd']    / 100 : 0,
                                                        'country'        => $campaign->country,
                                                        'added'          => now()->toDateString(),
                                                        'is_processed'   => true,
                                                    ]
                                                );
                                            } catch (\Throwable $e) {
                                                Log::channel('ads')->error("Save failed [{$item['keyword']}] {$e->getMessage()}");
                                            }
                                        }
                                    }
                                    // Hard release between API calls
                                    unset($lookup, $targets, $payload, $data, $response);
                                    gc_collect_cycles();
                                    // gentle rate control
                                    usleep(150000); // 0.15s
                                }

                                unset($batches);
                                gc_collect_cycles();
                            }

                            unset($byAdGroup);
                            gc_collect_cycles();
                        });

                    // campaign boundary log (one line)
                    // Log::channel('ads')->info("✔️ Completed campaign {$campaign->campaign_id}");
                    // small pause between campaigns to reduce socket churn
                    usleep(300000); // 0.3s
                }

                // release $campaigns chunk
                unset($campaigns);
                gc_collect_cycles();
            }, 'campaign_id');

        // Reset flags at the very end only if you truly want to re-process later
        SpKeywordSugByAdgroup::query()->update(['is_processed' => false]);

        Log::channel('ads')->info('✅ Keyword Recommendation Completed');
        return Command::SUCCESS;
    }
}
