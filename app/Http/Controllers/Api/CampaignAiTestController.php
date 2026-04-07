<?php

namespace App\Http\Controllers\Api;

use App\Services\Ai\CampaignAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CampaignAiTestController
{
    /**
     * Test endpoint to verify CampaignAiService response
     * GET /api/test/campaign-keywords
     */
    public function testCampaignKeywords(CampaignAiService $service): JsonResponse
    {
        try {
            // Hardcoded test values - adjust based on your data
            $asin = 'B07K6YKZBK'; // Replace with real ASIN from your database
            $campaignType = 'SP'; // SP, SB, SD, or null for all
            $country = 'US'; // US, UK, DE, etc., or null for all
            $keywords = 'Snow Cover_Medium_ASIN_SP_Comp_TOS_BDK_Bl@ck'; // Search term (optional)
            $date = null; // Defaults to yesterday if null

            Log::info('Testing CampaignAiService', [
                'asin' => $asin,
                'campaignType' => $campaignType,
                'country' => $country,
                'keywords' => $keywords,
                'date' => $date,
            ]);

            $result = $service->getCampaignKeywords(
                $asin,
                $campaignType,
                $country,
                $keywords,
                $date
            );
        
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Campaign keywords fetched successfully',
            ]);

        } catch (\Throwable $e) {
            Log::error('CampaignAiService test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to fetch campaign keywords',
            ], 500);
        }
    }

    /**
     * Test search functionality
     * GET /api/test/campaign-keywords/search?term=car cover
     */
    public function testSearchKeywords(CampaignAiService $service): JsonResponse
    {
        try {
            // Hardcoded test values
            $asin = 'B07K6YKZBK';
            $searchTerm = 'car cover'; // Search term to test
            $limit = 50;

            Log::info('Testing CampaignAiService search', [
                'asin' => $asin,
                'searchTerm' => $searchTerm,
                'limit' => $limit,
            ]);

            $result = $service->searchKeywords($asin, $searchTerm, $limit);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Search completed successfully',
            ]);

        } catch (\Throwable $e) {
            Log::error('CampaignAiService search test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Search failed',
            ], 500);
        }
    }

    /**
     * Test with custom filters
     * GET /api/test/campaign-keywords/custom?asin=B07K6YKZBK&type=SP&country=US
     */
    public function testCustomFilters(CampaignAiService $service): JsonResponse
    {
        try {
            // Get parameters from request (with defaults)
            $asin = request()->get('asin', 'B07K6YKZBK');
            $campaignType = request()->get('type'); // SP, SB, SD
            $country = request()->get('country'); // US, UK, etc
            $keywords = request()->get('keywords'); // search term
            $date = request()->get('date'); // YYYY-MM-DD

            // Validate ASIN
            if (!$asin) {
                return response()->json([
                    'success' => false,
                    'error' => 'ASIN parameter is required',
                ], 422);
            }

            Log::info('Testing CampaignAiService with custom filters', [
                'asin' => $asin,
                'campaignType' => $campaignType,
                'country' => $country,
                'keywords' => $keywords,
                'date' => $date,
            ]);

            $result = $service->getCampaignKeywords(
                $asin,
                $campaignType,
                $country,
                $keywords,
                $date
            );

            return response()->json([
                'success' => true,
                'filters' => [
                    'asin' => $asin,
                    'campaignType' => $campaignType,
                    'country' => $country,
                    'keywords' => $keywords,
                    'date' => $date,
                ],
                'data' => $result,
                'message' => 'Custom filter test completed successfully',
            ]);

        } catch (\Throwable $e) {
            Log::error('CampaignAiService custom filter test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Custom filter test failed',
            ], 500);
        }
    }

    /**
     * Test the CampaignKeywords Tool directly
     * GET /api/test/campaign-keywords-tool
     */
    public function testCampaignKeywordsTool(): JsonResponse
    {
        try {
            // Simulate a tool request
            $tool = new \App\Ai\Tools\CampaignKeywords();
            
            // Create a mock request with hardcoded values
            $mockRequest = [
                'asin' => 'B07K6YKZBK',
                'campaign_type' => 'SP',
                'country' => 'US',
                'search_term' => null,
                'date' => null,
            ];

            Log::info('Testing CampaignKeywords Tool', $mockRequest);

            // Use reflection to call handle with mock request
            $response = $tool->handle((object)$mockRequest);

            // Decode JSON response
            $decoded = json_decode($response, true);

            return response()->json([
                'success' => true,
                'tool_response' => $decoded,
                'raw_response' => $response,
                'message' => 'Tool test completed successfully',
            ]);

        } catch (\Throwable $e) {
            Log::error('CampaignKeywords Tool test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Tool test failed',
            ], 500);
        }
    }

    /**
     * Test deep analysis functionality
     * GET /api/test/campaign-keywords/deep-analysis?asin=B07K6YKZBK&grouping=performance
     */
    public function testDeepAnalysis(CampaignAiService $service): JsonResponse
    {
        try {
            // Get parameters from request
            $asin = request()->get('asin', 'B07K6YKZBK');
            $grouping = request()->get('grouping', 'performance'); // performance|campaign|keyword
            $minSpend = (float) request()->get('min_spend', 5.0);
            $maxAcos = (float) request()->get('max_acos', 50.0);
            $minClicks = (int) request()->get('min_clicks', 0);
            $includeMetrics = request()->boolean('include_metrics', true);
            $includeTrends = request()->boolean('include_trends', true);
            $includeRecommendations = request()->boolean('include_recommendations', true);
            $limitKeywords = (int) request()->get('limit_keywords', 15);

            // Validate ASIN
            if (!$asin) {
                return response()->json([
                    'success' => false,
                    'error' => 'ASIN parameter is required',
                ], 422);
            }

            Log::info('Testing deep analysis', [
                'asin' => $asin,
                'grouping' => $grouping,
                'min_spend' => $minSpend,
                'max_acos' => $maxAcos,
                'min_clicks' => $minClicks,
            ]);

            // Build options
            $options = [
                'grouping' => $grouping,
                'min_spend' => $minSpend,
                'max_acos' => $maxAcos,
                'min_clicks' => $minClicks,
                'include_metrics' => $includeMetrics,
                'include_trends' => $includeTrends,
                'include_recommendations' => $includeRecommendations,
                'limit_keywords' => $limitKeywords,
            ];

            // Get buildAsinCampaignDetails response first
            $controller = new \App\Http\Controllers\Products\SellingAdsItemController();
            $campaignDetailsResponse = $controller->buildAsinCampaignDetails($asin);
            
            // If response is a view or response object, we need the array
            if (method_exists($campaignDetailsResponse, 'getData')) {
                $data = $campaignDetailsResponse->getData();
                $campaignDetailsArray = $data['data'] ?? $data;
            } else {
                $campaignDetailsArray = (array)$campaignDetailsResponse;
            }

            // Call deep analysis
            $analysis = $service->deepAnalyzeCampaignKeywords(
                $campaignDetailsArray,
                $asin,
                $options
            );

            return response()->json([
                'success' => true,
                'asin' => $asin,
                'options' => $options,
                'analysis' => $analysis,
                'message' => 'Deep analysis completed successfully',
            ]);

        } catch (\Throwable $e) {
            Log::error('Deep analysis test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Deep analysis test failed',
            ], 500);
        }
    }
}
