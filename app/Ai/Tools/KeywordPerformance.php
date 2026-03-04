<?php

namespace App\Ai\Tools;

use App\Services\Ai\AiChatBotServices;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

class KeywordPerformance implements Tool
{
    private string $marketTz = 'America/Los_Angeles';

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return implode(' ', [
            'Fetch keyword performance metrics from amz_keyword_recommendations table.',
            'Supports detailed filtering: ACOS range (min/max), sales range (min/max), spend range (min/max).',
            'Example queries: "keywords with ACOS between 20-30%", "keywords with sales over $100 and spend under $50".',
            'Returns keyword text, sales, spend, ACOS, purchases, clicks, bid recommendations.',
            'Data uses PRE-CALCULATED aggregates: 1d (yesterday), 7d (last 7 days), 14d (last 14 days), 30d (last 30 days).',
            'Optimized for millions of records using indexed date, country, and campaign_types columns.',
            'Optional inputs: date (YYYY-MM-DD, defaults to yesterday), country, campaign_type (SP|SB), period (1d|7d|14d|30d),',
            'max_acos (filter keywords below this ACOS %), min_sales (minimum sales threshold), sort_by (sales|acos|spend|clicks), limit (1..100).',
            'Historical data only - use yesterday or earlier dates.',
        ]);
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        try {
            /** @var AiChatBotServices $service */
            $service = app(AiChatBotServices::class);

            $now = Carbon::now($this->marketTz);
            $todayDate = $now->copy()->toDateString();
            $yesterdayDate = $now->copy()->subDay()->toDateString();

            // Parse parameters with defaults
            $date = $yesterdayDate;  // Default to yesterday (today's data is based on yesterday)
            $country = null;
            $campaignType = null;
            $period = '7d';
            $limit = 25;
            $minAcos = null;
            $maxAcos = null;
            $minSales = null;
            $maxSales = null;
            $minSpend = null;
            $maxSpend = null;
            $sortBy = 'sales';

            // Parse date
            if (isset($request['date']) && is_string($request['date']) && trim($request['date']) !== '') {
                $date = trim($request['date']);
            }

            // Parse country
            if (isset($request['country']) && is_string($request['country']) && trim($request['country']) !== '') {
                $country = trim($request['country']);
            }

            // Parse campaign type
            if (isset($request['campaign_type']) && is_string($request['campaign_type']) && trim($request['campaign_type']) !== '') {
                $campaignType = trim($request['campaign_type']);
            }

            // Parse period
            if (isset($request['period']) && is_string($request['period']) && trim($request['period']) !== '') {
                $period = trim($request['period']);
            }

            // Parse ACOS filters
            if (isset($request['min_acos'])) {
                $minAcos = (float) $request['min_acos'];
                if ($minAcos < 0) {
                    $minAcos = null;
                }
            }
            if (isset($request['max_acos'])) {
                $maxAcos = (float) $request['max_acos'];
                if ($maxAcos < 0) {
                    $maxAcos = null;
                }
            }

            // Parse sales filters
            if (isset($request['min_sales'])) {
                $minSales = (float) $request['min_sales'];
                if ($minSales < 0) {
                    $minSales = null;
                }
            }
            if (isset($request['max_sales'])) {
                $maxSales = (float) $request['max_sales'];
                if ($maxSales < 0) {
                    $maxSales = null;
                }
            }

            // Parse spend filters
            if (isset($request['min_spend'])) {
                $minSpend = (float) $request['min_spend'];
                if ($minSpend < 0) {
                    $minSpend = null;
                }
            }
            if (isset($request['max_spend'])) {
                $maxSpend = (float) $request['max_spend'];
                if ($maxSpend < 0) {
                    $maxSpend = null;
                }
            }

            // Parse sort by
            if (isset($request['sort_by']) && is_string($request['sort_by']) && trim($request['sort_by']) !== '') {
                $sortBy = trim($request['sort_by']);
            }

            // Parse limit
            if (isset($request['limit'])) {
                $limit = (int) $request['limit'];
            }

            // Validate limit bounds
            if ($limit < 1) {
                $limit = 1;
            }
            if ($limit > 100) {
                $limit = 100;
            }

            // Validate date format
            $parsed = Carbon::createFromFormat('Y-m-d', $date, $this->marketTz);
            if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
                throw new \InvalidArgumentException('Invalid date. Expected format: YYYY-MM-DD.');
            }

