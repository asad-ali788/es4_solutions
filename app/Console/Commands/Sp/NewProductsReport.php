<?php

namespace App\Console\Commands\Sp;

use Illuminate\Console\Command;
use SellingPartnerApi\Seller\SellerConnector;
use Illuminate\Support\Facades\Log;
use App\Models\AmzReportsLog;
use App\Services\NewProductsService;
use SellingPartnerApi\Seller\ReportsV20210630\Dto\CreateReportSpecification;

class NewProductsReport extends Command
{
    protected $signature        = 'app:new-products-report';
    protected $description      = 'Fetch and process GET_MERCHANT_LISTINGS_ALL_DATA reports for all marketplaces';
    private   const REPORT_TYPE = 'GET_MERCHANT_LISTINGS_ALL_DATA';

    private const MAX_ATTEMPTS  = 30;
    private const SLEEP_SECONDS = 30;

    public function handle(SellerConnector $connector, NewProductsService $reportService): int
    {
        Log::channel('spApi')->info('✅ New Product Started.');
        $this->info("Starting SP-API New Product report process for all marketplaces...");

        $api = $connector->reportsV20210630();

        foreach (config('marketplaces.marketplace_ids') as $country => $marketplaceId) {
            $this->processMarketplace($api, $connector, $reportService, $country, $marketplaceId);
        }

        $this->info("SP-API merchant listings report process completed for all marketplaces.");
        Log::channel('spApi')->info('✅ New Product Completed.');

        return self::SUCCESS;
    }

    private function processMarketplace(
        $api,
        SellerConnector $connector,
        NewProductsService $reportService,
        string $country,
        string $marketplaceId
    ): void {
        $this->info("Processing marketplace: {$country} ({$marketplaceId})");

        try {
            $today = now()->toDateString();

            /**
             * 0. Check if we already created a report for this marketplace today
             */
            $logQuery = AmzReportsLog::query()
                ->where('report_type', self::REPORT_TYPE)
                ->whereDate('start_date', $today);

            // If marketplace_ids is JSON cast on model, use whereJsonContains
            $logQuery->whereJsonContains('marketplace_ids', $marketplaceId);

            /** @var AmzReportsLog|null $log */
            $log = $logQuery->latest('id')->first();

            if ($log) {
                $reportId = $log->report_id;
                $this->info("[{$country}] Found existing report for today. Reusing report ID: {$reportId}");
            } else {
                /**
                 * 1. Create a new report (no existing log for today)
                 */
                $reportSpec = new CreateReportSpecification(
                    reportType: self::REPORT_TYPE,
                    marketplaceIds: [$marketplaceId],
                );

                $response = $api->createReport($reportSpec)->json();
                $reportId = $response['reportId'] ?? null;

                if (!$reportId) {
                    $this->error("[{$country}] Failed to get reportId.");
                    return;
                }

                $log = AmzReportsLog::create([
                    'report_type'     => self::REPORT_TYPE,
                    'report_id'       => $reportId,
                    'report_status'   => $response['processingStatus'] ?? 'IN_PROGRESS',
                    'start_date'      => now(),          // <-- important: set today here
                    'end_date'        => null,
                    'marketplace_ids' => [$marketplaceId],
                ]);

                $this->info("[{$country}] Merchant Listings All Data report requested successfully. Report ID: {$reportId}");
            }

            /**
             * 2. Wait for the report to complete (using existing or new reportId)
             */
            $status = $this->waitForReportCompletion($api, $country, $reportId);

            if ($status === null) {
                $this->error("[{$country}] Timed out waiting for report to finish.");
                // Optionally update log
                if ($log) {
                    $log->update(['report_status' => 'TIMED_OUT']);
                }
                return;
            }

            $processingStatus = $status['processingStatus'] ?? 'UNKNOWN';

            // Update log with final status + end_date
            if ($log) {
                $log->update([
                    'report_status' => $processingStatus,
                    'end_date'      => now(),
                ]);
            }

            if ($processingStatus !== 'DONE') {
                $this->error("[{$country}] Report ended with status: {$processingStatus}");
                return;
            }

            $this->info("[{$country}] Report is ready. Proceeding to download document...");

            /**
             * 3. Download + process
             */
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
        } catch (\Throwable $e) {
            Log::channel('spApi')->error("[{$country}] Error fetching merchant listings report: " . $e->getMessage(), [
                'exception' => $e,
            ]);
            $this->error("[{$country}] Error: " . $e->getMessage());
        }
    }

    private function waitForReportCompletion($api, string $country, string $reportId): ?array
    {
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            sleep(self::SLEEP_SECONDS);

            $status           = $api->getReport($reportId)->json();
            $processingStatus = $status['processingStatus'] ?? 'UNKNOWN';

            $this->info("[{$country}] Attempt #{$attempt}. Status: {$processingStatus}");

            if ($processingStatus === 'DONE') {
                return $status;
            }

            if (in_array($processingStatus, ['CANCELLED', 'FATAL'], true)) {
                return $status;
            }
        }

        return null; // timed out
    }
}
