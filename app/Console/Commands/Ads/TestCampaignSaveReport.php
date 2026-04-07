<?php

namespace App\Console\Commands\Ads;

use App\Jobs\Ads\TestCampaignGetReportSave;
use App\Services\Api\AmazonAdsService;
use Illuminate\Console\Command;

class TestCampaignSaveReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-campaign-save-report {--country=US}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ADS: [Test] Save Campaign Report for SP Campaigns';

    /**
     * Execute the console command.
     */
    public function handle(AmazonAdsService $client)
    {
        $country = strtoupper($this->option('country'));
        
        $this->info("Fetching and saving test campaign report for {$country}...");
        
        $job = new TestCampaignGetReportSave($country);
        $job->handle($client);

        $this->info("✅ Test Campaign save job executed for {$country}. Check logs for details if it was pending or completed.");
    }
}
