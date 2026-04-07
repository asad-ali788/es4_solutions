<?php

namespace App\Console\Commands\PowerBi;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncBrandAnalyticsWeeklyDataBi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'powerbi:sync-brand-analytics-weekly-data-bi {mode? : all=sync all records, omit for last 4 weeks only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Brand_Analytics_Weekly_Data from PowerBI DB to brand_analytics_weekly_data_bi table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $marketTz = config('timezone.market', 'UTC');
        $mode = $this->argument('mode');
        $batchSize = 1000;

        $this->info('Starting Brand Analytics Weekly Data Sync...');

        $query = DB::connection('powerbi')->table('Brand_Analytics_Weekly_Data');

        if ($mode !== 'all') {
            $now = Carbon::now($marketTz);
            $syncWeeks = [];
            for ($i = 0; $i < 4; $i++) {
                $date = $now->copy()->subWeeks($i);
                $syncWeeks[] = [
                    'Year' => $date->year,
                    'Week Number' => 'Week-' . $date->weekOfYear,
                ];
            }

            $this->info('Syncing last 4 weeks of data...');
            $query->where(function ($q) use ($syncWeeks) {
                foreach ($syncWeeks as $pair) {
                    $q->orWhere(function ($sq) use ($pair) {
                        $sq->where('Year', (string)$pair['Year'])
                           ->where('Week Number', $pair['Week Number']);
                    });
                }
            });
        } else {
            $this->info('Syncing all historical records from PowerBI...');
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

        $query->orderBy('id')->chunk($batchSize, function ($rows) use (&$processed, $total, $now) {
            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'asin'        => $row->ASIN,
                    'week_number' => $row->{'Week Number'},
                    'week_date'   => $row->{'Week Date'},
                    'week_year'   => (string)$row->Year,
                    'impressions' => $row->Impression,
                    'clicks'      => $row->Clicks,
                    'orders'      => $row->Orders,
                    'updated_at'  => $now,
                    'created_at'  => $now,
                ];
            }

            if (!empty($data)) {
                DB::table('brand_analytics_weekly_data_bi')->upsert(
                    $data,
                    ['asin', 'week_date', 'week_year'],
                    [
                        'week_number',
                        'impressions',
                        'clicks',
                        'orders',
                        'updated_at',
                    ]
                );
            }

            $processed += count($rows);
            $this->info("Processed {$processed} / {$total}");
        });

        $this->info('Sync complete.');
        return 0;
    }
}
