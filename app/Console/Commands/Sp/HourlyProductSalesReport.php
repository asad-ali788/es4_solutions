<?php

namespace App\Console\Commands\Sp;

use App\Models\AmzReportsLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use SellingPartnerApi\Seller\ReportsV20210630\Dto\CreateReportSpecification;
use SellingPartnerApi\Seller\SellerConnector;
use Saloon\Exceptions\Request\Statuses\ForbiddenException;
use Throwable;

class HourlyProductSalesReport extends Command
{
    protected $signature = 'app:hourly-product-sales-report';

    protected $description = 'SP: Request Hourly Order Sales Report (GET_FLAT_FILE_ALL_ORDERS)';

    public function __construct(
        protected SellerConnector $connector
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $reportType     = 'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL';
        $frequency      = 'product_hourly_sales';
        $marketTz       = config('timezone.market', 'America/Los_Angeles');
        $marketplaceIds = array_values(config('marketplaces.marketplace_ids'));

        /**
         * 1️ Determine latest safe LOCAL hour
         * If now = 10:34 → safe hour = 09:00–10:00
         */
        $latestSafeLocalStart = Carbon::now($marketTz)
            ->startOfHour()
            ->subHour();

        /**
         * 2️ Find last processed hour
         */
        $lastLog = AmzReportsLog::query()
            ->where('report_frequency', $frequency)
            ->whereIn('report_status', ['IN_PROGRESS', 'DONE'])
            ->orderByDesc('start_date')
            ->first();

        if ($lastLog) {
            $startLocal = Carbon::parse($lastLog->start_date, 'UTC')
                ->timezone($marketTz)
                ->addHour()
                ->startOfHour();
        } else {
            $startLocal = $latestSafeLocalStart->copy();
        }

        if ($startLocal->greaterThan($latestSafeLocalStart)) {
            $this->info('No completed hour available yet.');
            return self::SUCCESS;
        }

        $endLocal = $startLocal->copy()->addHour();

        /**
         * Convert to UTC (SP-API requirement)
         */
        $startUtc = $startLocal->copy()->timezone('UTC');
        $endUtc   = $endLocal->copy()->timezone('UTC');

        $placeholderReportId = 'PENDING_' . sha1(
            $reportType . '|' .
                $frequency . '|' .
                $startUtc->toIso8601String() . '|' .
                $endUtc->toIso8601String()
        );
        /**
         * 4️ Idempotent log (prevents timing mess)
         */
        $log = AmzReportsLog::firstOrCreate(
            [
                'report_type'      => $reportType,
                'report_frequency' => $frequency,
                'start_date'       => $startUtc,
                'end_date'         => $endUtc,
            ],
            [
                'report_id'        => $placeholderReportId,
                'report_status'   => 'IN_PROGRESS',
                'marketplace_ids' => $marketplaceIds,
            ]
        );

        // Already requested earlier
        if ($log->report_id !== $placeholderReportId) {
            $this->info('Hourly report already requested.');
            return self::SUCCESS;
        }

        /**
         * 5️⃣ Create SP-API report
         */
        try {
            $api = $this->connector->reportsV20210630();

            $spec = new CreateReportSpecification(
                reportType: $reportType,
                marketplaceIds: $marketplaceIds,
                dataStartTime: $startUtc,
                dataEndTime: $endUtc
            );

            $response = $api->createReport($spec)->json();

            if (empty($response['reportId'])) {
                throw new \RuntimeException('Missing reportId');
            }

            $log->update([
                'report_id' => $response['reportId'],
            ]);

            $this->info("Report created: {$response['reportId']}");
            return self::SUCCESS;
        } catch (ForbiddenException $e) {
            Log::warning('SP-API throttled', ['error' => $e->getMessage()]);
            return self::SUCCESS; // do not advance hour
        } catch (Throwable $e) {
            $log->update([
                'report_status' => 'FATAL',
            ]);

            Log::error('Hourly report failed', [
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }
}
