<?php

namespace App\Console\Commands\Sp;

use Illuminate\Console\Command;
use App\Jobs\WeeklySalesReportSave as JobsWeeklySalesReportSave;
use Illuminate\Support\Facades\Log;

class WeeklySalesReportSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:weekly-sales-report-save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SP: Save Weekly Order Sales Data to Database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        JobsWeeklySalesReportSave::dispatch();
        $this->info('Weekly sales report job has been dispatched.');
        Log::channel('spApi')->info('✅ WeeklySalesReportSave dispatched.');
    }
}
