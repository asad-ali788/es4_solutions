<?php

namespace App\Ai\Tools;

use App\Services\Ai\AiChatBotServices;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

class CampaignDetails implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return implode(' ', [
            'Fetch detailed information about Amazon advertising campaigns and their products.',
            'Use for campaign details: Returns campaign name, state (ENABLED/PAUSED), daily budget, campaign type, targeting type.',
            'Search by campaign name using campaign_name parameter for partial or exact match.',
            'Use for product lookup: When user asks which ASIN/SKU belongs to a campaign, fetches product data from performance reports.',
            'Filters by: country (US, CA, MX), campaign type (SP, SB, SD), campaign state (ENABLED/PAUSED), campaign name, specific campaign IDs.',
            'Can query multiple campaigns at once via campaign_ids parameter.',
            'For product queries, returns campaign_name only (not campaign_id) and queries all three campaign tables.',
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

            // Check if user is asking about products (ASIN/SKU)
            $asin  = null;
            $sku   = null;
            if (isset($request['asin']) && is_string($request['asin']) && trim($request['asin']) !== '') {
                $asin = trim($request['asin']);
            }
            if (isset($request['sku']) && is_string($request['sku']) && trim($request['sku']) !== '') {
                $sku = trim($request['sku']);
            }

            // Parse other parameters
            $country       = null;
            $campaignType  = null;
            $limit         = 15;
            $campaignState = 'ENABLED';
            $campaignIds   = null;
            $campaignId    = null;
            $campaignName  = null;

            // Parse country parameter
            if (isset($request['country']) && is_string($request['country']) && trim($request['country']) !== '') {
                $country = trim($request['country']);
            }

            // Parse campaign type parameter
            if (isset($request['campaign_type']) && is_string($request['campaign_type']) && trim($request['campaign_type']) !== '') {
                $campaignType = trim($request['campaign_type']);
            }

            // Parse campaign state parameter - case insensitive
            if (isset($request['campaign_state']) && is_string($request['campaign_state']) && trim($request['campaign_state']) !== '') {
                $campaignState = trim($request['campaign_state']);
            }

            // Parse campaign name parameter - for searching by name
            if (isset($request['campaign_name']) && is_string($request['campaign_name']) && trim($request['campaign_name']) !== '') {
                $campaignName = trim($request['campaign_name']);
            }

            // Parse campaign ID for product queries
            if (isset($request['campaign_id']) && !empty($request['campaign_id'])) {
                $campaignId = (string) $request['campaign_id'];
            }

            // Parse campaign IDs - can be array or comma-separated string
            if (isset($request['campaign_ids'])) {
                if (is_array($request['campaign_ids'])) {
                    $campaignIds = array_filter($request['campaign_ids'], fn($id) => !empty($id));
                    if (!empty($campaignIds)) {
                        $campaignIds = array_map(fn($id) => (int) $id, $campaignIds);
                    } else {
                        $campaignIds = null;
                    }
                } elseif (is_string($request['campaign_ids']) && trim($request['campaign_ids']) !== '') {
                    // Handle comma-separated IDs
                    $ids = explode(',', $request['campaign_ids']);
                    $campaignIds = array_filter(
                        array_map(fn($id) => (int) trim($id), $ids),
                        fn($id) => $id > 0
                    );
                    if (!empty($campaignIds)) {
                        $campaignIds = array_values($campaignIds);
                    } else {
                        $campaignIds = null;
                    }
                }
            }

            // Parse limit parameter
            if (isset($request['limit'])) {
                $limit = (int) $request['limit'];
            }

            // If ASIN or SKU is provided, query products instead of campaigns
            if (!empty($asin) || !empty($sku)) {
                // For product queries, allow higher limit (1-100)
                if ($limit < 1) {
                    $limit = 1;
                }
                if ($limit > 100) {
                    $limit = 100;
                }

                // Call product query
                $result = $service->campaignProducts(
                    asin: $asin,
                    sku: $sku,
                    campaignId: $campaignId,
                    country: $country,
                    limit: $limit,
                );

                return json_encode([
                    'items' => $result['products'] ?? [],
                    'summary' => $result['summary'] ?? [],
                    'meta' => [
                        'tool' => 'Campaign Details - Product Lookup',
                        'query_type' => 'product',
                        'products_found' => count($result['products'] ?? []),
                        'asin' => $asin,
                        'sku' => $sku,
                    ],
                ], JSON_THROW_ON_ERROR);
            }

            // Campaign query - validate limit bounds
            if ($limit < 1) {
                $limit = 1;
            }

            if ($limit > 50) {
                $limit = 50;
            }

            // Call service with optimized parameters
            $result = $service->campaignDetails(
                country: $country,
                campaignType: $campaignType,
                campaignState: $campaignState,
                limit: $limit,
                campaignIds: $campaignIds,
                campaignName: $campaignName,
            );

            return json_encode([
                'items' => $result['campaigns'] ?? [],
                'summary' => $result['summary'] ?? [],
                'meta' => [
                    'tool' => 'Campaign Details',
                    'query_type' => 'campaign',
                    'campaigns_found' => count($result['campaigns'] ?? []),
                ],
            ], JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {

            return json_encode([
                'error' => $e->getMessage(),
                'items' => [],
                'summary' => [],
                'meta' => [
                    'tool' => 'Campaign Details',
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
            'asin' => $schema
                ->string()
                ->description('Search for products by ASIN. Returns which campaigns contain this ASIN.')
                ->nullable(),

            'sku' => $schema
                ->string()
                ->description('Search for products by SKU. Returns which campaigns contain this SKU.')
                ->nullable(),

            'country' => $schema
                ->string()
                ->description('Optional country filter (US, CA, MX, etc.). If omitted, queries all countries.')
                ->nullable(),

            'campaign_type' => $schema
                ->string()
                ->description('Optional campaign type filter: SP (Sponsored Products), SB (Sponsored Brands), or SD (Sponsored Display).')
                ->nullable(),

            'campaign_state' => $schema
                ->string()
                ->description('Optional campaign state filter: ENABLED or PAUSED. If omitted, searches both states. Case-insensitive.')
                ->nullable(),

            'campaign_name' => $schema
                ->string()
                ->description('Optional campaign name search. Use this to find campaigns by name. Partial match supported.')
                ->nullable(),

            'campaign_id' => $schema
                ->string()
                ->description('Optional specific campaign ID to filter product results. Use with asin or sku to get products for a specific campaign.')
                ->nullable(),

            'campaign_ids' => $schema
                ->array()
                ->items(
                    $schema->integer()
                        ->description('Campaign ID')
                )
                ->description('Optional array of specific campaign IDs to fetch campaign details. Not used with asin/sku queries.')
                ->nullable(),

            'limit' => $schema
                ->integer()
                ->min(1)
                ->max(100)
                ->description('Number of results to return. For campaigns: 1..50 (default 15). For products: 1..100 (default 25).')
                ->nullable(),
        ];
    }
}


