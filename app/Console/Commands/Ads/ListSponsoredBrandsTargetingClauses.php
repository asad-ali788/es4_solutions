<?php

namespace App\Console\Commands\Ads;

use App\Jobs\RetryCommandAfterDelay;
use App\Models\AmzTargetingClausesSb;
use App\Services\Api\AmazonAdsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Saloon\Exceptions\Request\Statuses\ForbiddenException;

class ListSponsoredBrandsTargetingClauses extends Command
{
    protected $signature = 'app:list-sponsored-brands-targeting-clauses';
    protected $description = 'ADS: Sync SB Targeting Clauses [US/CA] from Amazon Ads API';

    public function handle()
    {
        // ✅ Prevent overlapping runs (works if cache driver supports locks - Redis recommended)
        $lock = Cache::lock('sb_targeting_clauses_sync', 3600);

        if (! $lock->get()) {
            $this->info('Already running. Skipping.');
            return self::SUCCESS;
        }

        try {
            $amazonAdsService = app(AmazonAdsService::class);

            $profilesByCountry = [
                'US' => config('amazon_ads.profiles.US'),
                'CA' => config('amazon_ads.profiles.CA'),
            ];

            foreach ($profilesByCountry as $country => $profileId) {
                if (! $profileId) {
                    Log::channel('ads')->warning("No profileId mapping for country {$country}");
                    continue;
                }

                $payload = [
                    "filters" => [
                        [
                            "filterType" => "CREATIVE_TYPE",
                            "values"     => ["productCollection"],
                        ]
                    ],
                    "maxResults" => 1000,
                ];

                $nextToken = null;

                do {
                    if ($nextToken) {
                        $payload['nextToken'] = $nextToken;
                    } else {
                        unset($payload['nextToken']);
                    }

                    $data = $amazonAdsService->listSponsoredBrandsTargetingClauses($payload, $profileId);

                    if (empty($data['success']) || !$data['success']) {
                        Log::channel('ads')->error("SB TargetingClauses API failed", [
                            'country'  => $country,
                            'response' => $data,
                        ]);
                        break;
                    }

                    $response = json_decode($data['response'], true);
                    $targets  = $response['targets'] ?? [];

                    if (!empty($targets)) {
                        $now  = now();
                        $rows = [];

                        foreach ($targets as $t) {
                            $targetId  = (string) ($t['targetId'] ?? '');
                            $campaignId = (string) ($t['campaignId'] ?? '');   // IMPORTANT: use response campaignId
                            $adGroupId = (string) ($t['adGroupId'] ?? '');

                            if (!$targetId || !$campaignId || !$adGroupId) {
                                continue;
                            }

                            $rows[] = [
                                'country'               => $country,
                                'target_id'             => $targetId,
                                'campaign_id'           => $campaignId,
                                'ad_group_id'           => $adGroupId,
                                'bid'                   => $t['bid'] ?? null,

                                // ✅ no DB changes: store arrays as JSON string
                                'expressions'           => isset($t['expressions'])
                                    ? json_encode($t['expressions'], JSON_UNESCAPED_UNICODE)
                                    : null,

                                'resolved_expressions'  => isset($t['resolvedExpressions'])
                                    ? json_encode($t['resolvedExpressions'], JSON_UNESCAPED_UNICODE)
                                    : null,

                                'state'                 => $t['state'] ?? null,
                                'added'                 => $now->toDateString(),
                                'created_at'            => $now,
                                'updated_at'            => $now,
                            ];
                        }

                        // ✅ Chunk to avoid big memory + huge SQL packets
                        foreach (array_chunk($rows, 500) as $chunk) {
                            AmzTargetingClausesSb::upsert(
                                $chunk,
                                ['target_id', 'campaign_id', 'ad_group_id'], // your unique key (no country)
                                ['country', 'bid', 'expressions', 'resolved_expressions', 'state', 'added', 'updated_at']
                            );
                        }

                        unset($rows, $targets);
                    }

                    $nextToken = $response['nextToken'] ?? null;

                    unset($response, $data);

                    usleep(300_000); // ✅ throttle (0.3s). adjust if rate limits hit
                } while ($nextToken);
            }

            return self::SUCCESS;
        } catch (ForbiddenException $e) {
            Log::channel('ads')->warning('❌ 403 Forbidden (token expired/unauthorized): ' . $e->getMessage());
            RetryCommandAfterDelay::dispatch($this->signature, 5);
            return self::FAILURE;
        } catch (\Throwable $e) {
            Log::channel('ads')->error('❌ Unexpected error in ListSponsoredBrandsTargetingClauses: ' . $e->getMessage(), [
                'trace' => substr($e->getTraceAsString(), 0, 2000),
            ]);
            return self::FAILURE;
        } finally {
            optional($lock)->release();
        }
    }
}
