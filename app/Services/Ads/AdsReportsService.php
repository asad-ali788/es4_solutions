<?php

namespace App\Services\Ads;

use App\Models\AmzAdsReportLog;
use App\Services\Api\AmazonAdsService;
use Illuminate\Support\Facades\Log;

class AdsReportsService
{
    public function requestReport(
        AmazonAdsService $client,
        string $profileId,
        string $startDate,
        string $endDate,
        string $country,
        string $reportType,
        string $reportLogType,
        array $groupBy,
        array $columns,
        string $adProduct,
        string $timeUnit = "DAILY"  
    ): void {
        $data = [
            "name" => "",
            "startDate" => $startDate,
            "endDate"   => $endDate,
            "configuration" => [
                "adProduct"    => $adProduct,
                "groupBy"      => $groupBy,
                "columns"      => $columns,
                "reportTypeId" => $reportType,
                "timeUnit"     => $timeUnit,   // 👈 now dynamic
                "format"       => "GZIP_JSON",
            ]
        ];

        $response = $client->requestReport($data, $profileId);

        if ($response['code'] === 200) {
            $responseData = json_decode($response['response'], true);

            AmzAdsReportLog::create([
                'country'       => $country,
                'report_type'   => $reportLogType,
                'report_id'     => $responseData['reportId'] ?? null,
                'report_status' => 'IN_PROGRESS',
                'r_iteration'   => 0,
                'report_date'   => $responseData['startDate'] ?? null,
                'added'         => now(),
            ]);

            Log::channel('ads')->info("✅ [$country][$reportLogType] Ads report requested successfully. Report ID: " . ($responseData['reportId'] ?? 'N/A') . " | Profile: $profileId | Date Range: $startDate → $endDate");
        } elseif ($response['code'] === 425) {
            Log::channel('ads')->warning("⚠️ [$country] Duplicate report detected.");
        } else {
            Log::channel('ads')->error("❌ [$country] Report request failed: Code {$response['code']}", [
                'profile_id'    => $profileId,
                'request_body'  => $data,
                'raw_response'  => $response['response'] ?? null,
            ]);
        }

        sleep(3);
    }
}
