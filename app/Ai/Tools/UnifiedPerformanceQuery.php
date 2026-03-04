<?php

namespace App\Ai\Tools;

use App\Models\Ai\KeywordCampaignPerformanceLite;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

/**
 * UnifiedPerformanceQuery - Single tool for all campaign and keyword analysis
 * 
 * This unified tool queries the optimized keyword_campaign_performance_lites table
 * for ultra-fast retrieval of both keyword and campaign performance data in one query.
 * 
 * Key features:
 * - Keywords as primary identifier (not just supporting data)
 * - Campaign names for easy filtering
 * - Single day metrics (1d only)
 * - Budget information included
 * - Automatic ROAS and conversion rate calculations
 * - Supports ASIN, country, campaign type filtering
 * - Flexible sorting and limiting
 */
final class UnifiedPerformanceQuery implements Tool
{
    private string $marketTz = 'America/Los_Angeles';

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return implode(' ', [
            'Ultra-fast unified tool for querying both keywords and campaigns from a single optimized table.',
            'Query keywords, campaigns, or both together with flexible filtering.',
            'Supports: keyword search, campaign name search, ASIN filter, country/type filters, performance thresholds.',
            'Metrics: daily spend, sales, ACOS, ROAS, purchases, clicks, impressions, conversion rate, budget.',
            'Single-day data (1d) for real-time insights - no multi-period complexity.',
            'Examples: "keywords with ACOS > 30%", "campaigns in US generating > $500 sales", "windshield keywords", "top 10 by ROAS".',
            'Group by campaign_name with aggregated metrics. Budget info included for planning.',
        ]);
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        try {
            $now = Carbon::now($this->marketTz);
            $yesterdayDate = $now->copy()->subDay()->toDateString();
            $latestDate = KeywordCampaignPerformanceLite::query()->max('report_date');

            // Initialize query builder
            $query = KeywordCampaignPerformanceLite::query();

            // Parse parameters
            $date = $request['date'] ?? null;
            $dateFrom = $request['date_from'] ?? null;
            $dateTo = $request['date_to'] ?? null;
            $keyword = $request['keyword'] ?? null;
            $campaign = $request['campaign'] ?? null;
            $asin = $request['asin'] ?? null;
            $country = $request['country'] ?? null;
            $campaignType = $request['campaign_type'] ?? null;
            $campaignState = $request['campaign_state'] ?? null;
            $keywordState = $request['keyword_state'] ?? null;
            $limit = (int) ($request['limit'] ?? 25);
            $sortBy = $request['sort_by'] ?? 'sales';
            $sortOrder = $request['sort_order'] ?? 'desc';
            $groupBy = $request['group_by'] ?? null; // Can be 'campaign' or 'keyword'

            // Performance filters
            $minAcos = $request['min_acos'] ?? null;
            $maxAcos = $request['max_acos'] ?? null;
            $minRoas = $request['min_roas'] ?? null;
            $maxRoas = $request['max_roas'] ?? null;
            $minSales = $request['min_sales'] ?? null;
            $maxSales = $request['max_sales'] ?? null;
            $minSpend = $request['min_spend'] ?? null;
            $maxSpend = $request['max_spend'] ?? null;
            $minBudget = $request['min_budget'] ?? null;
            $maxBudget = $request['max_budget'] ?? null;
            $minClicks = $request['min_clicks'] ?? null;
            $minPurchases = $request['min_purchases'] ?? null;

            // Limit guardrails
            if ($limit < 1) $limit = 1;
            if ($limit > 500) $limit = 500;

            // Date filtering (strict)
            $dateUsed = null;
            $dateRequested = null;
            $dateWarning = null;

            if ($dateFrom && $dateTo) {
                $query->forDateRange($dateFrom, $dateTo);
                $dateRequested = $dateFrom . ' to ' . $dateTo;
                $dateUsed = $dateRequested;
            } elseif ($date) {
                $query->forDate($date);
                $dateRequested = $date;
                $dateUsed = $date;
            } else {
                return json_encode([
                    'success' => false,
                    'error' => 'Strict date mode: explicit date is required. Please pass `date` (YYYY-MM-DD) or both `date_from` and `date_to`.',
                    'items' => [],
                    'meta' => [
                        'count' => 0,
                        'strict_date_required' => true,
                        'latest_available_date' => $latestDate,
                        'yesterday_reference' => $yesterdayDate,
                    ],
                ], JSON_PRETTY_PRINT);
            }

            // Keyword and campaign filtering
            if ($keyword) {
                $query->searchKeyword($keyword);
            }

            if ($campaign) {
                $query->searchCampaign($campaign);
            }

            // ASIN filtering
            if ($asin) {
                $query->byAsin($asin);
            }

            // Geographic and type filtering
            if ($country) {
                $query->byCountry($country);
            }

            if ($campaignType) {
                $query->byType($campaignType);
            }

            // State filtering
            if ($campaignState) {
                $query->byState($campaignState);
            }

            if ($keywordState) {
                $query->byKeywordState($keywordState);
            }

            // Performance threshold filters
            if ($minAcos !== null) {
                $query->where('acos', '>=', $minAcos);
            }

            if ($maxAcos !== null) {
                $query->where('acos', '<=', $maxAcos)->where('acos', '>', 0);
            }

            if ($minRoas !== null) {
                $query->where('roas', '>=', $minRoas);
            }

            if ($maxRoas !== null) {
                $query->where('roas', '<=', $maxRoas);
            }

            if ($minSales !== null) {
                $query->where('total_sales', '>=', $minSales);
            }

            if ($maxSales !== null) {
                $query->where('total_sales', '<=', $maxSales);
            }

            if ($minSpend !== null) {
                $query->where('total_spend', '>=', $minSpend);
            }

            if ($maxSpend !== null) {
                $query->where('total_spend', '<=', $maxSpend);
            }

            if ($minBudget !== null) {
                $query->where('daily_budget', '>=', $minBudget);
            }

            if ($maxBudget !== null) {
                $query->where('daily_budget', '<=', $maxBudget);
            }

            if ($minClicks !== null) {
                $query->where('clicks', '>=', $minClicks);
            }

            if ($minPurchases !== null) {
                $query->where('purchases', '>=', $minPurchases);
            }

            // Process results based on grouping preference
            if (strtolower($groupBy ?? '') === 'campaign') {
                $results = $query->get();
                $items = $this->groupByCampaign($results);
                $this->applyArraySorting($items, $sortBy, $sortOrder);
                $items = array_slice($items, 0, $limit);
            } else {
                // Default: individual keyword records
                $this->applySorting($query, $sortBy, $sortOrder);
                $results = $query->limit($limit)->get();
                $items = $results->map(function ($row) {
                    return [
                        'keyword' => $row->keyword_text,
                        'campaign' => $row->campaign_name,
                        'campaign_id' => $row->campaign_id,
                        'asin' => $row->asin,
                        'country' => $row->country,
                        'campaign_type' => $row->campaign_type,
                        'campaign_state' => $row->campaign_state,
                        'keyword_state' => $row->keyword_state,
                        'report_date' => $row->report_date,
                        'metrics' => [
                            'spend' => round($row->total_spend, 2),
                            'sales' => round($row->total_sales, 2),
                            'acos' => round($row->acos, 2),
                            'roas' => $row->roas ? round($row->roas, 2) : null,
                            'purchases' => $row->purchases,
                            'clicks' => $row->clicks,
                            'impressions' => $row->impressions,
                            'ctr' => $row->ctr,
                            'cpc' => round($row->cpc, 4),
                            'conversion_rate' => round($row->conversion_rate, 2),
                        ],
                        'budget' => [
                            'daily' => round($row->daily_budget, 2),
                            'monthly_estimate' => round($row->estimated_monthly_budget, 2),
                        ],
                        'keyword_bid' => $row->keyword_bid,
                        'product_price' => $row->product_price,
                    ];
                })->values()->all();
            }

            // Aggregates
            $meta = [
                'count' => count($items),
                'limit' => $limit,
                'period' => '1d',
                'date_requested' => $dateRequested,
                'date_used' => $dateUsed,
                'filters' => $this->buildFiltersApplied($request),
                'query_params' => array_filter([
                    'date' => $date,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'campaign' => $campaign,
                    'keyword' => $keyword,
                    'asin' => $asin,
                    'country' => $country,
                    'campaign_type' => $campaignType,
                    'campaign_state' => $campaignState,
                    'keyword_state' => $keywordState,
                    'group_by' => $groupBy,
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder,
                    'limit' => $limit,
                ], fn ($value) => ! is_null($value) && $value !== ''),
            ];

            if ($dateWarning) {
                $meta['warning'] = $dateWarning;
            }

            if (!empty($items)) {
                $totalSpend = array_sum(array_map(fn($item) => $item['metrics']['spend'], $items));
                $totalSales = array_sum(array_map(fn($item) => $item['metrics']['sales'], $items));
                $totalClicks = array_sum(array_map(fn($item) => $item['metrics']['clicks'], $items));
                $totalPurchases = array_sum(array_map(fn($item) => $item['metrics']['purchases'], $items));

                $meta['aggregates'] = [
                    'total_spend' => round($totalSpend, 2),
                    'total_sales' => round($totalSales, 2),
                    'total_clicks' => $totalClicks,
                    'total_purchases' => $totalPurchases,
                    'avg_acos' => ($totalSpend > 0 && $totalSales > 0) ? round(($totalSpend / $totalSales) * 100, 2) : 0,
                    'avg_roas' => $totalSpend > 0 ? round($totalSales / $totalSpend, 2) : 0,
                ];
            }

            return json_encode([
                'success' => true,
                'items' => $items,
                'meta' => $meta,
            ], JSON_PRETTY_PRINT);

        } catch (Throwable $e) {
            return json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'items' => [],
                'meta' => ['count' => 0],
            ], JSON_PRETTY_PRINT);
        }
    }

    /**
     * Apply sorting based on user preference
     */
    private function applySorting($query, string $sortBy, string $sortOrder): void
    {
        $direction = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

        switch (strtolower($sortBy)) {
            case 'sales':
                $query->orderBy('total_sales', $direction);
                break;
            case 'spend':
                $query->orderBy('total_spend', $direction);
                break;
            case 'acos':
                $query->orderBy('acos', $direction);
                break;
            case 'roas':
                $query->orderBy('roas', $direction);
                break;
            case 'clicks':
                $query->orderBy('clicks', $direction);
                break;
            case 'purchases':
                $query->orderBy('purchases', $direction);
                break;
            case 'impressions':
                $query->orderBy('impressions', $direction);
                break;
            case 'conversion_rate':
                $query->orderBy('conversion_rate', $direction);
                break;
            case 'budget':
                $query->orderBy('daily_budget', $direction);
                break;
            default:
                $query->orderBy('total_sales', 'desc');
        }
    }

    /**
     * Group records by campaign and aggregate keyword performance.
     */
    private function groupByCampaign($results): array
    {
        return $results
            ->groupBy(function ($row) {
                return $row->campaign_name . '|' . $row->campaign_id . '|' . $row->country . '|' . $row->campaign_type;
            })
            ->map(function ($group) {
                $first = $group->first();
                $totalSpend = $group->sum('total_spend');
                $totalSales = $group->sum('total_sales');
                $totalPurchases = $group->sum('purchases');
                $totalClicks = $group->sum('clicks');
                $totalImpressions = $group->sum('impressions');
                $avgCpc = (float) $group->avg('cpc');
                $ctr = $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0;
                $conversionRate = $totalClicks > 0 ? ($totalPurchases / $totalClicks) * 100 : 0;
                $acos = $totalSales > 0 ? ($totalSpend / $totalSales) * 100 : 0;
                $roas = $totalSpend > 0 ? ($totalSales / $totalSpend) : 0;

                return [
                    'campaign' => $first->campaign_name,
                    'campaign_id' => $first->campaign_id,
                    'country' => $first->country,
                    'campaign_type' => $first->campaign_type,
                    'campaign_state' => $first->campaign_state,
                    'report_date' => $first->report_date,
                    'keyword_count' => $group->pluck('keyword_text')->unique()->count(),
                    'keywords' => $group->pluck('keyword_text')->unique()->values()->all(),
                    'metrics' => [
                        'spend' => round($totalSpend, 2),
                        'sales' => round($totalSales, 2),
                        'acos' => round($acos, 2),
                        'roas' => round($roas, 2),
                        'purchases' => (int) $totalPurchases,
                        'clicks' => (int) $totalClicks,
                        'impressions' => (int) $totalImpressions,
                        'ctr' => round($ctr, 2),
                        'cpc' => round($avgCpc, 4),
                        'conversion_rate' => round($conversionRate, 2),
                    ],
                    'budget' => [
                        'daily' => round((float) $first->daily_budget, 2),
                        'monthly_estimate' => round((float) $first->estimated_monthly_budget, 2),
                    ],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Sort mapped array results (used for grouped campaign output).
     */
    private function applyArraySorting(array &$items, string $sortBy, string $sortOrder): void
    {
        $direction = strtolower($sortOrder) === 'asc' ? 1 : -1;
        $sortKey = strtolower($sortBy);

        usort($items, function ($a, $b) use ($sortKey, $direction) {
            $valueA = match ($sortKey) {
                'sales' => $a['metrics']['sales'] ?? 0,
                'spend' => $a['metrics']['spend'] ?? 0,
                'acos' => $a['metrics']['acos'] ?? 0,
                'roas' => $a['metrics']['roas'] ?? 0,
                'clicks' => $a['metrics']['clicks'] ?? 0,
                'purchases' => $a['metrics']['purchases'] ?? 0,
                'impressions' => $a['metrics']['impressions'] ?? 0,
                'conversion_rate' => $a['metrics']['conversion_rate'] ?? 0,
                'budget' => $a['budget']['daily'] ?? 0,
                default => $a['metrics']['sales'] ?? 0,
            };

            $valueB = match ($sortKey) {
                'sales' => $b['metrics']['sales'] ?? 0,
                'spend' => $b['metrics']['spend'] ?? 0,
                'acos' => $b['metrics']['acos'] ?? 0,
                'roas' => $b['metrics']['roas'] ?? 0,
                'clicks' => $b['metrics']['clicks'] ?? 0,
                'purchases' => $b['metrics']['purchases'] ?? 0,
                'impressions' => $b['metrics']['impressions'] ?? 0,
                'conversion_rate' => $b['metrics']['conversion_rate'] ?? 0,
                'budget' => $b['budget']['daily'] ?? 0,
                default => $b['metrics']['sales'] ?? 0,
            };

            if ($valueA == $valueB) {
                return 0;
            }

            return ($valueA <=> $valueB) * $direction;
        });
    }

    /**
     * Build a list of applied filters for metadata
     */
    private function buildFiltersApplied(Request $request): array
    {
        $filters = [];

        if (isset($request['date'])) $filters[] = "date: {$request['date']}";
        if (isset($request['date_from']) && isset($request['date_to'])) {
            $filters[] = "date_range: {$request['date_from']} to {$request['date_to']}";
        }
        if (isset($request['keyword'])) $filters[] = "keyword: {$request['keyword']}";
        if (isset($request['campaign'])) $filters[] = "campaign: {$request['campaign']}";
        if (isset($request['asin'])) $filters[] = "asin: {$request['asin']}";
        if (isset($request['country'])) $filters[] = "country: {$request['country']}";
        if (isset($request['campaign_type'])) $filters[] = "campaign_type: {$request['campaign_type']}";
        if (isset($request['campaign_state'])) $filters[] = "campaign_state: {$request['campaign_state']}";
        if (isset($request['keyword_state'])) $filters[] = "keyword_state: {$request['keyword_state']}";
        if (isset($request['group_by'])) $filters[] = "group_by: {$request['group_by']}";
        if (isset($request['min_acos'])) $filters[] = "min_acos: {$request['min_acos']}";
        if (isset($request['max_acos'])) $filters[] = "max_acos: {$request['max_acos']}";
        if (isset($request['min_sales'])) $filters[] = "min_sales: {$request['min_sales']}";
        if (isset($request['max_sales'])) $filters[] = "max_sales: {$request['max_sales']}";

        return $filters;
    }

    /**
     * Define the JSON schema for the tool's parameters.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'date' => $schema
                ->string()
                ->description('Report date in YYYY-MM-DD format. REQUIRED unless both date_from and date_to are provided. Strict mode: no implicit date fallback.')
                ->nullable(),

            'date_from' => $schema
                ->string()
                ->description('Start date for range query (YYYY-MM-DD). REQUIRED together with date_to when date is not provided.')
                ->nullable(),

            'date_to' => $schema
                ->string()
                ->description('End date for range query (YYYY-MM-DD). REQUIRED together with date_from when date is not provided.')
                ->nullable(),

            'keyword' => $schema
                ->string()
                ->description('Search by keyword text (partial match, case-insensitive). Example: "sunshade".')
                ->nullable(),

            'campaign' => $schema
                ->string()
                ->description('Search by campaign name (partial match, case-insensitive).')
                ->nullable(),

            'asin' => $schema
                ->string()
                ->description('Filter by specific ASIN code.')
                ->nullable(),

            'country' => $schema
                ->string()
                ->description('Filter by country code (US, CA, MX, etc.).')
                ->nullable(),

            'campaign_type' => $schema
                ->string()
                ->description('Filter by campaign type: SP (Sponsored Products), SB (Sponsored Brands), SD (Sponsored Display).')
                ->nullable(),

            'campaign_state' => $schema
                ->string()
                ->description('Optional campaign state filter: ENABLED, PAUSED, ARCHIVED. If omitted, includes all states.')
                ->nullable(),

            'keyword_state' => $schema
                ->string()
                ->description('Filter by keyword state: ACTIVE.')
                ->nullable(),

            'min_acos' => $schema
                ->number()
                ->description('Minimum ACOS threshold (e.g., 20 for ACOS >= 20%).')
                ->nullable(),

            'max_acos' => $schema
                ->number()
                ->description('Maximum ACOS threshold (e.g., 50 for ACOS <= 50%).')
                ->nullable(),

            'min_roas' => $schema
                ->number()
                ->description('Minimum ROAS threshold (e.g., 2 for ROAS >= 2x).')
                ->nullable(),

            'max_roas' => $schema
                ->number()
                ->description('Maximum ROAS threshold (e.g., 5 for ROAS <= 5x).')
                ->nullable(),

            'min_sales' => $schema
                ->number()
                ->description('Minimum sales threshold in dollars.')
                ->nullable(),

            'max_sales' => $schema
                ->number()
                ->description('Maximum sales threshold in dollars.')
                ->nullable(),

            'min_spend' => $schema
                ->number()
                ->description('Minimum spend threshold in dollars.')
                ->nullable(),

            'max_spend' => $schema
                ->number()
                ->description('Maximum spend threshold in dollars.')
                ->nullable(),

            'min_budget' => $schema
                ->number()
                ->description('Minimum daily budget threshold.')
                ->nullable(),

            'max_budget' => $schema
                ->number()
                ->description('Maximum daily budget threshold.')
                ->nullable(),

            'min_clicks' => $schema
                ->integer()
                ->description('Minimum clicks threshold.')
                ->nullable(),

            'min_purchases' => $schema
                ->integer()
                ->description('Minimum purchases threshold.')
                ->nullable(),

            'sort_by' => $schema
                ->string()
                ->description('Sort by: sales (default), spend, acos, roas, clicks, purchases, impressions, conversion_rate, budget.')
                ->nullable(),

            'sort_order' => $schema
                ->string()
                ->description('Sort order: asc (ascending) or desc (descending). Defaults to desc.')
                ->nullable(),

            'limit' => $schema
                ->integer()
                ->min(1)
                ->max(500)
                ->description('Maximum results to return (1-500). Defaults to 25.')
                ->nullable(),

            'group_by' => $schema
                ->string()
                ->description('Grouping mode: campaign (aggregated campaign-level output) or keyword (default row-level output).')
                ->nullable(),
        ];
    }
}
