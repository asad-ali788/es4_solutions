<?php

namespace App\Console\Commands\Sp;

use Illuminate\Console\Command;
use App\Services\MonthlyReportCreateService;
use Illuminate\Support\Facades\Log;

class MonthlySalesReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:monthly-sales-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SP: Request Monthly Order Sales Report (All Marketplaces)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Define the required report type and marketplace
        $reportType     = 'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL';
        $marketplaceIds = ['ATVPDKIKX0DER', 'A2EUQ1WTGCTBG2', 'A1AM78C64UM0Y8'];

        // Resolve the report creation service from the container
        $service = app(MonthlyReportCreateService::class);

        // Trigger report creation and capture response
        $response = $service->createReport($reportType, $marketplaceIds);

        // Log the result for debugging/auditing
        Log::channel('spApi')->info('✅ MonthlySalesReport Generated.', $response);

        $this->info($response['message']);
    }
}
