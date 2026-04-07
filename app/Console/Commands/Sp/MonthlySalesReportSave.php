<?php

namespace App\Console\Commands\Sp;

use App\Jobs\MonthlySalesReportSave as JobsMonthlySalesReportSave;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonthlySalesReportSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:monthly-sales-report-save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SP: Save Monthly Order Sales Data to Database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        JobsMonthlySalesReportSave::dispatch();
        $this->info('Monthly sales report job has been dispatched.');
        Log::channel('spApi')->info('✅ MonthlySalesReportSave dispatched.');
    }
}
