<?php

namespace App\Console\Commands\Ads;

use Illuminate\Console\Command;
use App\Jobs\Ads\SyncProductsKeywords;
use Illuminate\Support\Facades\Log;

class ListProductsKeywords extends Command
{
    protected $signature = 'app:list-sp-keywords';
    protected $description = 'Sync SP Keywords';

    public function handle(): void
    {
        SyncProductsKeywords::dispatch('US')->onQueue('long-running');
        SyncProductsKeywords::dispatch('CA')->onQueue('long-running');
        $this->info('✅ Sponsored Products Keywords job dispatched for US and CA.');
        Log::channel('ads')->info('SyncProductsKeywords dispatched for US & CA.');
    }
}
