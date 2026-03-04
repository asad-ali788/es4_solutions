<?php

namespace App\Console\Commands\Wh;

use App\Jobs\SyncTacticalWarehouseInventory;
use Illuminate\Console\Command;

class TacticalWHInventory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:tactical-inventory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tactical Warehouse Sync';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        SyncTacticalWarehouseInventory::dispatch()->onQueue('long-running');
        $this->info('✅ Tactical warehouse inventory sync dispatched.');
    }
}
