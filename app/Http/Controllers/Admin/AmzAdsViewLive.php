<?php

namespace App\Http\Controllers\Admin;

use App\Enum\Permissions\AmzAdsEnum;
use App\Enum\Permissions\DeveloperEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\Api\AmazonAdsService;

class AmzAdsViewLive extends Controller
{
    public function campaign(Request $request, AmazonAdsService $amazonAdsService)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsViewLivePerformance);

        $result = null;
        $error  = null;

        if ($request->filled(['country', 'campaign', 'campaign_id'])) {

            $validated = $request->validate([
                'country'     => 'required|in:US,CA',
                'campaign'    => 'required|in:SP,SB,SD', // ✅ SD added
                'campaign_id' => 'required|numeric',
            ]);

            $profileId = config("amazon_ads.profiles.{$validated['country']}");

            if (!$profileId) {
                return back()->with('error', "Missing profile ID for {$validated['country']}.");
            }

            try {

                $filter = [
                    'stateFilter' => ['include' => ['ENABLED', 'PAUSED']],
                    'includeExtendedDataFields' => true,
                    'campaignIdFilter' => ['include' => [$validated['campaign_id']]],
                    'maxResults' => 1,
                ];

                // ✅ SP, SB, SD handling
                if ($validated['campaign'] === 'SB') {
                    $resp = $amazonAdsService->listSBCampaigns($filter, $profileId);
                } elseif ($validated['campaign'] === 'SD') {
                    $filterSd = [
                        'count'            => 1,
                        'stateFilter'      => 'enabled,paused',
                        'campaignIdFilter' => (string) $validated['campaign_id'], // single id as string
                    ];

                    $resp = $amazonAdsService->listSDCampaigns($filterSd, $profileId);
                } else {
                    // SP
                    $resp = $amazonAdsService->listCampaigns($filter, $profileId);
                }
                $body = $resp['response']       ?? null;
                $info = $resp['responseInfo']   ?? [];
                $msg  = $resp['message']        ?? '';
                $code = (int)($info['http_code'] ?? 0);

                // Token failure / network issue
                if (
                    stripos($msg, 'Unable to refresh token') !== false ||
                    ($code === 0 && empty($body))
                ) {
                    return back()->with('error', 'Amazon API is busy right now. Try again after 10 seconds.');
                }

                if ($body) {
                    $result = json_decode($body, true);
                } else {
                    return back()->with('error', 'No campaign found or empty response.');
                }
            } catch (\Throwable $e) {
                Log::error('Campaign fetch failed', ['e' => $e->getMessage()]);
                return back()->with('error', 'Error fetching campaign — check logs.');
            }
        }

        return view('pages.admin.amzAds.viewLive.campaign', compact('result', 'error'));
    }


    public function keyword(Request $request, AmazonAdsService $amazonAdsService)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsViewLivePerformance);
        $result = null;
        $error  = null;

        if ($request->filled(['country', 'campaign'])) {
            $validated = $request->validate([
                'country'     => 'required|in:US,CA',
                'campaign'    => 'required|in:SP,SB',
                'keyword_id'  => 'nullable|numeric',
                'campaign_id' => 'nullable|numeric',
            ]);
            $profileId = config("amazon_ads.profiles.{$validated['country']}");

            if (!$profileId) {
                return back()->with('error', "Missing profile ID for {$validated['country']}.");
            }

            try {
                $filter = [
                    'stateFilter'               => ['include' => ['ENABLED', 'PAUSED', 'ARCHIVED']],
                    'includeExtendedDataFields' => true,
                ];
                if (!empty($validated['keyword_id'])) {
                    $filter['keywordIdFilter'] = ['include' => [(string) $validated['keyword_id']]];
                }

                if (!empty($validated['campaign_id'])) {
                    $filter['campaignIdFilter'] = ['include' => [(string) $validated['campaign_id']]];
                }

                // SB vs SP
                $resp = $validated['campaign'] === 'SB'
                    ? $amazonAdsService->listSBKeywords($filter, $profileId)
                    : $amazonAdsService->listKeywords($filter, $profileId);

                $body     = $resp['response']     ?? null;
                $info     = $resp['responseInfo'] ?? [];
                $httpCode = (int)($info['http_code'] ?? 0);
                $message  = $resp['message'] ?? '';

                // API busy / token refresh/network failure → toast + redirect back
                if (stripos($message, 'Unable to refresh token') !== false || ($httpCode === 0 && empty($body))) {
                    return back()->with('error', 'Amazon API is busy right now. Try again after 10 seconds.');
                }

                if ($body) {
                    $result = json_decode($body, true);
                } else {
                    return back()->with('error', 'No keyword found or empty response.');
                }
            } catch (\Throwable $e) {
                Log::error('Keyword fetch failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                return back()->with('error', 'Error fetching keyword Check the inputs');
            }
        }

        return view('pages.admin.amzAds.viewLive.keyword', compact('result', 'error'));
    }

    public function keywordForAdGroup(Request $request, AmazonAdsService $amazonAdsService)
    {
        $this->authorize(AmzAdsEnum::AmazonAdsViewLivePerformance);

        $result = null;
        $error  = null;

        if ($request->filled(['country', 'campaign'])) {

            // ONLY validate the required 4 fields
            $validated = $request->validate([
                'country'    => 'required|in:US,CA',
                'campaign'   => 'required|in:SP,SB',
                'adGroupId'  => 'required|numeric',
                'campaignId' => 'required|numeric',
            ]);

            // Resolve profile ID
            $profileId = config("amazon_ads.profiles.{$validated['country']}");
            if (!is_scalar($profileId) || $profileId === '') {
                return back()->with('error', "Missing profile ID for {$validated['country']}.");
            }
            $profileId = (string) $profileId;

            try {
                $payload = [
                    'adGroupId'          => (string) $validated['adGroupId'],
                    'campaignId'         => (string) $validated['campaignId'],
                    'recommendationType' => 'KEYWORDS_FOR_ADGROUP',
                    'maxRecommendations' => 200,
                    'sortDimension'      => 'DEFAULT',
                ];

                // Your service signature: payload first, then profileId
                $resp = $amazonAdsService->getRankedKeywordRecommendation($payload, $profileId);
                $body     = $resp['response']     ?? null;
                $info     = $resp['responseInfo'] ?? [];
                $httpCode = (int)($info['http_code'] ?? 0);
                $message  = $resp['message'] ?? '';

                // Similar fallback rules as `keyword()`
                if (stripos($message, 'Unable to refresh token') !== false || ($httpCode === 0 && empty($body))) {
                    return back()->with('error', 'Amazon API is busy right now. Try again after 10 seconds.');
                }

                if ($body) {
                    $result = json_decode($body, true);
                } else {
                    return back()->with('error', 'No recommendations found or empty response.');
                }
            } catch (\Throwable $e) {
                Log::error('Keyword recommendation fetch failed: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
                return back()->with('error', 'Error fetching recommendations — check inputs/logs.');
            }
        }

        return view('pages.admin.amzAds.viewLive.keywordAdGroup', compact('result', 'error'));
    }
}
