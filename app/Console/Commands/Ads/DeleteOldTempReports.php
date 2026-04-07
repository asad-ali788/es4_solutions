<?php

namespace App\Console\Commands\Ads;

use Illuminate\Console\Command;

use App\Models\TempAmzCampaignPerformanceReport;
use App\Models\TempAmzCampaignSBPerformanceReport;
use App\Models\TempAmzCampaignSDPerformanceReport;
use App\Models\TempAmzKeywordPerformanceReport;
use App\Models\TempAmzKeywordSBPerformanceReport;
use App\Models\TempAmzProductPerformanceReport;
use App\Models\TempAmzProductPerformanceReportSd;
use App\Models\TempAmzTargetsPerformanceReportSb;
use App\Models\TempAmzTargetsPerformanceReportSd;
use Illuminate\Support\Facades\Log;

class DeleteOldTempReports extends Command
{
    protected $signature = 'app:truncate-temp-reports';
    protected $description = 'ADS: Clean Up Old Temp Amazon Ads Report Logs';

    public function handle()
    {
        $tables = [
            TempAmzCampaignPerformanceReport::class,
            TempAmzCampaignSBPerformanceReport::class,
            TempAmzKeywordPerformanceReport::class,
            TempAmzKeywordSBPerformanceReport::class,
            TempAmzProductPerformanceReport::class,
            TempAmzProductPerformanceReportSd::class,
            TempAmzCampaignSDPerformanceReport::class,
            TempAmzTargetsPerformanceReportSd::class,
            TempAmzTargetsPerformanceReportSb::class
        ];

        foreach ($tables as $table) {
            $table::truncate();
            $this->info("🗑️ Truncated table: $table");
        }

        Log::channel('ads')->info("✅ All temp tables have been Deleted.");
    }
}
