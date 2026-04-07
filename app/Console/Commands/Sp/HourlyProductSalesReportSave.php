<?php

namespace App\Console\Commands\Sp;

use App\Jobs\HourlyProductSalesReportSave as JobsHourlyProductSalesReportSave;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class HourlyProductSalesReportSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:hourly-product-sales-report-save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SP: Save Hourly Order Sales Data to Database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        JobsHourlyProductSalesReportSave::dispatchSync();
        $this->info('Hourly Product sales report job has been dispatched.');
        Log::channel('spApi')->info('✅ HourlyProductSalesReportSave dispatched.');
    }
}
