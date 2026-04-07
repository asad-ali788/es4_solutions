<?php

namespace App\Console\Commands\PowerBi;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncAdvertisedProductBi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'powerbi:sync-advertised-product-bi {--from=} {--to=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Advertised_Product data from PowerBI DB to advertised_product_bi table. Optionally provide --from=YYYY-MM-DD and --to=YYYY-MM-DD for a date range.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $marketTz = config('timezone.market');
        $batchSize = 5000;

        // Parse date range options
        $from = $this->option('from');
        $to = $this->option('to');

        if ($from) {
            $fromDate = Carbon::parse($from, $marketTz)->startOfDay();
        } else {
            $fromDate = Carbon::today($marketTz)->subDay();
        }

        if ($to) {
            $toDate = Carbon::parse($to, $marketTz)->endOfDay();
        } else {
            $toDate = $fromDate->copy();
        }

        $this->info("Syncing records from PowerBI Advertised_product for date range: " . $fromDate->toDateString() . " to " . $toDate->toDateString());

        $query = DB::connection('powerbi')
            ->table('Advertised_product')
            ->whereBetween('Date', [$fromDate->toDateString(), $toDate->toDateString()]);

        $total = $query->count();
        $this->info('Fetched ' . $total . ' rows from PowerBI.');
        $processed = 0;

        $query->orderBy('Date')->chunk($batchSize, function ($rows) use (&$processed, $total) {
            $now = now();
            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'report_date'    => $row->Date,
                    'campaign_name'  => $row->{'Campaign_Name'} ?? null,
                    'asin'           => $row->ASIN ?? null,
                    'currency'       => $row->Currency ?? null,
                    'units'          => $row->Units ?? null,
                    'sales'          => $row->Sales ?? null,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }
            DB::table('advertised_product_bi')->upsert(
                $data,
                [
                    'report_date',
                    'campaign_name',
                    'asin',
                ],
                [
                    'currency',
                    'units',
                    'sales',
                    'updated_at',
                ]
            );
            $processed += count($rows);
            $this->info("Processed $processed / $total");
        });

        $this->info('Sync complete.');
    }
}
