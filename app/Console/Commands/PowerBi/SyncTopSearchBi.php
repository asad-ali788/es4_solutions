<?php

namespace App\Console\Commands\PowerBi;

use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;

class SyncTopSearchBi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'powerbi:sync-top-search-bi {mode?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Top_search from PowerBI DB to top_search_bis table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $marketTz = config('timezone.market', 'UTC');
        $mode = $this->argument('mode');
        $batchSize = 1000;

        $lookbackWeeks = ($mode === 'all') ? 4 : 1;
        $this->info("Starting Top Search Data Sync (Weeks back: {$lookbackWeeks})...");

        $now = Carbon::now($marketTz);
        $syncWeeks = [];
        
        for ($i = 0; $i < $lookbackWeeks; $i++) {
            $date = $now->copy()->subWeeks($i);
            $syncWeeks[] = (string) $date->weekOfYear;
        }

        $query = DB::connection('powerbi')->table('Top_search')->whereIn('Week', $syncWeeks);

        try {
            $total = $query->count();
            $this->info("Fetched {$total} rows from PowerBI.");
        } catch (\Exception $e) {
            $this->error("Error connecting to PowerBI: " . $e->getMessage());
            return 1;
        }

        $processed = 0;
        $nowDatetime = now();

        $query->orderBy('Reporting Date')->chunk($batchSize, function ($rows) use (&$processed, $total, $nowDatetime) {
            $data = [];
            foreach ($rows as $row) {
                // Remove commas and spaces if they're formatted as numbers like '1,000'
                $searchFreqRank = preg_replace('/[^0-9]/', '', $row->{'Search Frequency Rank'} ?? '');

                $uniqueKey = $row->{'Search Term'} . '|' . $row->{'Reporting Date'};

                $data[$uniqueKey] = [
                    'search_frequency_rank'      => (int) $searchFreqRank,
                    'search_term'                => $row->{'Search Term'},
                    'top_clicked_brand_1'        => $row->{'Top Clicked Brand #1'} ?? null,
                    'top_clicked_brand_2'        => $row->{'Top Clicked Brands #2'} ?? null,
                    'top_clicked_brand_3'        => $row->{'Top Clicked Brands #3'} ?? null,
                    'top_clicked_category_1'     => $row->{'Top Clicked Category #1'} ?? null,
                    'top_clicked_category_2'     => $row->{'Top Clicked Category #2'} ?? null,
                    'top_clicked_category_3'     => $row->{'Top Clicked Category #3'} ?? null,
                    'top_clicked_product_1_asin' => $row->{'Top Clicked Product #1: ASIN'} ?? null,
                    'top_clicked_product_2_asin' => $row->{'Top Clicked Product #2: ASIN'} ?? null,
                    'top_clicked_product_3_asin' => $row->{'Top Clicked Product #3: ASIN'} ?? null,
                    'week'                       => $row->{'Week'} ?? null,
                    'reporting_date'             => $row->{'Reporting Date'},
                    'updated_at'                 => $nowDatetime,
                    'created_at'                 => $nowDatetime,
                ];
            }

            if (!empty($data)) {
                DB::table('top_search_bis')->upsert(
                    array_values($data), // strict numeric array for upsert
                    ['search_term', 'reporting_date'],
                    [
                        'search_frequency_rank',
                        'top_clicked_brand_1',
                        'top_clicked_brand_2',
                        'top_clicked_brand_3',
                        'top_clicked_category_1',
                        'top_clicked_category_2',
                        'top_clicked_category_3',
                        'top_clicked_product_1_asin',
                        'top_clicked_product_2_asin',
                        'top_clicked_product_3_asin',
                        'week',
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
