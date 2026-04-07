<?php

namespace App\Services\Api;

use AmazonAdvertisingApi\Client;
use App\Services\Ads\AmazonAdsJsonClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AmazonAdsService
{
    protected Client $client;
    protected AmazonAdsJsonClient $customClient;

    public function __construct()
    {
        $config = [
            'clientId'     => config('amazon_ads.client_id'),
            'clientSecret' => config('amazon_ads.client_secret'),
            'refreshToken' => config('amazon_ads.refresh_token'),
            'region'       => config('amazon_ads.region'),
            'appUserAgent' => config('amazon_ads.user_agent'),
            'sandbox'      => config('amazon_ads.sandbox', false),
            'accessToken'  => null,
            'isUseProxy'   => false,
            'saveFile'     => false,
            'headerAccept' => ''
        ];

        $this->client = new Client($config);
        $this->customClient = new AmazonAdsJsonClient($config);
    }

    public function getProfiles(): array
    {
        return $this->client->listProfiles();
    }

    public function getProfileIdByCountry(string $countryCode): ?string
    {
        try {
            $rawProfiles = $this->client->listProfiles();

            $response    = $rawProfiles['response'] ?? [];
            $profiles    = is_array($response) ? $response : json_decode($response, true);
            if (!is_array($profiles)) {
                Log::error("Invalid profile response for country: $countryCode", [
                    'raw_response' => $response,
                ]);
                return null;
            }
            foreach ($profiles as $profile) {
                if (strtoupper($profile['countryCode'] ?? '') == strtoupper($countryCode)) {
                    return $profile['profileId'];
                }
            }
            Log::warning("⚠️ No profile found for country: $countryCode", [
                'available_profiles' => $profiles,
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error("❌ Exception while fetching profile for $countryCode", [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    public function listCampaigns(array $filter = [], $profileId = null): array
    {
        // Use fallback if no profileId provided
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/vnd.spcampaign.v3+json';
        $this->client->headerAccept      = 'application/vnd.spcampaign.v3+json';
        return $this->client->listSponsoredProductsCampaigns($filter);
    }

    public function createCampaigns(array $filter = [], $profileId = null): array
    {
        // Use fallback if no profileId provided
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/vnd.spCampaign.v3+json';
        $this->client->headerAccept      = 'application/vnd.spCampaign.v3+json';
        return $this->client->createSponsoredProductsCampaigns($filter);
    }


    public function updateCampaigns(array $filter = [], $profileId = null): array
    {
        // Use fallback if no profileId provided
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/vnd.spCampaign.v3+json';
        $this->client->headerAccept      = 'application/vnd.spCampaign.v3+json';
        return $this->client->updateSponsoredProductsCampaigns($filter);
    }

    public function listSBCampaigns(array $filter = [], $profileId = null): array
    {
        // Use fallback if no profileId provided
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/vnd.sbcampaignresource.v4+json';
        $this->client->headerAccept      = 'application/vnd.sbcampaignresource.v4+json';
        return $this->client->listSponsoredBrandsCampaigns($filter);
    }

    public function updateSBCampaigns(array $filter = [], $profileId = null): array
    {
        // Use fallback if no profileId provided
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/vnd.sbcampaignresource.v4+json';
        $this->client->headerAccept      = 'application/vnd.sbcampaignresource.v4+json';
        return $this->client->updateSponsoredBrandsCampaigns($filter);
    }

    public function listAdGroups(array $params = [], $profileId = null): array
    {
        // Use fallback if no profileId provided
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/vnd.spadGroup.v3+json';
        $this->client->headerAccept      = 'application/vnd.spadGroup.v3+json';

        return $this->client->listSponsoredProductsAdGroups($params);
    }

    public function listKeywords(array $filter = [], $profileId = null): array
    {
        // Use fallback if no profileId provided
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/vnd.spkeyword.v3+json';
        $this->client->headerAccept      = 'application/vnd.spkeyword.v3+json';

        return $this->client->listSponsoredProductsKeywords($filter);
    }

    public function createKeywords(array $filter = [], $profileId = null): array
    {
        // Use fallback if no profileId provided
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/vnd.spKeyword.v3+json';
        $this->client->headerAccept      = 'application/vnd.spkeyword.v3+json';

        return $this->client->createSponsoredProductsKeywords($filter);
    }

    public function updateKeywords(array $filter = [], $profileId = null): array
    {
        // Use fallback if no profileId provided
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/vnd.spKeyword.v3+json';
        $this->client->headerAccept      = 'application/vnd.spkeyword.v3+json';

        return $this->client->updateSponsoredProductsKeywords($filter);
    }

    public function deleteKeywords(array $filter = [], $profileId = null): array
    {
        // Use fallback if no profileId provided
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/vnd.spKeyword.v3+json';
        $this->client->headerAccept      = 'application/vnd.spkeyword.v3+json';

        return $this->client->deleteSponsoredProductsKeywords($filter);
    }


    public function updateSBKeywords(array $keywords = [], ?string $profileId = null): array
    {

        $this->customClient->profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->customClient->headerAccept = "application/vnd.amazon.advertising.v3+json";

        return $this->customClient->customOperation('sb/keywords', $keywords, 'PUT', true);
    }


    public function listSBKeywords(array $filter = [], ?string $profileId = null): array
    {
        $this->client->profileId         = $profileId ?? config('amazon_ads.profiles.US');
        $this->client->headerContentType = 'application/vnd.sbkeywordresource.v3.2+json';

        return $this->client->listSponsoredBrandKeywords($filter);
    }

    public function listProductAds($filter = null, $profileId = null)
    {
        // Use fallback if no profileId provided
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/vnd.spProductAd.v3+json';
        $this->client->headerAccept      = 'application/vnd.spProductAd.v3+json';

        return $this->client->listSponsoredProductsProductAds($filter);
    }

    public function listProductAdsSb($filter = null, $profileId = null)
    {
        // Use fallback if no profileId provided
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/vnd.sbadresource.v4+json';
        $this->client->headerAccept      = 'application/vnd.sbadresource.v4+json';

        return $this->client->listSponsoredBrandsAds($filter);
    }

    public function requestReport($filter = null, $profileId = null)
    {
        // Use fallback if no profileId provided
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/vnd.createasyncreportrequest.v3+json';
        $this->client->headerAccept      = 'application/vnd.createasyncreportrequest.v3+json';

        return $this->client->requestOfflineReport($filter);
    }

    public function getReport($reportId = null, $profileId = null)
    {
        // Use fallback if no profileId provided
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/vnd.getasyncreportresponse.v3+json';
        $this->client->headerAccept      = 'application/vnd.getasyncreportresponse.v3+json';

        return $this->client->getOfflineReport($reportId);
    }

    public function deleteReport($reportId = null)
    {
        $this->client->profileId         = config('amazon_ads.profiles.US');
        $this->client->headerContentType = 'application/vnd.deleteasyncreportresponse.v3+json';
        $this->client->headerAccept      = 'application/vnd.deleteasyncreportresponse.v3+json';

        return $this->client->deleteOfflineReport($reportId);
    }

    public function getRankedKeywordRecommendation($reportId = null)
    {
        $this->client->profileId         = config('amazon_ads.profiles.US');
        $this->client->headerContentType = 'application/vnd.spkeywordsrecommendation.v4+json';
        $this->client->headerAccept      = 'application/vnd.spkeywordsrecommendation.v4+json';

        return $this->client->getRankedKeywordRecommendation($reportId);
    }

    public function listSponsoredProductsTargetingClauses(array $payload, string $profileId): array
    {
        $this->client->profileId         = $profileId ?? config('amazon_ads.profiles.US');
        $this->client->headerContentType = 'application/vnd.spTargetingClause.v3+json';
        $this->client->headerAccept      = 'application/vnd.spTargetingClause.v3+json';

        return $this->client->listSponsoredProductsTargetingClauses($payload);
    }

    public function getSponsoredBrandBidRecommendations(array $payload, string $profileId): array
    {
        $this->customClient->profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->customClient->headerAccept = 'application/vnd.sbbidsrecommendation.v3+json';

        return $this->customClient->customOperation('sb/recommendations/bids', $payload, 'POST', true);
    }

    public function listSDCampaigns(array $filter = [], $profileId = null): array
    {
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/json';
        $this->client->headerAccept      = 'application/json';

        return $this->client->listSponsoredDisplayCampaigns($filter);
    }

    public function updateSDCampaigns(array $filter = [], $profileId = null): array
    {
        // Use fallback if no profileId provided
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/json';
        $this->client->headerAccept      = 'application/json';
        return $this->client->updateSponsoredDisplayCampaigns($filter);
    }



    public function listSDAdGroups(array $filter = [], $profileId = null): array
    {
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/json';
        $this->client->headerAccept      = 'application/json';

        return $this->client->listSponsoredDisplayAdGroups($filter);
    }

    public function listProductAdsSd($filter = null, $profileId = null)
    {
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/json';
        $this->client->headerAccept      = 'application/json';

        return $this->client->listSponsoredDisplayProductAds($filter);
    }

    public function listTargetsSd($filter = null, $profileId = null)
    {
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/json';
        $this->client->headerAccept      = 'application/json';

        return $this->client->listSponsoredDisplayTargets($filter);
    }

    public function listSponsoredBrandsTargetingClauses(array $payload, string $profileId): array
    {
        $this->customClient->profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->customClient->headerAccept = 'application/vnd.sblisttargetsrequest.v3+json';

        return $this->customClient->customOperation('sb/targets/list', $payload, 'POST', true);
    }

    public function downloadReport($location = null, bool $gunzip = false, $profileId = null)
    {
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        return $this->client->download($location, $gunzip);
    }

    public function getThemeBasedBidRecommendationForAdGroup($filter = null, $profileId = null)
    {
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/vnd.spthemebasedbidrecommendation.v4+json';
        $this->client->headerAccept      = 'application/vnd.spthemebasedbidrecommendation.v4+json';

        return $this->client->getThemeBasedBidRecommendationForAdGroupV1($filter);
    }

    public function createSPAdGroups(array $filter = [], $profileId = null): array
    {
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/vnd.spAdGroup.v3+json';
        $this->client->headerAccept      = 'application/vnd.spAdGroup.v3+json';
        return $this->client->createSponsoredProductsAdGroups($filter);
    }
    public function updateSPAdGroups(array $filter = [], $profileId = null): array
    {
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/vnd.spAdGroup.v3+json';
        $this->client->headerAccept      = 'application/vnd.spAdGroup.v3+json';
        return $this->client->updateSponsoredProductsAdGroups($filter);
    }

    public function createSPProductAds(array $filter = [], $profileId = null): array
    {
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/vnd.spProductAd.v3+json';
        $this->client->headerAccept      = 'application/vnd.spProductAd.v3+json';
        return $this->client->createSponsoredProductsProductAds($filter);
    }

    public function createTargets(array $filter = [], $profileId = null): array
    {
        // Use fallback if no profileId provided
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/vnd.spTargetingClause.v3+json';
        $this->client->headerAccept      = 'application/vnd.spTargetingClause.v3+json';

        return $this->client->createSponsoredProductsTargetingClauses($filter);
    }

    public function spCampaignsBudgetUsage(array $filter = [], $profileId = null): array
    {
        // Use fallback if no profileId provided
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/vnd.spcampaignbudgetusage.v1+json';
        $this->client->headerAccept      = 'application/vnd.spcampaignbudgetusage.v1+json';

        return $this->client->spCampaignsBudgetUsage($filter);
    }
    public function getBudgetRecommendations(array $filter = [], $profileId = null): array
    {
        // Use fallback if no profileId provided
        $profileId = $profileId ?? config('amazon_ads.profiles.US');

        $this->client->profileId         = $profileId;
        $this->client->headerContentType = 'application/vnd.budgetrecommendation.v3+json';
        $this->client->headerAccept      = 'application/vnd.budgetrecommendation.v3+json';

        return $this->client->getBudgetRecommendations($filter);
    }

    public function getSPBudgetRulesForAdvertiser(?string $profileId = null, array $payload = []): array
    {
        $this->customClient->profileId = $profileId ?? config('amazon_ads.profiles.US');
        $this->customClient->headerAccept = 'application/json';

        // Ensure pageSize is always present (Amazon rejects null/missing)
        $payload['pageSize'] = (int) ($payload['pageSize'] ?? 30);

        return $this->customClient->customOperation('sp/budgetRules', $payload, 'GET', true);
    }

    public function listDspStreamSubscriptions(array $payload, string $dspAccountId): array
    {
        try {
            $accessToken = $this->getDspAccessToken(); // DSP token
            Log::info('DSP Access Token', ['token' => $accessToken]);
            $headers = [
                'Amazon-Ads-Account-ID: ' . $dspAccountId,
                'Amazon-Advertising-API-ClientId: ' . config('amazon_ads.dsp_client_id'),
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
                'Accept: application/vnd.amazonmarketingstreamsubscriptions.v1+json',
            ];

            $url = 'https://advertising-api.amazon.com/dsp/streams/subscriptions';
            if (!empty($payload)) {
                $url .= '?' . http_build_query($payload, '', '&', PHP_QUERY_RFC3986);
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);

            if ($info['http_code'] === 401) {
                throw new \Exception("Unauthorized: Check DSP client ID, account ID, and token.");
            }

            return [
                'success'  => true,
                'response' => $response,
                'data'     => json_decode($response, true),
            ];
        } catch (\Throwable $e) {
            Log::error('Amazon DSP Stream API Error', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success'  => false,
                'response' => null,
                'data'     => null,
            ];
        }
    }

    private function getDspAccessToken(): string
    {
        $url = 'https://api.amazon.com/auth/o2/token';
        $params = [
            'grant_type'    => 'refresh_token',
            'refresh_token' => config('amazon_ads.refresh_token'),
            'client_id'     => config('amazon_ads.client_id'),
            'client_secret' => config('amazon_ads.client_secret', ''),
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
        ]);

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if ($info['http_code'] !== 200) {
            throw new \Exception("Failed to get DSP token: {$response}");
        }

        $data = json_decode($response, true);
        Log::info('DSP Token Response', [
            'http_code' => $info['http_code'],
            'response'  => $response,
        ]);

        if (empty($data['access_token'])) {
            throw new \Exception("DSP token missing in response: {$response}");
        }

        return $data['access_token'];
    }
}
