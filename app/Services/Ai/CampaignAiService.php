<?php

namespace App\Services\Ai;

use App\Services\Seller\SellingAdsItemService;
use App\Traits\HasFilteredAdsPerformance;
use Illuminate\Support\Facades\Cache;

class CampaignAiService
{
    use HasFilteredAdsPerformance;

    /**
     * Get campaign keywords and recommendations for AI analysis.
     * Uses buildAsinCampaignDetails from SellingAdsItemService.
     *
     * @param string $asin
     * @param string|null $campaignType Optional filter (SP, SB, SD)
     * @param string|null $country Optional filter (US, UK, DE, etc)
     * @param string|null $keywords Optional search term to filter keywords
     * @param string|null $selectedDate Optional date for keyword data (defaults to yesterday)
     * @param array|null $campaignIds Optional specific campaign IDs (not used with buildAsinCampaignDetails)
     * @return array Structured keyword and recommendation data
     */
    public function getCampaignKeywords(
        string $asin,
        ?string $campaignType = null,
        ?string $country = null,
        ?string $keywords = null,
        ?string $selectedDate = null,
        ?array $campaignIds = null
    ): array {
        $selectedDate = $selectedDate ?? now()->subDay()->toDateString();

        // Build cache key with all parameters
        $cacheKey = "campaign_ai_keywords:{$asin}:" . md5(
            $campaignType . ':' . $country . ':' . $keywords . ':' . $selectedDate
        );

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use (
            $asin,
            $campaignType,
            $country,
            $keywords,
            $selectedDate
        ) {
            $service = app(SellingAdsItemService::class);

            // Build query using traits (same as controller)
            $request = new class implements \ArrayAccess {
                public $attributes = [
                    'period'            => '1d',
                    'asins'             => [],
                    'campaign'          => 'SP',
                    'sp_targeting_type' => 'MANUAL',
                    'country'           => null,
                ];

                public function input($key, $default = null) {
                    return $this->attributes[$key] ?? $default;
                }

                public function get($key, $default = null) {
                    return $this->attributes[$key] ?? $default;
                }

                public function has($key) {
                    return isset($this->attributes[$key]);
                }

                public function filled($key) {
                    if (!array_key_exists($key, $this->attributes)) {
                        return false;
                    }
                    $value = $this->attributes[$key];
                    if (is_string($value)) {
                        return trim($value) !== '';
                    }
                    return $value !== null && $value !== [];
                }

                public function merge($array) {
                    $this->attributes = array_merge($this->attributes, $array);
                }

                public function offsetExists($offset): bool {
                    return array_key_exists($offset, $this->attributes);
                }

                public function offsetGet($offset): mixed {
                    return $this->attributes[$offset] ?? null;
                }

                public function offsetSet($offset, $value): void {
                    $this->attributes[$offset] = $value;
                }

                public function offsetUnset($offset): void {
                    unset($this->attributes[$offset]);
                }
            };

            // Set up request attributes for filtering
            $request->attributes['period'] = '1d';
            $request->attributes['asins'] = [$asin];
            $request->attributes['campaign'] = $campaignType ?: 'SP';
            $request->attributes['country'] = $country;

            // Get filtered campaigns using the trait method
            $query = $this->getFilteredCampaignsQuery($request);

            // Get campaign details (includes keywords and recommended keywords)
            $result = $service->buildAsinCampaignDetails(
                query: $query,
                selectedDate: $selectedDate,
                asin: $asin,
                perPage: 999 // Get all campaigns, not paginated
            );

            // Transform the response for AI consumption
            return $this->transformCampaignDetailsForAi(
                $result['campaigns'],
                $keywords // Optional keyword filter
            );
        });
    }

    /**
     * Transform buildAsinCampaignDetails response into AI-friendly format.
     *
     * @param mixed $campaigns Paginated campaigns from buildAsinCampaignDetails
     * @param string|null $keywordFilter Optional filter by keyword text
     * @return array
     */
    private function transformCampaignDetailsForAi($campaigns, ?string $keywordFilter = null): array
    {
        $keywords = [];
        $recommended = [];

        // Extract campaigns array from paginated result
        $campaignsList = $campaigns instanceof \Illuminate\Pagination\Paginator
            ? $campaigns->getCollection()
            : collect($campaigns);

        if ($campaignsList->has('data') && is_array($campaignsList->get('data'))) {
            $campaignsList = collect($campaignsList->get('data'));
        }
        foreach ($campaignsList as $campaign) {
            $campaignData = is_array($campaign) ? $campaign : (array) $campaign;
            $keywordsRaw = $campaignData['keywords'] ?? [];
            $recommendedRaw = $campaignData['recommended'] ?? ($campaignData['recommended_keywords'] ?? []);

            if ($keywordsRaw instanceof \Illuminate\Support\Collection) {
                $keywordsRaw = $keywordsRaw->all();
            }
            if ($recommendedRaw instanceof \Illuminate\Support\Collection) {
                $recommendedRaw = $recommendedRaw->all();
            }
            if (is_string($keywordsRaw)) {
                $decoded = json_decode($keywordsRaw, true);
                $keywordsRaw = is_array($decoded) ? $decoded : [];
            }
            if (is_string($recommendedRaw)) {
                $decoded = json_decode($recommendedRaw, true);
                $recommendedRaw = is_array($decoded) ? $decoded : [];
            }
            // Extract active keywords
            if (is_array($keywordsRaw)) {
                foreach ($keywordsRaw as $kw) {
                    // Optional keyword filter
                    if ($keywordFilter && stripos($kw['keyword'] ?? '', $keywordFilter) === false) {
                        continue;
                    }

                    $keywords[] = [
                        'campaign_id' => (string) ($campaignData['campaign_id'] ?? ''),
                        'campaign_name' => $campaignData['campaign_name'] ?? null,
                        'keyword_id' => $kw['keyword_id'] ?? null,
                        'keyword' => $kw['keyword'] ?? null,
                        'clicks' => (int) ($kw['clicks'] ?? 0),
                        'impressions' => (int) ($kw['impressions'] ?? 0),
                        'total_spend' => (float) ($kw['total_spend'] ?? 0),
                        'total_sales' => (float) ($kw['total_sales'] ?? 0),
                        'bid' => $kw['bid'] ? (float) $kw['bid'] : null,
                        'state' => $kw['sp_state'] ?? 'UNKNOWN',
                        'acos' => $this->calculateAcos($kw['total_spend'] ?? 0, $kw['total_sales'] ?? 0),
                    ];
                }
            }

            // Extract recommended keywords
            if (is_array($recommendedRaw)) {
                foreach ($recommendedRaw as $rec) {
                    // Optional keyword filter
                    if ($keywordFilter && stripos($rec['keyword'] ?? '', $keywordFilter) === false) {
                        continue;
                    }

                    $recommended[] = [
                        'campaign_id' => (string) ($campaignData['campaign_id'] ?? ''),
                        'campaign_name' => $campaignData['campaign_name'] ?? null,
                        'ad_group_id' => $rec['ad_group_id'] ?? null,
                        'keyword' => $rec['keyword'] ?? null,
                        'match_type' => $rec['match_type'] ?? 'BROAD',
                        'bid' => (float) ($rec['bid'] ?? 0),
                        'updated_at' => $rec['updated_at'] ?? null,
                    ];
                }
            }
        }

        // Calculate summary metrics
        $summary = $this->calculateSummaryMetrics($keywords, $recommended);

        return [
            'keywords'    => $keywords,
            'recommended' => $recommended,
            'summary'     => $summary,
        ];
    }

    /**
     * Calculate ACOS (Ad Cost of Sales).
     *
     * @param float $spend
     * @param float $sales
     * @return float
     */
    private function calculateAcos(float $spend, float $sales): float
    {
        if ($sales <= 0) {
            return $spend > 0 ? 999.99 : 0; // High ACOS for wasted spend
        }
        return round(($spend / $sales) * 100, 2);
    }

    /**
     * Calculate summary metrics from keyword data.
     *
     * @param array $keywords
     * @param array $recommended
     * @return array
     */
    private function calculateSummaryMetrics(array $keywords, array $recommended): array
    {
        $totalSpend = array_sum(array_column($keywords, 'total_spend'));
        $totalSales = array_sum(array_column($keywords, 'total_sales'));
        $totalClicks = array_sum(array_column($keywords, 'clicks'));

        return [
            'keyword_count' => count($keywords),
            'recommended_count' => count($recommended),
            'total_spend' => round($totalSpend, 2),
            'total_sales' => round($totalSales, 2),
            'total_clicks' => $totalClicks,
            'total_acos' => $this->calculateAcos($totalSpend, $totalSales),
            'avg_bid' => count($keywords) > 0
                ? round(array_sum(array_filter(array_column($keywords, 'bid'))) / count($keywords), 2)
                : 0,
        ];
    }

    /**
     * Search keywords across all campaigns for an ASIN.
     *
     * @param string $asin
     * @param string $searchTerm
     * @param int $limit
     * @return array
     */
    public function searchKeywords(string $asin, string $searchTerm, int $limit = 50): array
    {
        $cacheKey = "campaign_ai_search:{$asin}:" . md5($searchTerm);

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($asin, $searchTerm, $limit) {
            // Get all keywords for the ASIN
            $allData = $this->getCampaignKeywords($asin);

            $keywords = $allData['keywords'] ?? [];
            $recommended = $allData['recommended'] ?? [];

            // Filter by search term
            $filtered_keywords = array_filter(
                $keywords,
                fn($k) => stripos($k['keyword'] ?? '', $searchTerm) !== false
            );

            $filtered_recommended = array_filter(
                $recommended,
                fn($r) => stripos($r['keyword'] ?? '', $searchTerm) !== false
            );

            return [
                'keywords' => array_slice($filtered_keywords, 0, $limit),
                'recommended' => array_slice($filtered_recommended, 0, $limit),
            ];
        });
    }
    
    /**
     * Deep analysis of campaign keywords using buildAsinCampaignDetails response.
     * This method accepts the raw response from buildAsinCampaignDetails for deeper AI analysis.
     *
     * @param array $campaignDetailsResponse The response from buildAsinCampaignDetails()
     * @param string $asin
     * @param array $options Additional analysis options:
     *   - 'grouping' => 'performance|campaign|keyword' (how to group results)
     *   - 'min_spend' => float (minimum spend threshold for analysis)
     *   - 'max_acos' => float (maximum ACOS for low performers)
     *   - 'min_clicks' => int (minimum clicks for statistical significance)
     *   - 'include_metrics' => bool (include detailed metrics breakdown)
     *   - 'include_trends' => bool (include performance trends across periods)
     *   - 'include_recommendations' => bool (include recommended keywords analysis)
     *   - 'limit_keywords' => int (limit keywords per group)
     * @return array Deep analysis with grouped data and insights
     */
    public function deepAnalyzeCampaignKeywords(
        array $campaignDetailsResponse,
        string $asin,
        array $options = []
    ): array {
        // Default options
        $defaults = [
            'grouping' => 'performance',
            'min_spend' => 5.0,
            'max_acos' => 50.0,
            'min_clicks' => 0,
            'include_metrics' => true,
            'include_trends' => true,
            'include_recommendations' => true,
            'limit_keywords' => 15,
        ];
        $options = array_merge($defaults, $options);

        // Extract campaigns from response
        $campaigns = $campaignDetailsResponse['campaigns'] ?? null;
        if (!$campaigns) {
            return ['error' => 'Invalid campaign details response'];
        }

        // Extract campaigns array from paginated result
        $campaignsList = $campaigns instanceof \Illuminate\Pagination\Paginator 
            ? $campaigns->getCollection() 
            : collect($campaigns);

        // Initialize analysis structure
        $analysis = [
            'asin' => $asin,
            'campaign_count' => count($campaignsList),
            'analyzed_at' => now()->toIso8601String(),
            'options' => $options,
            'by_performance' => [],
            'by_campaign' => [],
            'keywords_summary' => [],
            'recommended_summary' => [],
            'metrics_breakdown' => [],
            'insights' => [],
        ];

        // Process campaigns based on grouping preference
        foreach ($campaignsList as $campaign) {
            $campaignId = (string) $campaign->campaign_id;
            $campaignName = $campaign->campaign_name;

            // Process keywords
            $campaignKeywords = $this->analyzeKeywords(
                $campaign->keywords ?? [],
                $options
            );

            // Store by campaign
            $analysis['by_campaign'][$campaignId] = [
                'campaign_id' => $campaignId,
                'campaign_name' => $campaignName,
                'campaign_type' => $campaign->campaign_types ?? null,
                'country' => $campaign->country ?? null,
                'keywords' => $campaignKeywords['all'],
                'keyword_count' => $campaignKeywords['count'],
                'metrics' => $campaignKeywords['metrics'],
            ];

            // Store by performance (if requested)
            if ($options['grouping'] === 'performance') {
                $this->groupKeywordsByPerformance(
                    $analysis['by_performance'],
                    $campaignKeywords
                );
            }

            // Analyze recommended keywords
            if ($options['include_recommendations'] && isset($campaign->recommended)) {
                $recommendedAnalysis = $this->analyzeRecommendedKeywords(
                    $campaign->recommended,
                    $campaignId,
                    $campaignName
                );
                $analysis['by_campaign'][$campaignId]['recommended'] = $recommendedAnalysis;
                $analysis['recommended_summary'] = array_merge(
                    $analysis['recommended_summary'],
                    $recommendedAnalysis
                );
            }
        }

        // Calculate overall metrics
        if ($options['include_metrics']) {
            $analysis['metrics_breakdown'] = $this->calculateOverallMetrics($analysis['by_campaign']);
        }

        // Generate insights
        if ($options['include_trends']) {
            $analysis['insights'] = $this->generateInsights($analysis);
        }

        // Add summary
        $analysis['keywords_summary'] = [
            'total_keywords' => array_sum(array_column($analysis['by_campaign'], 'keyword_count')),
            'total_recommended' => count($analysis['recommended_summary']),
        ];

        return $analysis;
    }

    /**
     * Analyze keywords within a campaign.
     */
    private function analyzeKeywords(array $keywords, array $options): array
    {
        $analyzed = [
            'all' => [],
            'high_acos' => [],
            'low_acos' => [],
            'no_sales' => [],
            'count' => count($keywords),
            'metrics' => [],
        ];

        $totalSpend = 0;
        $totalSales = 0;
        $totalClicks = 0;

        foreach ($keywords as $kw) {
            $spend = (float) ($kw['total_spend'] ?? 0);
            $sales = (float) ($kw['total_sales'] ?? 0);
            $clicks = (int) ($kw['clicks'] ?? 0);
            $acos = $this->calculateAcos($spend, $sales);

            // Apply filters
            if ($spend < $options['min_spend']) {
                continue;
            }
            if ($clicks < $options['min_clicks']) {
                continue;
            }

            $keywordData = [
                'keyword' => $kw['keyword'] ?? null,
                'keyword_id' => $kw['keyword_id'] ?? null,
                'clicks' => $clicks,
                'impressions' => (int) ($kw['impressions'] ?? 0),
                'spend' => $spend,
                'sales' => $sales,
                'acos' => $acos,
                'ctr' => $this->calculateCtr($clicks, (int) ($kw['impressions'] ?? 0)),
                'roas' => $spend > 0 ? round($sales / $spend, 2) : 0,
                'bid' => $kw['bid'] ? (float) $kw['bid'] : null,
                'state' => $kw['sp_state'] ?? null,
            ];

            $analyzed['all'][] = $keywordData;
            $totalSpend += $spend;
            $totalSales += $sales;
            $totalClicks += $clicks;

            // Categorize by performance
            if ($sales == 0 && $spend > 0) {
                $analyzed['no_sales'][] = $keywordData;
            } elseif ($acos > $options['max_acos']) {
                $analyzed['high_acos'][] = $keywordData;
            } else {
                $analyzed['low_acos'][] = $keywordData;
            }
        }

        // Limit results
        $limit = $options['limit_keywords'];
        foreach (['all', 'high_acos', 'low_acos', 'no_sales'] as $key) {
            if (count($analyzed[$key]) > $limit) {
                $analyzed[$key] = array_slice($analyzed[$key], 0, $limit);
            }
        }

        // Calculate metrics
        if ($analyzed['count'] > 0) {
            $bids = array_filter(array_column($analyzed['all'], 'bid'), fn($bid) => $bid !== null);
            $analyzed['metrics'] = [
                'total_spend' => round($totalSpend, 2),
                'total_sales' => round($totalSales, 2),
                'total_clicks' => $totalClicks,
                'total_acos' => $this->calculateAcos($totalSpend, $totalSales),
                'average_bid' => count($bids) > 0
                    ? round(array_sum($bids) / count($bids), 2)
                    : 0,
                'keywords_by_performance' => [
                    'high_acos_count' => count($analyzed['high_acos']),
                    'low_acos_count' => count($analyzed['low_acos']),
                    'no_sales_count' => count($analyzed['no_sales']),
                ],
            ];
        }

        return $analyzed;
    }

    /**
     * Analyze recommended keywords.
     */
    private function analyzeRecommendedKeywords(
        array $recommended,
        string $campaignId,
        string $campaignName
    ): array {
        return array_map(function ($rec) use ($campaignId, $campaignName) {
            return [
                'keyword' => trim($rec['keyword'] ?? ''),
                'match_type' => $rec['match_type'] ?? 'BROAD',
                'bid' => (float) ($rec['bid'] ?? 0),
                'campaign_id' => $campaignId,
                'campaign_name' => $campaignName,
                'updated_at' => $rec['updated_at'] ?? null,
            ];
        }, $recommended);
    }

    /**
     * Group keywords by performance category.
     */
    private function groupKeywordsByPerformance(array &$byPerformance, array $campaignKeywords): void
    {
        if (!isset($byPerformance['high_acos'])) {
            $byPerformance['high_acos'] = [];
            $byPerformance['low_acos'] = [];
            $byPerformance['no_sales'] = [];
        }

        $byPerformance['high_acos'] = array_merge(
            $byPerformance['high_acos'],
            $campaignKeywords['high_acos']
        );
        $byPerformance['low_acos'] = array_merge(
            $byPerformance['low_acos'],
            $campaignKeywords['low_acos']
        );
        $byPerformance['no_sales'] = array_merge(
            $byPerformance['no_sales'],
            $campaignKeywords['no_sales']
        );
    }

    /**
     * Calculate overall metrics across all campaigns.
     */
    private function calculateOverallMetrics(array $campaignsByAnalysis): array
    {
        $totalSpend = 0;
        $totalSales = 0;
        $totalClicks = 0;
        $campaignCount = 0;

        foreach ($campaignsByAnalysis as $campaign) {
            if (isset($campaign['metrics'])) {
                $totalSpend += $campaign['metrics']['total_spend'] ?? 0;
                $totalSales += $campaign['metrics']['total_sales'] ?? 0;
                $totalClicks += $campaign['metrics']['total_clicks'] ?? 0;
                $campaignCount++;
            }
        }

        return [
            'total_spend' => round($totalSpend, 2),
            'total_sales' => round($totalSales, 2),
            'total_clicks' => $totalClicks,
            'total_acos' => $this->calculateAcos($totalSpend, $totalSales),
            'average_spend_per_campaign' => $campaignCount > 0 ? round($totalSpend / $campaignCount, 2) : 0,
            'campaigns_analyzed' => $campaignCount,
        ];
    }

    /**
     * Generate actionable insights from the analysis.
     */
    private function generateInsights(array $analysis): array
    {
        $insights = [];

        // Overall ACOS insight
        if (isset($analysis['metrics_breakdown']['total_acos'])) {
            $acos = $analysis['metrics_breakdown']['total_acos'];
            if ($acos > 50) {
                $insights[] = [
                    'type' => 'warning',
                    'title' => 'High Overall ACOS',
                    'message' => "Overall ACOS is {$acos}%. Consider pausing or lowering bids on underperforming keywords.",
                    'severity' => 'high',
                ];
            } elseif ($acos < 25) {
                $insights[] = [
                    'type' => 'opportunity',
                    'title' => 'Excellent ACOS',
                    'message' => "Overall ACOS is {$acos}%. Opportunity to increase bids on performing keywords.",
                    'severity' => 'low',
                ];
            }
        }

        // Summary insight
        if ($analysis['campaign_count'] > 0 && isset($analysis['metrics_breakdown']['total_spend'])) {
            $totalKeywords = array_sum(array_column($analysis['by_campaign'], 'keyword_count'));
            $totalSpend = $analysis['metrics_breakdown']['total_spend'];
            $insights[] = [
                'type' => 'info',
                'title' => 'Campaign Summary',
                'message' => "Analyzing {$analysis['campaign_count']} campaign(s) with {$totalKeywords} keywords and \${$totalSpend} total spend.",
                'severity' => 'info',
            ];
        }

        return $insights;
    }

    /**
     * Helper: Calculate CTR
     */
    private function calculateCtr(int $clicks, int $impressions): float
    {
        if ($impressions === 0) {
            return 0;
        }
        return round(($clicks / $impressions) * 100, 2);
    }
}
