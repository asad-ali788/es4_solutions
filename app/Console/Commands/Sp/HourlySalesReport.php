<?php

namespace App\Console\Commands\Sp;

use Illuminate\Console\Command;
use App\Models\AmzReportsLog;
use Illuminate\Support\Facades\Log;
use SellingPartnerApi\Seller\ReportsV20210630\Dto\CreateReportSpecification;
use SellingPartnerApi\Seller\SellerConnector;
use Carbon\Carbon;
use App\Jobs\RetryCommandAfterDelay;
use Saloon\Exceptions\Request\Statuses\ForbiddenException;

class HourlySalesReport extends Command
{
    public function __construct(
        protected SellerConnector $connector
    ) {
        parent::__construct();
    }

    protected $signature = 'app:hourly-sales-report';
    protected $description = 'SP: Request Hourly Summary Sales Report (Legacy)';

    public function handle(): int
    {
        Log::channel('spApi')->info('✅ HourlySalesReport Started.');

        $reportType     = 'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL';
        $marketplaceIds = ['ATVPDKIKX0DER', 'A2EUQ1WTGCTBG2', 'A1AM78C64UM0Y8'];

        $api = $this->connector->reportsV20210630();

        // Convert Los Angeles time to UTC for SP-API
        $startDate = Carbon::now('America/Los_Angeles')->startOfDay()->timezone('UTC');
        $endDate   = Carbon::now('America/Los_Angeles')->timezone('UTC');

        $reportSpec = new CreateReportSpecification(
            reportType: $reportType,
            marketplaceIds: $marketplaceIds,
            dataStartTime: $startDate,
            dataEndTime: $endDate
        );

        try {
            $response = $api->createReport($reportSpec)->json();

            AmzReportsLog::create([
                'report_type'      => $reportType,
                'report_frequency' => 'hourly',
                'report_id'        => $response['reportId'] ?? null,
                'report_status'    => $response['processingStatus'] ?? 'IN_PROGRESS',
                'start_date'       => $startDate,
                'end_date'         => $endDate,
                'marketplace_ids'  => $marketplaceIds,
            ]);

            Log::channel('spApi')->info('✅ Hourly Report created successfully', $response);
            $this->info('✅ Hourly Report - created: ' . ($response['reportId'] ?? 'N/A'));

            return 0;
        } catch (ForbiddenException $e) {
            // SP-API token is likely expired
            Log::channel('spApi')->warning('❌ 403 Forbidden: Hourly Sales SP-API access token expired or unauthorized.'. $e->getMessage());

            // Dispatch a retry in 5 minutes
            RetryCommandAfterDelay::dispatch($this->signature, 2);

            return 0; // Failure exit code
        } catch (\Throwable $e) {
            Log::channel('spApi')->error('❌ Hourly Sales -  Unexpected error in HourlySalesReport: ' . $e->getMessage());
            return 0;
        }
        Log::channel('spApi')->info('✅ HourlySalesReport Completed.');
    }
}
