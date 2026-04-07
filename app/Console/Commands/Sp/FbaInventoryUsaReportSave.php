<?php

namespace App\Console\Commands\Sp;

use Illuminate\Console\Command;
use App\Jobs\FbaInventoryUsaReportSave as JobFbaInventoryUsaReportSave;
use Illuminate\Support\Facades\Log;

class FbaInventoryUsaReportSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fba-inventory-usa-report-save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SP: Save FBA USA Inventory Data to Database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('memory_limit', '2048M');
        JobFbaInventoryUsaReportSave::dispatch();
        Log::channel('spApi')->info('✅ FbaInventoryUsaReportSave Dispatched');
    }
}
