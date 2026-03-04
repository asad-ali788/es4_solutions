<?php

namespace App\Services;

use App\Models\AmzReportsLog;
use Illuminate\Support\Facades\Log;
use SellingPartnerApi\Seller\ReportsV20210630\Dto\CreateReportSpecification;
use SellingPartnerApi\Seller\SellerConnector;
use Carbon\Carbon;

class WeeklyReportCreateService
{
    /**
     * Inject the Amazon SellerConnector to access SP API.
     */
    public function __construct(
        protected SellerConnector $connector
    ) {}

    /**
     * Creates a Weekly report request via Amazon SP API and logs it to the database.
     *
     * @param string $reportType
     * @param array $marketplaceIds
     * @return array
     */

    public function createReport(string $reportType, array $marketplaceIds): array
    {
        // Validate report type input
        if (empty($reportType)) {
            return [
                'success' => false,
                'message' => '⚠️ Report type is required.',
            ];
        }

        try {
            // Initialize the SP API reports client
            $api = $this->connector->reportsV20210630();

            // Set weekly range (previous week: Monday to Sunday in UTC)
            $marketTz = config('timezone.market');

            $startDate = Carbon::now($marketTz)
                ->subWeek()
                ->startOfWeek(Carbon::MONDAY)
                ->timezone('UTC');

            $endDate = Carbon::now($marketTz)
                ->subWeek()
                ->endOfWeek(Carbon::SUNDAY)
                ->timezone('UTC');

            $exists = AmzReportsLog::where('report_type', $reportType)
                ->whereDate('start_date', $startDate->toDateString())
                ->whereDate('end_date', $endDate->toDateString())
                ->where('marketplace_ids', json_encode($marketplaceIds))
                ->exists();

            if ($exists) {
                return [
                    'success' => false,
                    'message' => '⚠️Weekly Report already created for the given date and marketplace.',
                ];
            }

            // Build report request payload
            $reportSpec = new CreateReportSpecification(
                reportType: $reportType,
                marketplaceIds: $marketplaceIds,
                dataStartTime: $startDate,
                dataEndTime: $endDate
            );

            // Send the report creation request
            $response = $api->createReport($reportSpec)->json();

            // Log the report request to the local database
            AmzReportsLog::create([
                'report_type'     => $reportType,
                'report_id'       => $response['reportId'] ?? null,
                'report_status'   => $response['processingStatus'] ?? 'IN_PROGRESS',
                'start_date'      => $startDate,
                'end_date'        => $endDate,
                'marketplace_ids' => $marketplaceIds,
            ]);

            // Log the report request to the local database
            return [
                'success'   => true,
                'report_id' => $response['reportId'] ?? null,
                'status'    => $response['processingStatus'] ?? 'IN_PROGRESS',
                'message'   => '✅Weekly Report created successfully.',
            ];
        } catch (\Throwable $e) {
            // Log unexpected exception to error logs
            Log::error('Amazon SP API Weekly Report Create Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => '❌ Exception while creating Weekly report.',
                'error'   => $e->getMessage(),
            ];
        }
    }
}
