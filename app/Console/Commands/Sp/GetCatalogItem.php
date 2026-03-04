<?php

namespace App\Console\Commands\Sp;

use App\Jobs\GetCatalogItem as JobsGetCatalogItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GetCatalogItem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-catalog-item';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Catalog Item by Asin';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        JobsGetCatalogItem::dispatchSync();
        $this->info('Get Catalog Item job has been dispatched.');
        Log::channel('spApi')->info('✅ GetCatalogItem dispatched.');
    }
}
