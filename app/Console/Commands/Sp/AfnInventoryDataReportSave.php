<?php

namespace App\Console\Commands\Sp;

use App\Jobs\AfnInventoryDataReportSave as JobsAfnInventoryDataReportSave;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AfnInventoryDataReportSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:afn-inventory-data-report-save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save AFN Inventory Report';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        JobsAfnInventoryDataReportSave::dispatchSync();
        Log::channel('spApi')->info('✅ AfnInventoryDataReportSave Dispatched LR');

    }
}
