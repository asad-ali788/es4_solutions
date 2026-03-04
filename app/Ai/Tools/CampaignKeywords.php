<?php

namespace App\Ai\Tools;

use App\Services\Ai\CampaignAiService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

class CampaignKeywords implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Fetch all keywords and recommended keywords for campaigns. Returns keyword performance metrics (spend, sales, ACOS, clicks, bid) and recommended keywords for optimization. Searchable by keyword text, campaign_id. Filters: campaign_type (SP/SB/SD), country (US/CA/MX/etc), date.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        try {
            /** @var CampaignAiService $service */
            $service = app(CampaignAiService::class);

            // Get ASIN from context
            $asin = auth()->user()?->current_asin;
            if (!$asin && isset($request['asin'])) {
                $asin = trim($request['asin']);
            }

            if (!$asin) {
                return json_encode(['error' => 'ASIN context required']);
            }

            // Parse filters
            $campaignType = isset($request['campaign_type']) && is_string($request['campaign_type']) 
                ? trim($request['campaign_type']) ?: null 
                : null;

            $country = isset($request['country']) && is_string($request['country']) 
                ? trim($request['country']) ?: null 
                : null;

            $searchTerm = isset($request['search_term']) && is_string($request['search_term']) 
                ? trim($request['search_term']) ?: null 
                : null;

            $selectedDate = isset($request['date']) && is_string($request['date']) 
                ? trim($request['date']) ?: null 
                : null;

            // Get data
            $data = $service->getCampaignKeywords(
                $asin,
                $campaignType,
                $country,
                $searchTerm,
                $selectedDate
            );

            // Return minimal JSON for AI to process
            return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get the schema for tool parameters.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'asin' => $schema->string('ASIN code (e.g., B07K6YKZBK)'),
            'campaign_type' => $schema->string('Filter: SP, SB, or SD'),
            'country' => $schema->string('Filter: US, UK, DE, FR, IT, ES, JP, CA, AU, MX'),
            'search_term' => $schema->string('Search keywords by text'),
            'date' => $schema->string('Date filter (YYYY-MM-DD)'),
        ];
    }
}

