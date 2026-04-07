<?php

namespace App\Console\Commands\Sp;

use Illuminate\Console\Command;
use SellingPartnerApi\Seller\SellerConnector;
use Illuminate\Support\Facades\Log;
use App\Models\AmzReportsLog;
use App\Services\ReportService;
use Carbon\Carbon;

class GetMerchantListingsAllData extends Command
{
    protected $signature = 'app:get-merchant-listings';

    protected $description = 'SP: Fetch GET_MERCHANT_LISTINGS_ALL_DATA from SP-API';

    public function handle(SellerConnector $connector, ReportService $reportService)
    {
        Log::channel('spApi')->info('✅ GetMerchantListingsAllData Started.');

        $this->info("Starting SP-API merchant listings report process for all marketplaces...");

        $reportType = 'GET_MERCHANT_LISTINGS_ALL_DATA';

        foreach (config('marketplaces.marketplace_ids') as $country => $marketplaceId) {
            $this->info("Processing marketplace: {$country} ({$marketplaceId})");

            try {
                // Step 1. Create the report
                $api = $connector->reportsV20210630();

                $reportSpec = new \SellingPartnerApi\Seller\ReportsV20210630\Dto\CreateReportSpecification(
                    reportType: $reportType,
                    marketplaceIds: [$marketplaceId]
                );

                $response = $api->createReport($reportSpec)->json();

                $reportId = $response['reportId'] ?? null;

                if (!$reportId) {
                    $this->error("[{$country}] Failed to get reportId.");
                    continue;
                }

                // Save report log
                AmzReportsLog::create([
                    'report_type'     => $reportType,
                    'report_id'       => $reportId,
                    'report_status'   => $response['processingStatus'] ?? 'IN_PROGRESS',
                    'start_date'      => null,
                    'end_date'        => null,
                    'marketplace_ids' => [$marketplaceId],
                ]);

                $this->info("[{$country}] Get Merchant ListingsAll Data Report requested successfully. Report ID: {$reportId}");

                // Step 2. Poll until report is DONE
                $maxAttempts = 30;
                $sleepSeconds = 30;
                $attempts = 0;

                while ($attempts < $maxAttempts) {
                    sleep($sleepSeconds);
                    $status = $api->getReport($reportId)->json();
                    $processingStatus = $status['processingStatus'] ?? null;

                    $this->info("[{$country}] Attempt #{$attempts}. Status: {$processingStatus}");

                    if ($processingStatus === 'DONE') {
                        $this->info("[{$country}] Report is ready. Proceeding to download document...");

                        // Instead of controller call, use your service
                        $result = $reportService->downloadAndProcessReport(
                            $connector,
                            $reportId,
                            $country
                        );

                        if (!empty($result['error'])) {
                            $this->error("[{$country}] Error downloading report: " . $result['message']);
                        } else {
                            $this->info("[{$country}] Report processed successfully. File URL: " . $result['download_url']);
                        }
                        break;
                    }

                    if (in_array($processingStatus, ['CANCELLED', 'FATAL'])) {
                        $this->error("[{$country}] Report failed with status: {$processingStatus}");
                        break;
                    }

                    $attempts++;
                }

                if ($attempts === $maxAttempts) {
                    $this->error("[{$country}] Timed out waiting for report to finish.");
                }
            } catch (\Throwable $e) {
                Log::channel('spApi')->error("[{$country}] Error fetching merchant listings report: " . $e->getMessage());
                $this->error("[{$country}] Error: " . $e->getMessage());
            }
        }

        $this->info("SP-API merchant listings report process completed for all marketplaces.");
        Log::channel('spApi')->info('✅ GetMerchantListingsAllData Completed.');

        return 0;
    }
}
