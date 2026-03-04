<?php

namespace App\Console\Commands\Sp;

use App\Jobs\HourlySalesReportSave as JobsHourlySalesReportSave;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class HourlySalesReportSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:hourly-sales-report-save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Save hourly sales report';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        JobsHourlySalesReportSave::dispatchSync();
        $this->info('Hourly sales report job has been dispatched.');
        Log::channel('spApi')->info('✅ HourlySalesReportSave dispatched.');
    }
}
