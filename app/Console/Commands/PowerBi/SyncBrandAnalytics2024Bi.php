<?php

namespace App\Console\Commands\PowerBi;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncBrandAnalytics2024Bi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'powerbi:sync-brand-analytics-2024-bi {mode? : all=sync all records, omit for last 4 weeks only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync 2024BrandAnalytics from PowerBI DB to brand_analytics_2024_bis table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $marketTz = config('timezone.market', 'UTC');
        $mode = $this->argument('mode');
        $batchSize = 1000;

        $this->info('Starting 2024 Brand Analytics Data Sync...');

        $query = DB::connection('powerbi')->table('2024BrandAnalytics');

        if ($mode !== 'all') {
            $this->info('Syncing last 4 weeks of data based on reporting_date...');
            $fourWeeksAgo = Carbon::now($marketTz)->subWeeks(4)->toDateString();
            $query->where('reporting_date', '>=', $fourWeeksAgo);
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
        $skipped   = 0;
        $now = now();

        $query->orderBy('reporting_date')->chunk($batchSize, function ($rows) use (&$processed, &$skipped, $total, $now) {
            $data = [];
            foreach ($rows as $row) {
                // Skip rows where search_query exceeds VARCHAR(255)
                if (strlen((string) $row->search_query) > 255) {
                    $skipped++;
                    continue;
                }

                // Remove commas and spaces if they're formatted as numbers like '1,000'
                $cleanNumeric = function ($val) {
                    if (is_null($val)) return 0;
                    return (float) preg_replace('/[^0-9.]/', '', (string) $val);
                };

                $data[] = [
                    'asin'                       => $row->asin,
                    'name'                       => $row->name ?? null,
                    'search_query'               => $row->search_query,
                    'search_query_score'         => (int) $cleanNumeric($row->search_query_score),
                    'search_query_volume'        => (int) $cleanNumeric($row->search_query_volume),
                    'reporting_date'             => $row->reporting_date,
                    'week'                       => $row->week ?? null,
                    'year'                       => (int) $cleanNumeric($row->year),
                    'impressions_total_count'    => (int) $cleanNumeric($row->impressions_total_count),
                    'impressions_asin_count'     => (int) $cleanNumeric($row->impressions_asin_count),
                    'clicks_total_count'         => (int) $cleanNumeric($row->clicks_total_count),
                    'clicks_asin_count'          => (int) $cleanNumeric($row->clicks_asin_count),
                    'clicks_price_median'        => $cleanNumeric($row->clicks_price_median),
                    'clicks_asin_price_median'   => $cleanNumeric($row->clicks_asin_price_median),
                    'clicks_shipping_same_day'   => (int) $cleanNumeric($row->clicks_shipping_same_day),
                    'clicks_shipping_1d'         => (int) $cleanNumeric($row->clicks_shipping_1d),
                    'clicks_shipping_2d'         => (int) $cleanNumeric($row->clicks_shipping_2d),
                    'cart_adds_total_count'      => (int) $cleanNumeric($row->cart_adds_total_count),
                    'cart_adds_asin_count'       => (int) $cleanNumeric($row->cart_adds_asin_count),
                    'purchases_total_count'      => (int) $cleanNumeric($row->purchases_total_count),
                    'purchases_asin_count'       => (int) $cleanNumeric($row->purchases_asin_count),
                    'impressions_asin_share_pct' => $cleanNumeric($row->impressions_asin_share_pct),
                    'clicks_rate_pct'            => $cleanNumeric($row->clicks_rate_pct),
                    'clicks_asin_share_pct'      => $cleanNumeric($row->clicks_asin_share_pct),
                    'purchases_rate_pct'         => $cleanNumeric($row->purchases_rate_pct),
                    'purchases_asin_share_pct'   => $cleanNumeric($row->purchases_asin_share_pct),
                    'updated_at'                 => $now,
                    'created_at'                 => $now,
                ];
            }

            if (!empty($data)) {
                DB::table('brand_analytics_2024_bis')->upsert(
                    $data,
                    ['asin', 'search_query', 'reporting_date'],
                    [
                        'name',
                        'search_query_score',
                        'search_query_volume',
                        'week',
                        'year',
                        'impressions_total_count',
                        'impressions_asin_count',
                        'clicks_total_count',
                        'clicks_asin_count',
                        'clicks_price_median',
                        'clicks_asin_price_median',
                        'clicks_shipping_same_day',
                        'clicks_shipping_1d',
                        'clicks_shipping_2d',
                        'cart_adds_total_count',
                        'cart_adds_asin_count',
                        'purchases_total_count',
                        'purchases_asin_count',
                        'impressions_asin_share_pct',
                        'clicks_rate_pct',
                        'clicks_asin_share_pct',
                        'purchases_rate_pct',
                        'purchases_asin_share_pct',
                        'updated_at',
                    ]
                );
            }

            $processed += count($rows);
            $this->info("Processed {$processed} / {$total}" . ($skipped > 0 ? " (skipped {$skipped} long search_query rows)" : ''));
        });

        $this->info("Sync complete. Processed: {$processed}, Skipped: {$skipped}.");
        return 0;
    }
}
