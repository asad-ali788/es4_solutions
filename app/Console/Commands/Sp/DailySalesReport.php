<?php

namespace App\Console\Commands\Sp;

use Illuminate\Console\Command;
use App\Services\DailyReportCreateService;
use Illuminate\Support\Facades\Log;

class DailySalesReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:daily-sales-report';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SP: Request Daily Order Sales Report (All Marketplaces)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Define the required report type and marketplace
        $reportType     = 'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL';
        $marketplaceIds = ['ATVPDKIKX0DER', 'A2EUQ1WTGCTBG2', 'A1AM78C64UM0Y8'];

        // Resolve the report creation service from the container
        $service = app(DailyReportCreateService::class);

        // Trigger report creation and capture response
        $response = $service->createReport($reportType, $marketplaceIds);

        // Log the result for debugging/auditing
        Log::channel('spApi')->info('✅ DailySalesReport Generated', $response);

        $this->info($response['message']);
    }
}
