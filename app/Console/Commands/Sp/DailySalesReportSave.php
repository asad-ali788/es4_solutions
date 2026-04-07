<?php

namespace App\Console\Commands\Sp;

use Illuminate\Console\Command;
use App\Jobs\DailySalesReportSave as JobsDailySalesReportSave;
use Illuminate\Support\Facades\Log;

class DailySalesReportSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:daily-sales-report-save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SP: Process and Save Daily Order Sales Data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        JobsDailySalesReportSave::dispatchSync();
        $this->info('Daily sales report job has been dispatched.');
        Log::channel('spApi')->info('✅ DailySalesReportSave Dispatched');
    }
}
