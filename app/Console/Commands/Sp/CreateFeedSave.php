<?php

namespace App\Console\Commands\Sp;

use App\Models\FeedLog;
use App\Models\PriceUpdateQueue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use SellingPartnerApi\Seller\SellerConnector;
use Illuminate\Support\Facades\Log;

class CreateFeedSave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-feed-save';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function __construct(
        protected SellerConnector $connector
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $feedsApi = $this->connector->feedsV20210630();
        $logs     = PriceUpdateQueue::where('status', 'pending')
            ->whereNotNull('feed_id')
            ->get();
        if ($logs->isEmpty()) {
            $this->info('No processing Price feed logs found 🚫');
            return;
        }

        $requestCounter = 0;
        foreach ($logs as $log) {
            try {
                $this->info("Checking feed: {$log->feed_id}");

                // Step 1: Get feed status
                $feedStatusResponse = $feedsApi->getFeed($log->feed_id);
                $feedStatus         = $feedStatusResponse->json();

                $reportDocId = $feedStatus['resultFeedDocumentId'] ?? null;

                if (!$reportDocId) {
                    $this->warn("No report available yet for feed: {$log->feed_id}");
                    continue;
                }

                // Step 2: Get report document
                $docResponse = $feedsApi->getFeedDocument($reportDocId);
                $docData     = $docResponse->json();
                $downloadUrl = $docData['url'] ?? null;

                if (!$downloadUrl) {
                    $this->warn("Failed to retrieve URL for feed: {$log->feed_id}");
                    continue;
                }

                // Step 3: Download and decode report content
                $response      = Http::get($downloadUrl);
                $body          = $response->body();
                $reportContent = substr($body, 0, 2) === "\x1f\x8b" ? gzdecode($body) : $body;
                $report        = json_decode($reportContent, true);

                $errorCount = $report['summary']['errors'] ?? 0;
                $message    = $errorCount > 0 ? json_encode($report['issues'] ?? []) : null;
                // Step 4: Update log status
                $log->update([
                    'status'  => $errorCount > 0 ? 'failed' : 'success',
                    'message' => $message,
                ]);

                FeedLog::where('feed_id', $log->feed_id)->update([
                    'feed_result_ID' => $report ?? null,
                    'feed_submit'    => now(),
                    'status'         => $errorCount > 0 ? 'failed' : 'success',
                ]);
                $this->info("Feed ID {$log->feed_id} marked as " . ($errorCount > 0 ? '❌ failed' : '✅ success'));
                if($errorCount > 0){
                    logger()->error($report);
                }
            } catch (\Throwable $e) {
                Log::error("Error processing feed log ID {$log->id}", ['error' => $e->getMessage()]);
                $this->error("Error for feed {$log->feed_id}: {$e->getMessage()}");
            }

            // Sleep logic
            $requestCounter++;
            if ($requestCounter >= 10) {
                $this->warn("Feeds - Burst limit reached. Cooling down for 50s 💤");
                sleep(50);
                $requestCounter = 0;
            } else {
                usleep(250000); // 0.25s delay between calls
            }
        }

        $this->info('Feed report check completed 🎯');
    }
}
