<?php

namespace App\Services\Seller;

use App\Models\AmzAdsKeywords;
use Illuminate\Http\Request;
use App\Services\Seller\AsinRepositoryService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\AmzKeywordRecommendation;
use App\Models\CampaignKeywordRecommendation;
use App\Services\Api\AmazonAdsService;
use App\Services\Seller\ReportService;
use App\Traits\HasFilteredAdsPerformance;
use Exception;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;

class SellingAdsItemService
{
    use HasFilteredAdsPerformance;
    public function __construct(
        protected AsinRepositoryService $asinRepo,
        protected ReportService $reportService,
        protected CampaignService $campaignService,
        protected AmazonAdsService $amazonAdsService

    ) {}

    public function getAsinsForIndex(Request $request): array
    {
        $user = Auth::user();
        $reportingUsers = $this->asinRepo->getReportingUsers($user->id);

        $paginator = $this->asinRepo->getAsinsForIndex($request, $user, $reportingUsers);

        return [
            'asins'          => $paginator,
            'reportingUsers' => $reportingUsers,
            'targetUserId'   => $request->input('select'),
        ];
    }

    public function getAsinDetails(string $asin): array
    {
        $weeklyData  = $this->reportService->buildWeeklyReport($asin);
        $dailydata   = $this->reportService->buildDailyReport($asin);

        $weeklyReport = [
            'summary'         => $weeklyData['summary'],
            'weeks'           => $weeklyData['weeks'],
            'sp'              => $weeklyData['sp'],
            'sb'              => $weeklyData['sb'],
            'campaignMetrics' => $weeklyData['campaignMetrics'],
        ];

        $dailyReport = [
            'summary'  => $dailydata['daily'],
            'days'     => $dailydata['days'],
            'dayNames' => $dailydata['dayNames'],
        ];
        $campaignReport = $this->getCampaignReport($asin);
        return compact(
            'asin',
            'weeklyReport',
            'dailyReport',
            'campaignReport'
        );
    }

    public function getCampaignReport(string $asin): array
    {
        try {
            return $this->campaignService->getCampaignReportDataDaily($asin);
        } catch (\Throwable $e) {
            return ['sp' => [], 'sb' => [], 'campaignMetrics' => [], 'days' => [], 'dayNames' => []];
        }
    }

