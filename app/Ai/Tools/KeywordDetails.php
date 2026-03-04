<?php

namespace App\Ai\Tools;

use App\Services\Ai\AiChatBotServices;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

class KeywordDetails implements Tool
{
    private string $marketTz = 'America/Los_Angeles';

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return implode(' ', [
            'Fetch keyword details and related ASINs via campaign_id.',
            'Use this when asked for ASINs tied to a keyword or keyword_id.',
            'Searchable by keyword text, keyword_id, campaign_id, asin, sku, country, and campaign_type.',
            'Queries amz_keyword_recommendations for keyword-to-campaign mapping, then resolves ASINs using product performance reports.',
            'Optimized for large datasets with strict limits on returned rows.',
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

            $date = $yesterdayDate;
            $keyword = null;
            $keywordId = null;
            $campaignId = null;
            $asin = null;
            $sku = null;
            $country = null;
            $campaignType = null;
            $limit = 25;
            $asinLimit = 50;

            if (isset($request['date']) && is_string($request['date']) && trim($request['date']) !== '') {
                $date = trim($request['date']);
            }

            if (isset($request['keyword']) && is_string($request['keyword']) && trim($request['keyword']) !== '') {
                $keyword = trim($request['keyword']);
            }

            if (isset($request['keyword_id']) && is_string($request['keyword_id']) && trim($request['keyword_id']) !== '') {
                $keywordId = trim($request['keyword_id']);
            }

            if (isset($request['campaign_id']) && is_string($request['campaign_id']) && trim($request['campaign_id']) !== '') {
                $campaignId = trim($request['campaign_id']);
            }

            if (isset($request['asin']) && is_string($request['asin']) && trim($request['asin']) !== '') {
                $asin = trim($request['asin']);
            }

            if (isset($request['sku']) && is_string($request['sku']) && trim($request['sku']) !== '') {
                $sku = trim($request['sku']);
            }

            if (isset($request['country']) && is_string($request['country']) && trim($request['country']) !== '') {
                $country = trim($request['country']);
            }

            if (isset($request['campaign_type']) && is_string($request['campaign_type']) && trim($request['campaign_type']) !== '') {
                $campaignType = trim($request['campaign_type']);
            }

            if (isset($request['limit'])) {
                $limit = (int) $request['limit'];
            }

            if (isset($request['asin_limit'])) {
                $asinLimit = (int) $request['asin_limit'];
            }

            if ($limit < 1) {
                $limit = 1;
            }
            if ($limit > 100) {
                $limit = 100;
            }

            if ($asinLimit < 1) {
                $asinLimit = 1;
            }
            if ($asinLimit > 200) {
                $asinLimit = 200;
            }

            $parsed = Carbon::createFromFormat('Y-m-d', $date, $this->marketTz);
            if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
                throw new \InvalidArgumentException('Invalid date. Expected format: YYYY-MM-DD.');
            }

            if ($parsed->startOfDay()->greaterThanOrEqualTo($now->copy()->startOfDay())) {
                throw new \InvalidArgumentException(
                    "Keyword details are available only for yesterday and previous days. Today is {$todayDate}; latest complete date is {$yesterdayDate}."
                );
            }

            $result = $service->keywordDetails(
                date: $date,
                keyword: $keyword,
                keywordId: $keywordId,
                campaignId: $campaignId,
                asin: $asin,
                sku: $sku,
                country: $country,
                campaignType: $campaignType,
                limit: $limit,
                asinLimit: $asinLimit
            );

            return json_encode([
                'items' => $result['keywords'] ?? [],
                'asins' => $result['asins'] ?? [],
                'summary' => $result['summary'] ?? [],
                'meta' => [
                    'tool' => 'Keyword Details',
                    'date' => $date,
                    'timezone' => $this->marketTz,
                    'keywords_found' => count($result['keywords'] ?? []),
                    'asins_found' => count($result['asins'] ?? []),
                ],
            ], JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            return json_encode([
                'error' => $e->getMessage(),
                'items' => [],
                'asins' => [],
                'summary' => [],
                'meta' => [
                    'tool' => 'Keyword Details',
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

            'keyword' => $schema
                ->string()
                ->description('Keyword text search (partial match supported).')
                ->nullable(),

            'keyword_id' => $schema
                ->string()
                ->description('Exact keyword_id to search.')
                ->nullable(),

            'campaign_id' => $schema
                ->string()
                ->description('Exact campaign_id to search.')
                ->nullable(),

            'asin' => $schema
                ->string()
                ->description('Optional ASIN filter when resolving products from campaigns.')
                ->nullable(),

            'sku' => $schema
                ->string()
                ->description('Optional SKU filter when resolving products from campaigns.')
                ->nullable(),

            'country' => $schema
                ->string()
                ->description('Optional country filter (US, CA, MX, etc.). If omitted, queries all countries.')
                ->nullable(),

            'campaign_type' => $schema
                ->string()
                ->description('Optional campaign type filter: SP (Sponsored Products), SB (Sponsored Brands), or SD (Sponsored Display).')
                ->nullable(),

            'limit' => $schema
                ->integer()
                ->min(1)
                ->max(100)
                ->description('Number of keywords to return (1..100). Defaults to 25.')
                ->nullable(),

            'asin_limit' => $schema
                ->integer()
                ->min(1)
                ->max(200)
                ->description('Number of ASINs to return (1..200). Defaults to 50.')
                ->nullable(),
        ];
    }
}
