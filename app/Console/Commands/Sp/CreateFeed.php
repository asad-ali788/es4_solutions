<?php

namespace App\Console\Commands\Sp;

use App\Jobs\CreateFeedJobs;
use Illuminate\Console\Command;

class CreateFeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-feed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Price Update Queue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $marketplaceIds = ['ATVPDKIKX0DER'];
        $message        = app(CreateFeedJobs::class)->handle($marketplaceIds);
        $this->info($message);
        logger()->info($message);
    }
}