    public function buildAsinCampaignDetails($query, string $selectedDate, string $asin, int $perPage = 25): array
    {
        // paginate campaigns
        $campaigns = $query->orderBy('campaign_id', 'desc')->paginate($perPage);
        // if you have any transform logic later, keep it here
        $merged = $this->mergeCampaignAsins($campaigns->getCollection());
        $campaigns->setCollection($merged);

        $campaignIds = $campaigns->getCollection()
            ->pluck('campaign_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $keywordsByCampaign = [];
        $recommendedByCampaign = [];

        if (!empty($campaignIds)) {

            // 1) Keywords (by date)
            $kwRows = AmzKeywordRecommendation::query()
                ->withoutGlobalScope(SoftDeletingScope::class)
                ->from('amz_keyword_recommendations as rec')
                ->whereDate('rec.date', $selectedDate)
                ->whereIn('rec.campaign_id', $campaignIds)
                ->leftJoin('amz_ads_keywords as sp_kw', 'rec.keyword_id', '=', 'sp_kw.keyword_id')
                ->select([
                    'rec.campaign_id',
                    'rec.keyword_id',
                    'rec.keyword',
                    'rec.clicks',
                    'rec.impressions',
                    'rec.total_spend',
                    'rec.total_sales',
                    'sp_kw.state as sp_state',
                    'sp_kw.bid as bid',
                ])
                ->orderBy('rec.campaign_id')
                ->orderBy('rec.keyword')
                ->get();

            $keywordsByCampaign = $kwRows
                ->groupBy('campaign_id')
                ->map(fn($rows) => $rows->values())
                ->toArray();

            // 2) Latest recommendations per campaign (BROAD)
            $latestByCampaign = CampaignKeywordRecommendation::query()
                ->whereIn('campaign_id', $campaignIds)
                ->select('campaign_id', DB::raw('MAX(updated_at) as latest_updated_at'))
                ->groupBy('campaign_id');

            $recoRows = CampaignKeywordRecommendation::query()
                ->joinSub($latestByCampaign, 'mx', function ($join) {
                    $join->on('mx.campaign_id', '=', 'campaign_keyword_recommendations.campaign_id')
                        ->on('mx.latest_updated_at', '=', 'campaign_keyword_recommendations.updated_at');
                })
                ->whereIn('campaign_keyword_recommendations.campaign_id', $campaignIds)
                ->where('campaign_keyword_recommendations.match_type', 'BROAD')
                ->select([
                    'campaign_keyword_recommendations.campaign_id',
                    'campaign_keyword_recommendations.ad_group_id',
                    DB::raw('TRIM(campaign_keyword_recommendations.keyword) as keyword'),
                    'campaign_keyword_recommendations.match_type',
                    'campaign_keyword_recommendations.bid',
                    'campaign_keyword_recommendations.updated_at',
                ])
                ->orderBy('campaign_keyword_recommendations.campaign_id')
                ->orderBy('keyword')
                ->get();

            $recommendedByCampaign = $recoRows
                ->groupBy('campaign_id')
                ->map(fn($rows) => $rows->values())
                ->toArray();
        }

        // attach to each campaign
        $campaigns->getCollection()->transform(function ($c) use ($keywordsByCampaign, $recommendedByCampaign) {
            $cid = (string) $c->campaign_id;

            $c->keywords = $keywordsByCampaign[$cid] ?? [];
            $c->recommended = $recommendedByCampaign[$cid] ?? [];

            $c->keywords_count = count($c->keywords);
            $c->recommended_count = count($c->recommended);

            return $c;
        });

        return [
            'campaigns' => $campaigns,
        ];
    }

    /**
     * Orchestrator: fetch latest recos -> build payload -> call Amazon -> persist created keywords
     */
    public function createSpKeywordsFromLatestRecommendations(int $campaignId, string $country): array
    {
        $latestUpdatedAt = CampaignKeywordRecommendation::query()
            ->where('campaign_id', $campaignId)
            ->max('updated_at');

        if (!$latestUpdatedAt) {
            return ['ok' => false, 'message' => 'No keyword recommendations found for this campaign.'];
        }

        $recoRows = CampaignKeywordRecommendation::query()
            ->where('campaign_id', $campaignId)
            ->where('updated_at', $latestUpdatedAt)
            ->select(['campaign_id', 'ad_group_id', 'keyword', 'match_type', 'bid_suggestion'])
            ->get();

        $profileId = $this->resolveProfileId($country);
        $payload   = $this->buildSpKeywordPayload($recoRows);
        $response  = $this->amazonAdsService->createKeywords($payload, $profileId);

        return $this->persistCreatedKeywordsFromAmazonResponse(
            response: $response,
            payload: $payload,
            country: $country
        );
    }

    /**
     * (1) Build payload
     */
    public function buildSpKeywordPayload($recoRows): array
    {
        return [
            'keywords' => $recoRows->map(function ($row) {
                return [
                    'campaignId' => (string) $row->campaign_id,
                    'adGroupId'  => (string) $row->ad_group_id,
                    'keywordText' => (string) $row->keyword,
                    'matchType'  => strtoupper((string) $row->match_type),
                    'bid'        => round((float) ($row->bid_suggestion ?? 0.1), 2),
                    'state'      => 'ENABLED',
                ];
            })->values()->all(),
        ];
    }

    /**
     * (2) Parse Amazon response + insert keywords immediately
     */
    public function persistCreatedKeywordsFromAmazonResponse(array $response, array $payload, string $country): array
    {
        $body = json_decode($response['response'] ?? '', true) ?: [];

        $successItems = $body['keywords']['success'] ?? [];
        if (!empty($successItems)) {

            foreach ($successItems as $item) {
                $i = (int) ($item['index'] ?? -1);
                $keywordId = $item['keywordId'] ?? null;

                if ($i < 0 || !$keywordId) continue;
                if (!isset($payload['keywords'][$i])) continue;

                $k = $payload['keywords'][$i];

                $row = [
                    'country'      => strtoupper($country),
                    'keyword_id'   => (string) $keywordId,
                    'campaign_id'  => (string) ($k['campaignId'] ?? ''),
                    'ad_group_id'  => (string) ($k['adGroupId'] ?? ''),
                    'keyword_text' => (string) ($k['keywordText'] ?? ''),
                    'match_type'   => (string) ($k['matchType'] ?? ''),
                    'state'        => (string) ($k['state'] ?? 'ENABLED'),
                    'bid'          => isset($k['bid']) ? (float) $k['bid'] : null,
                ];

                // Avoid duplicates
                AmzAdsKeywords::updateOrCreate(
                    ['keyword_id' => $row['keyword_id'], 'country' => $row['country']],
                    $row
                );
            }

            return ['ok' => true, 'message' => 'Keywords created successfully.'];
        }

        // failure message extraction
        $errorMessage = 'An error occurred while creating keywords.';
        $errorItems = $body['keywords']['error'] ?? [];
        if (!empty($errorItems)) {
            $errorMessage =
                $errorItems[0]['errors'][0]['errorValue']['malformedValueError']['message']
                ?? $errorMessage;
        }

        return ['ok' => false, 'message' => $errorMessage, 'amazon' => $body];
    }

    /**
     * (3) Resolve profileId
     */
    public function resolveProfileId(string $country): string
    {
        return match (strtoupper($country)) {
            'US' => (string) config('amazon_ads.profiles.US'),
            'CA' => (string) config('amazon_ads.profiles.CA'),
            default => throw new Exception("Unhandled country: {$country}"),
        };
    }

    public function spCreateCampaignWithRetry(string $profileId, string $name, float $dailyBudget, array $opts = [], string $targetingType, string $state): array
    {
        $maxAttempts = (int) ($opts['maxAttempts'] ?? 4);
        $minBudget   = (float) ($opts['minDailyBudget'] ?? 1.00); // adjust per marketplace

        // 1) budget floor (fail fast with nice error)
        if ($dailyBudget < $minBudget) {
            return $this->fail("Daily budget {$dailyBudget} is below marketplace minimum {$minBudget}. Increase total budget or reduce campaign count.");
        }

        $currentName = $name;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {

            $payload = [
                'campaigns' => [[
                    'name'          => $currentName,
                    'campaignType'  => 'SPONSORED_PRODUCTS',
                    'targetingType' => $targetingType,
                    'state'         => $state,
                    'startDate'     => now()->format('Y-m-d'),
                    'budget'        => [
                        'budget'     => $dailyBudget,
                        'budgetType' => 'DAILY',
                    ],
                ]],
            ];

            $res = $this->amazonAdsService->createCampaigns($payload, $profileId);
            $result = $this->apiResult($res, 'campaigns', 'campaignId');

            if ($result['ok']) {
                $result['name_used'] = $currentName;
                return $result;
            }

            // If duplicate name, modify CMP and retry
            if ($this->hasErrorType($result, 'duplicateValueError')) {
                $newSuffix = $this->randomCmpSuffix(4); // 0000-9999
                $currentName = $this->withCmpSuffix($currentName, $newSuffix);

                Log::warning('Duplicate campaign name, retrying', [
                    'attempt'   => $attempt,
                    'old_name'  => $name,
                    'new_name'  => $currentName,
                    'requestId' => $result['requestId'] ?? null,
                ]);

                continue;
            }

            // Any other error => return immediately
            return $result;
        }

        return $this->fail("Duplicate campaign name keeps happening after {$maxAttempts} attempts. Please try again.");
    }

    public function spCreateAdGroup(string $profileId, string $campaignId, string $name, float $defaultBid): array
    {
        $payload = [
            'adGroups' => [[
                'campaignId' => (string) $campaignId,
                'name'       => $name,
                'defaultBid' => $defaultBid,
                'state'      => 'ENABLED',
            ]],
        ];

        $res = $this->amazonAdsService->createSPAdGroups($payload, $profileId);
        return $this->apiResult($res, 'adGroups', 'adGroupId');
    }

    public function spCreateProductAd(string $profileId, string $campaignId, string $adGroupId, string $asin, string $sku): array
    {
        $payload = [
            'productAds' => [[
                'campaignId' => (string) $campaignId,
                'adGroupId'  => (string) $adGroupId,
                'sku'        => $sku,
                'asin'       => $asin,
                'state'      => 'ENABLED',
            ]],
        ];

        $res = $this->amazonAdsService->createSPProductAds($payload, $profileId);
        return $this->apiResult($res, 'productAds', 'adId');
    }

    /**
     * ------------------------------------------------------------
     * COMPENSATION / ROLLBACK (best-effort)
     * ------------------------------------------------------------
     */

    public function archiveCampaign(string $profileId, string $campaignId): array
    {
        // You need an API method that updates campaign state to ARCHIVED.
        // Rename this call to match your actual amazonAdsService method.
        $payload = [
            'campaigns' => [[
                'campaignId' => (string) $campaignId,
                'state'      => 'ARCHIVED',
            ]],
        ];

        $res = $this->amazonAdsService->updateCampaigns($payload, $profileId);
        return $this->apiResult($res, 'campaigns', 'campaignId');
    }

    public function archiveAdGroup(string $profileId, string $adGroupId): array
    {
        // Rename this call to match your actual amazonAdsService method.
        $payload = [
            'adGroups' => [[
                'adGroupId' => (string) $adGroupId,
                'state'     => 'ARCHIVED',
            ]],
        ];

        $res = $this->amazonAdsService->updateSPAdGroups($payload, $profileId);
        return $this->apiResult($res, 'adGroups', 'adGroupId');
    }

    /**
     * ------------------------------------------------------------
     * Update the Values to the table that way its updated
     * ------------------------------------------------------------
     */
    public function upsertCreatedProductAdsAndCampaigns(array $data, array $successRows): void
    {
        if (empty($successRows)) {
            return;
        }
        $campaignType = strtoupper($data['campaign_type'] ?? 'SP');

        // Products table (SP vs SB)
        $productsTable = match ($campaignType) {
            'SB' => 'amz_ads_products_sb',
            default => 'amz_ads_products', // SP
        };

        $now = now();

        // 1) Product Ads batch
        $productsBatch = array_map(function ($s) use ($data, $now) {
            return [
                'campaign_id' => (string) ($s['campaignId'] ?? ''),
                'ad_group_id' => (string) ($s['adGroupId'] ?? ''),
                'ad_id'       => (string) ($s['adId'] ?? ''),
                'asin'        => (string) ($data['asin'] ?? ''),
                'sku'         => (string) ($data['sku'] ?? ''),
                'state'       => (string) ($data['campaign_state'] ?? 'ENABLED'),
                'country'     => (string) ($data['country'] ?? 'US'),
                'added'       => $now,
                'updated_at'  => $now,
            ];
        }, $successRows);

        $productsBatch = array_values(array_filter(
            $productsBatch,
            fn($r) => $r['ad_group_id'] !== '' && $r['campaign_id'] !== '' && $r['country'] !== ''
        ));

        // 2) Campaigns batch (amz_campaigns)
        // IMPORTANT: your successRows must contain campaign name + budget.
        // If not, pass them: add 'name' and 'budget' into $results['success'][].
        $campaignsBatch = array_map(function ($s) use ($data, $now) {
            return [
                'campaign_id'    => (string) ($s['campaignId'] ?? ''),
                'country'        => (string) ($data['country'] ?? 'US'),
                'campaign_name'  => (string) ($s['name'] ?? ''),                       // make sure you store finalName in successRows
                'campaign_type'  => (string) ($data['campaign_type'] ?? 'SP'),
                'targeting_type' => (string) ($data['targeting_type'] ?? ''),
                'daily_budget'   => (float)  ($s['budget'] ?? 0),
                'start_date'     => now()->format('Y-m-d'),
                'campaign_state' => (string) ($data['campaign_state'] ?? 'ENABLED'),
                'added'          => $now,
                'updated_at'     => $now,
            ];
        }, $successRows);

        $campaignsBatch = array_values(array_filter(
            $campaignsBatch,
            fn($r) => $r['campaign_id'] !== '' && $r['country'] !== '' && $r['campaign_name'] !== ''
        ));

        // Upsert in a transaction (recommended)
        DB::transaction(function () use ($productsTable, $productsBatch, $campaignsBatch) {

            if (!empty($productsBatch)) {
                DB::table($productsTable)->upsert(
                    $productsBatch,
                    ['ad_group_id', 'country'],
                    ['campaign_id', 'ad_id', 'asin', 'sku', 'state', 'added', 'updated_at']
                );
            }

            if (!empty($campaignsBatch)) {
                DB::table('amz_campaigns')->upsert(
                    $campaignsBatch,
                    ['campaign_id', 'country'],
                    ['campaign_name', 'campaign_type', 'targeting_type', 'daily_budget', 'start_date', 'campaign_state', 'added', 'updated_at']
                );
            }
        });
    }

    public function buildSpKeywordPayloadFromDraft(
        int|string $campaignId,
        int|string $adGroupId,
        array $keywordsDraft
    ): array {
        // $keywordsDraft: [ ['text'=>'...', 'bid'=>0.75, 'match'=>'BROAD'], ... ]

        $items = collect($keywordsDraft)
            ->filter(function ($k) {
                $text = trim((string) ($k['text'] ?? ''));
                $bid  = $k['bid'] ?? null;
                $match = strtoupper((string) ($k['match'] ?? ''));

                return $text !== '' && is_numeric($bid) && in_array($match, ['BROAD', 'PHRASE', 'EXACT'], true);
            })
            ->map(function ($k) use ($campaignId, $adGroupId) {
                return [
                    'campaignId'  => (string) $campaignId,
                    'adGroupId'   => (string) $adGroupId,
                    'keywordText' => trim((string) $k['text']),
                    'matchType'   => strtoupper((string) $k['match']),
                    'bid'         => round((float) $k['bid'], 2),
                    'state'       => 'ENABLED',
                ];
            })
            ->values()
            ->all();

        return ['keywords' => $items];
    }


    public function spCreateKeywordsFromDraft(
        string $profileId,
        int|string $campaignId,
        int|string $adGroupId,
        array $keywordsDraft
    ): array {
        $payload = $this->buildSpKeywordPayloadFromDraft($campaignId, $adGroupId, $keywordsDraft);

        // If user didn’t add any valid keywords, return cleanly
        if (empty($payload['keywords'])) {
            return [
                'ok' => false,
                'messages' => ['No valid keywords found (text/bid/match required).'],
                'created' => [],
            ];
        }

        try {
            $response = $this->amazonAdsService->createKeywords($payload, $profileId);

            // Normalize response (adjust if your AmazonAdsService returns different structure)
            // Commonly returns list of created entities with keywordId + code/message
            $created = $response['keywords'] ?? $response ?? [];

            // Detect errors: any item has code != SUCCESS
            $errors = collect($created)
                ->filter(fn($x) => isset($x['code']) && strtoupper($x['code']) !== 'SUCCESS')
                ->map(fn($x) => ($x['description'] ?? 'Keyword create failed'))
                ->values()
                ->all();

            if (!empty($errors)) {
                return ['ok' => false, 'messages' => $errors, 'created' => $created];
            }

            return ['ok' => true, 'messages' => [], 'created' => $created];
        } catch (\Throwable $e) {
            Log::error('SP createKeywords failed', [
                'campaignId' => $campaignId,
                'adGroupId'  => $adGroupId,
                'error'      => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'messages' => [$e->getMessage()],
                'created' => [],
            ];
        }
    }

    public function spCreateTargetFromDraft(
        string $profileId,
        int|string $campaignId,
        int|string $adGroupId,
        array $targetsDraft
    ): array {
        $payload = [
            'targetingClauses' => collect($targetsDraft)->map(function ($t) use ($campaignId, $adGroupId) {
                return [
                    'campaignId'     => (string) $campaignId,
                    'adGroupId'      => (string) $adGroupId,
                    'bid'            => (float) ($t['bid'] ?? 0.1),
                    'expressionType' => 'MANUAL',
                    'state'          => (string) ($t['state'] ?? 'ENABLED'),
                    'expression'     => [
                        [
                            'type'  => (string) data_get($t, 'expression.0.type', 'ASIN_SAME_AS'),
                            'value' => (string) data_get($t, 'expression.0.value', ''),
                        ],
                    ],
                ];
            })->values()->all(),
        ];

        if (empty($payload['targetingClauses'])) {
            return ['ok' => false, 'messages' => ['Targets payload is empty.'], 'created' => []];
        }

        try {
            $resp = $this->amazonAdsService->createTargets($payload, $profileId);
            $rawBody = $resp['response'] ?? null;

            // If already decoded array, accept it
            $body = is_array($rawBody) ? $rawBody : (is_string($rawBody) ? json_decode($rawBody, true) : null);

            if (!is_array($body)) {
                return [
                    'ok' => false,
                    'messages' => ['Targets: Invalid API response (cannot decode body).'],
                    'created' => $resp,
                ];
            }

            $bucket = data_get($body, 'targetingClauses', []);

            $successItems = data_get($bucket, 'success', []);
            $errorItems   = data_get($bucket, 'error', []);

            // Normalize errors into readable strings
            $messages = collect($errorItems)->map(function ($e) {
                // common fields you might get
                $index = $e['index'] ?? null;
                $msg   = $e['message'] ?? $e['description'] ?? $e['details'] ?? 'Target create failed';
                $code  = $e['code'] ?? null;

                $prefix = '';
                if ($index !== null) $prefix .= "Index {$index}: ";
                if ($code !== null)  $prefix .= "{$code} - ";

                return trim($prefix . $msg);
            })->values()->all();

            // If Amazon returned errors, mark not ok (even if partial success)
            if (!empty($messages)) {
                return [
                    'ok' => false,
                    'messages' => $messages,
                    'created' => [
                        'success' => $successItems,
                        'error'   => $errorItems,
                        'raw'     => $resp, // keep raw for debugging
                    ],
                ];
            }
            // All good
            return [
                'ok' => true,
                'messages' => [],
                'created' => [
                    'success' => $successItems,
                    'error'   => $errorItems,
                    'raw'     => $resp,
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('SP createTargets failed', [
                'profileId'  => $profileId,
                'campaignId' => $campaignId,
                'adGroupId'  => $adGroupId,
                'error'      => $e->getMessage(),
            ]);
            return [
                'ok' => false,
                'messages' => [$e->getMessage()],
                'created' => [],
            ];
        }
    }


    /**
     * ------------------------------------------------------------
     * CORE PARSER (207 success + error arrays)
     * ------------------------------------------------------------
     */

    private function apiResult($res, string $rootKey, string $idKey): array
    {
        $decoded = $this->decodeResponse($res);

        $id = data_get($decoded, "{$rootKey}.success.0.{$idKey}");
        $errors = data_get($decoded, "{$rootKey}.error", []);

        $messages = [];
        $types = [];

        foreach ($errors as $errItem) {
            $idx = $errItem['index'] ?? null;

            foreach (($errItem['errors'] ?? []) as $e) {
                $type = $e['errorType'] ?? 'error';
                $types[] = $type;

                // Amazon nests message under errorValue.{errorType}.message
                $msg = data_get($e, "errorValue.$type.message")
                    ?? data_get($e, "errorValue.$type.reason")
                    ?? $e['message']
                    ?? $type;

                $messages[] = ($idx !== null ? "Index {$idx}: " : '') . $msg;
            }
        }

        $ok = !empty($id) && empty($messages);

        return [
            'ok'        => $ok,
            'id'        => $id ? (string) $id : null,
            'messages'  => $messages,
            'types'     => array_values(array_unique($types)),
            'requestId' => is_array($res) ? ($res['requestId'] ?? null) : null,
            'raw'       => $decoded,
        ];
    }

    private function decodeResponse($res): array
    {
        // Your amazonAdsService returns:
        // [ 'success'=>true, 'code'=>207, 'response' => '{"campaigns":...}', 'requestId'=>... ]
        if (is_array($res) && isset($res['response']) && is_string($res['response'])) {
            $json = json_decode($res['response'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                return $json;
            }
        }

        return is_array($res) ? $res : [];
    }

    private function hasErrorType(array $result, string $type): bool
    {
        return in_array($type, $result['types'] ?? [], true);
    }

    private function fail(string $message): array
    {
        return [
            'ok'        => false,
            'id'        => null,
            'messages'  => [$message],
            'types'     => ['localError'],
            'requestId' => null,
            'raw'       => null,
        ];
    }

    /**
     * ------------------------------------------------------------
     * CMP helpers
     * ------------------------------------------------------------
     */

    private function withCmpSuffix(string $name, string $suffix): string
    {
        // Replace trailing _CMP_XXXX with new one
        $new = preg_replace('/_CMP_\d+$/', '_CMP_' . $suffix, $name);
        return $new ?: $name;
    }

    private function randomCmpSuffix(int $digits = 4): string
    {
        $max = (10 ** $digits) - 1;
        return str_pad((string) random_int(0, $max), $digits, '0', STR_PAD_LEFT);
    }

    public function buildAsinKeywordDetails($query, string $selectedDate, string $asin, int $perPage = 25): array
    {
        // paginate keywords
        // IMPORTANT: orderBy should use a real column from your base table alias
        $keywords = $query
            ->orderBy('amz_keyword_recommendations.keyword_id', 'desc')
            ->paginate($perPage);

        // merge related_asin + asin into a single "related_asin" list per keyword_id
        $merged = $this->mergeKeywordAsins($keywords->getCollection());
        $keywords->setCollection($merged);

        // If you need any per-keyword attachments later, do them here.

        return [
            'keywords' => $keywords,
        ];
    }

    public function mergeKeywordAsins($keywords)
    {
        return $keywords->groupBy('keyword_id')->map(function ($group) {
            $first = $group->first();

            $allAsins = $group->pluck('asin')->filter()->unique()->values()->all();

            $related = $group->pluck('related_asin')->filter()->map(function ($item) {
                if (is_array($item)) {
                    return array_map(function ($v) {
                        return json_decode($v, true) ?: $v;
                    }, $item);
                } elseif (is_string($item)) {
                    return json_decode($item, true) ?: [$item];
                }
                return [];
            })->flatten()->unique()->values()->all();

            $first->related_asin = array_values(array_unique(array_merge($allAsins, $related)));

            return $first;
        })->values();
    }
}
