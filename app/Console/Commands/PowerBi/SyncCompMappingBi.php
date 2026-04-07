<?php

namespace App\Console\Commands\PowerBi;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;
use App\Models\CompMappingBi;

class SyncCompMappingBi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'powerbi:sync-comp-mapping-bi';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Comp Mapping from PowerBI to local table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $batchSize = 1000;
        $this->info('Starting Comp Mapping Sync...');

        $query = DB::connection('powerbi')->table('comp_mapping');

        try {
            $total = $query->count();
            $this->info("Fetched {$total} rows from PowerBI.");
        } catch (\Exception $e) {
            $this->error("Error connecting to PowerBI or fetching count: " . $e->getMessage());
            return 1;
        }

        $processed = 0;
        $now = now();

        $query->orderBy('asin')->chunk($batchSize, function ($rows) use (&$processed, $total, $now) {
            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'asin'       => $row->asin,
                    'comp_asin'  => $row->comp_asin,
                    'brand'      => $row->brand ?? null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ];
            }

            if (!empty($data)) {
                DB::table('comp_mapping')->upsert(
                    $data,
                    ['asin', 'comp_asin'],
                    ['brand', 'updated_at']
                );
            }

            $processed += count($rows);
            $this->info("Processed {$processed} / {$total}");
        });

        $this->info('Sync complete.');
        return 0;
    }
}
