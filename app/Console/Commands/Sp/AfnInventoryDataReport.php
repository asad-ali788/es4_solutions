<?php

namespace App\Console\Commands\Sp;

use Illuminate\Console\Command;
use App\Services\HourlyReportCreateService;
use Illuminate\Support\Facades\Log;

class AfnInventoryDataReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:afn-inventory-data-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate AFN Inventory Report';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Define the required report type and marketplace
        $reportType     = 'GET_AFN_INVENTORY_DATA';
        $marketplaceIds = ['ATVPDKIKX0DER'];
        // $marketplaceIds = config('marketplaces.marketplace_ids');

        // Resolve the report creation service from the container
        $service = app(HourlyReportCreateService::class);

        // Trigger report creation and capture response
        $response = $service->createReport($reportType, $marketplaceIds);

        // Log the result for debugging/auditing
        Log::channel('spApi')->info('✅ AfnInventoryDataReport Generated', $response);

        $this->info($response['message']);
    }
}
