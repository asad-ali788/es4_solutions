<?php

namespace App\Ai\Tools;

use App\Services\Ai\AiChatBotServices;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

class CampaignPerformance implements Tool
{
    private string $marketTz = 'America/Los_Angeles';

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return implode(' ', [
            'Fetch campaign performance summary for a historical report date (yesterday or earlier).',
            'Returns ACOS range, sales range, spend range, bad-performing campaigns, and top campaign list.',
            'Supports detailed filtering: ACOS range (min/max), sales range (min/max), spend range (min/max).',
            'Example queries: "campaigns with ACOS between 20-30%", "campaigns with sales over $500 and spend under $200".',
            'Optional inputs: date (YYYY-MM-DD), country, campaign_type (SP|SB|SD), period (1d|7d|14d|30d), limit (1..50).',
            'Do not use current day; campaign reporting is historical-only.',
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
            $country = null;
            $campaignType = null;
            $period = '7d';
            $limit = 15;
            $minAcos = null;
            $maxAcos = null;
            $minSales = null;
            $maxSales = null;
            $minSpend = null;
            $maxSpend = null;

            if (isset($request['date']) && is_string($request['date']) && trim($request['date']) !== '') {
                $date = trim($request['date']);
            }

            if (isset($request['country']) && is_string($request['country']) && trim($request['country']) !== '') {
                $country = trim($request['country']);
            }

            if (isset($request['campaign_type']) && is_string($request['campaign_type']) && trim($request['campaign_type']) !== '') {
                $campaignType = trim($request['campaign_type']);
            }

            if (isset($request['period']) && is_string($request['period']) && trim($request['period']) !== '') {
                $period = trim($request['period']);
            }

            if (isset($request['limit'])) {
                $limit = (int) $request['limit'];
            }

            if ($limit < 1) {
                $limit = 1;
            }

            if ($limit > 50) {
                $limit = 50;
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

            $parsed = Carbon::createFromFormat('Y-m-d', $date, $this->marketTz);
            if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
                throw new \InvalidArgumentException('Invalid date. Expected format: YYYY-MM-DD.');
            }

            if ($parsed->startOfDay()->greaterThanOrEqualTo($now->copy()->startOfDay())) {
                throw new \InvalidArgumentException(
                    "Campaign performance is available only for yesterday and previous days. Today is {$todayDate}; latest complete date is {$yesterdayDate}."
                );
            }

            $result = $service->campaignPerformance(
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
            );

            return json_encode([
                'items' => $result['campaigns'] ?? [],
                'bad_campaigns' => $result['bad_campaigns'] ?? [],
                'ranges' => $result['ranges'] ?? [],
                'summary' => $result['summary'] ?? [],
                'meta' => [
                    'tool' => 'campaign_performance',
                    'date' => $date,
                    'today_date' => $todayDate,
                    'latest_complete_date' => $yesterdayDate,
                    'period' => $period,
                    'country' => $country,
                    'campaign_type' => $campaignType,
                    'limit' => $limit,
                    'timezone' => $this->marketTz,
                    'note' => 'Historical results only (yesterday/previous days). Current day is not supported.',
                ],
            ], JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            $now = Carbon::now($this->marketTz);

            return json_encode([
                'error' => $e->getMessage(),
                'items' => [],
                'bad_campaigns' => [],
                'ranges' => [],
                'summary' => [],
                'meta' => [
                    'tool' => 'campaign_performance',
                    'timezone' => $this->marketTz,
                    'today_date' => $now->copy()->toDateString(),
                    'latest_complete_date' => $now->copy()->subDay()->toDateString(),
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
                ->description('Optional campaign type filter: SP (Sponsored Products), SB (Sponsored Brands), or SD (Sponsored Display).')
                ->nullable(),

            'period' => $schema
                ->string()
                ->description('Time period: 1d (single day), 7d (last 7 days, default), 14d (last 14 days), 30d (last 30 days).')
                ->nullable(),

            'min_acos' => $schema
                ->number()
                ->description('Filter campaigns with ACOS greater than or equal to this value (e.g., 20 for ACOS >= 20%).')
                ->nullable(),

            'max_acos' => $schema
                ->number()
                ->description('Filter campaigns with ACOS less than or equal to this value (e.g., 30 for ACOS <= 30%). Excludes zero ACOS.')
                ->nullable(),

            'min_sales' => $schema
                ->number()
                ->description('Filter campaigns with sales greater than or equal to this value (e.g., 500 for sales >= $500).')
                ->nullable(),

            'max_sales' => $schema
                ->number()
                ->description('Filter campaigns with sales less than or equal to this value (e.g., 1000 for sales <= $1000).')
                ->nullable(),

            'min_spend' => $schema
                ->number()
                ->description('Filter campaigns with spend greater than or equal to this value (e.g., 100 for spend >= $100).')
                ->nullable(),

            'max_spend' => $schema
                ->number()
                ->description('Filter campaigns with spend less than or equal to this value (e.g., 300 for spend <= $300).')
                ->nullable(),

            'limit' => $schema
                ->integer()
                ->min(1)
                ->max(50)
                ->description('Number of top campaigns to return (1..50). Defaults to 15.')
                ->nullable(),
            'date' => $schema
                ->string()
                ->description('Report date in YYYY-MM-DD. Must be yesterday or earlier. If omitted, defaults to yesterday.')
                ->nullable(),

            'country' => $schema
                ->string()
                ->description('Optional country filter (for example US, CA, MX). If omitted, uses all countries.')
                ->nullable(),

            'campaign_type' => $schema
                ->string()
                ->description('Optional campaign type filter: SP, SB, or SD.')
                ->nullable(),

            'period' => $schema
                ->string()
                ->description('Time period for pre-calculated metrics: 1d (single day), 7d (last 7 days), 14d (last 14 days), 30d (last 30 days). Defaults to 7d.')
                ->nullable(),

            'limit' => $schema
                ->integer()
                ->min(1)
                ->max(50)
                ->description('Number of campaign rows to return (1..50). Defaults to 15.')
                ->nullable(),
        ];
    }
}
