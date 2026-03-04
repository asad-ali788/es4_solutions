<?php

namespace App\Console\Commands\Sp;

use App\Jobs\SyncCatalogCategories;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ListCatalogCategoryReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:list-catalog-category-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Catalog category by ASIN';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        SyncCatalogCategories::dispatch();
        $this->info(' Catalog category job has been dispatched.');
        Log::channel('spApi')->info('✅ SyncCatalogCategories dispatched.');
    }
}
