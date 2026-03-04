<?php

namespace App\Console\Commands\Sp;

use App\Services\HourlyReportCreateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FbaInventoryUsaReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fba-inventory-usa-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate FBA Inventory Report	';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $reportType     = 'GET_FBA_MYI_ALL_INVENTORY_DATA';
        $marketplaceIds = ['ATVPDKIKX0DER'];
        $service        = app(HourlyReportCreateService::class);
        $response       = $service->createReport($reportType, $marketplaceIds);
        Log::channel('spApi')->info('✅ FbaInventoryUsaReport Generated', $response);
        $this->info($response['message']);
    }
}
