<?php

namespace App\Console\Commands\PowerBi;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ModifiedCategoryRankHistoryBi;

class SyncModifiedCategoryRankHistoryBi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'powerbi:sync-modified-category-rank-history-bi {mode? : "all" for last 7 days, omit for current day only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Modified Category Rank History from PowerBI to local table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mode = $this->argument('mode');
        $batchSize = 1000;

        $this->info('Starting Modified Category Rank History Sync...');

        $query = DB::connection('powerbi')->table('modified_category_rank_history');

        if ($mode === 'all') {
            $this->info('Syncing data for the last 7 days...');
            $startDate = Carbon::today()->subDays(7)->toDateString();
            $query->where('date', '>=', $startDate);
        } else {
            $this->info('Syncing data for today (default mode)...');
            $query->where('date', '>=', Carbon::today()->toDateString());
        }

        try {
            $total = $query->count();
            $this->info("Fetched {$total} rows from PowerBI.");
        } catch (\Exception $e) {
            $this->error("Error connecting to PowerBI or fetching count: " . $e->getMessage());
            return 1;
        }

        $processed = 0;
        $now = now();

        $query->orderBy('date')->chunk($batchSize, function ($rows) use (&$processed, $total, $now) {
            $data = [];
            foreach ($rows as $row) {
                // Ensure the date is a string if it's an object/Carbon
                $dateValue = ($row->date instanceof \DateTimeInterface) 
                    ? $row->date->format('Y-m-d') 
                    : $row->date;

                $data[] = [
                    'date'          => $dateValue,
                    'asin'          => $row->asin,
                    'category_type' => $row->category_type,
                    'category_name' => $row->category_name ?? null,
                    'updated_at'    => $now,
                    'created_at'    => $now,
                ];
            }

            if (!empty($data)) {
                DB::table('modified_category_rank_history_bis')->upsert(
                    $data,
                    ['date', 'asin', 'category_type'],
                    ['category_name', 'updated_at']
                );
            }

            $processed += count($rows);
            $this->info("Processed {$processed} / {$total}");
        });

        $this->info('Sync complete.');
        return 0;
    }
}