            // Ensure date is historical (yesterday or earlier)
            if ($parsed->startOfDay()->greaterThanOrEqualTo($now->copy()->startOfDay())) {
                throw new \InvalidArgumentException(
                    "Keyword performance is available only for yesterday and previous days. Today is {$todayDate}; latest complete date is {$yesterdayDate}."
                );
            }

            // Call service
            $result = $service->keywordPerformance(
                date: $date,
                country: $country,
                campaignType: $campaignType,
                period: $period,
                limit: $limit,
                minAcos: $minAcos,
                maxAcos: $maxAcos,
                minSales: $minSales,
                maxSales: $maxSales,
                minSpend: $minSpend,
                maxSpend: $maxSpend,
                sortBy: $sortBy,
            );

            return json_encode([
                'items' => $result['keywords'] ?? [],
                'summary' => $result['summary'] ?? [],
                'meta' => [
                    'tool' => 'Keyword Performance',
                    'date' => $date,
                    'period' => $result['period'] ?? $period,
                    'timezone' => $this->marketTz,
                    'keywords_found' => count($result['keywords'] ?? []),
                ],
            ], JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            return json_encode([
                'error' => $e->getMessage(),
                'items' => [],
                'summary' => [],
                'meta' => [
                    'tool' => 'Keyword Performance',
                    'timezone' => $this->marketTz,
                ],
            ], JSON_THROW_ON_ERROR);
        }
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'date' => $schema
                ->string()
                ->description('Report date in YYYY-MM-DD format. Defaults to yesterday. Must be yesterday or earlier (historical data only).')
                ->nullable(),

            'country' => $schema
                ->string()
                ->description('Optional country filter (US, CA, MX, etc.). If omitted, queries all countries.')
                ->nullable(),

            'campaign_type' => $schema
                ->string()
                ->description('Optional campaign type filter: SP (Sponsored Products) or SB (Sponsored Brands).')
                ->nullable(),

            'period' => $schema
                ->string()
                ->description('Time period for pre-calculated metrics: 1d (single day), 7d (last 7 days), 14d (last 14 days), 30d (last 30 days). Defaults to 7d.')
                ->nullable(),

            'min_acos' => $schema
                ->number()
                ->description('Filter keywords with ACOS greater than or equal to this value (e.g., 20 for ACOS >= 20%).')
                ->nullable(),

            'max_acos' => $schema
                ->number()
                ->description('Filter keywords with ACOS less than or equal to this value (e.g., 30 for ACOS <= 30%). Excludes zero ACOS.')
                ->nullable(),

            'min_sales' => $schema
                ->number()
                ->description('Filter keywords with sales greater than or equal to this value (e.g., 100 for sales >= $100).')
                ->nullable(),

            'max_sales' => $schema
                ->number()
                ->description('Filter keywords with sales less than or equal to this value (e.g., 500 for sales <= $500).')
                ->nullable(),

            'min_spend' => $schema
                ->number()
                ->description('Filter keywords with spend greater than or equal to this value (e.g., 50 for spend >= $50).')
                ->nullable(),

            'max_spend' => $schema
                ->number()
                ->description('Filter keywords with spend less than or equal to this value (e.g., 200 for spend <= $200).')
                ->nullable(),

            'sort_by' => $schema
                ->string()
                ->description('Sort results by: sales (default, descending), acos (ascending), spend (descending), clicks (descending), purchases (descending).')
                ->nullable(),

            'limit' => $schema
                ->integer()
                ->min(1)
                ->max(100)
                ->description('Number of keywords to return (1..100). Defaults to 25. Optimized for large datasets.')
                ->nullable(),
        ];
    }
}
