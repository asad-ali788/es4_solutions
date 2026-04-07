<?php

namespace App\Console\Commands\PowerBi;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncSearchTermReportBi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'powerbi:sync-search-term-report-bi {from? : Start date (Y-m-d)} {to? : End date (Y-m-d)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Search_Term_Report data from PowerBI DB to search_term_report_bi table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $marketTz = config('timezone.market');
        $batchSize = 3000;
        $fromArg = $this->argument('from');
        $toArg = $this->argument('to');
        $fromDate = $fromArg ? Carbon::parse($fromArg, $marketTz)->startOfDay() : Carbon::today($marketTz);
        $toDate = $toArg ? Carbon::parse($toArg, $marketTz)->endOfDay() : Carbon::today($marketTz)->endOfDay();
        $this->info("Syncing records from $fromDate to $toDate from PowerBI search term report...");
        $query = DB::connection('powerbi')
            ->table('search term report')
            ->whereBetween('Date', [$fromDate, $toDate]);

        $total = $query->count();
        $this->info('Fetched ' . $total . ' rows from PowerBI.');
        $processed = 0;

        $query->orderBy('Date')->chunk($batchSize, function ($rows) use (&$processed, $total) {
            $now = now();
            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'report_date'           => $row->Date,
                    'campaign_name'         => $row->{'Campaign Name'} ?? null,
                    'portfolio_name'        => $row->{'Portfolio name'} ?? null,
                    'targeting'             => $row->Targeting ?? null,
                    'match_type'            => $row->{'Match Type'} ?? null,
                    'customer_search_term'  => $row->{'Customer Search Term'} ?? null,
                    'impressions'           => $row->Impressions ?? null,
                    'clicks'                => $row->Clicks ?? null,
                    'spend'                 => $row->Spend ?? null,
                    'sales'                 => $row->Sales ?? null,
                    'units'                 => $row->Units ?? null,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ];
            }
            DB::table('search_term_report_bi')->upsert(
                $data,
                [
                    'report_date',
                    'campaign_name',
                    'targeting',
                ],
                [
                    'portfolio_name',
                    'match_type',
                    'customer_search_term',
                    'impressions',
                    'clicks',
                    'spend',
                    'sales',
                    'units',
                    'updated_at',
                ]
            );
            $processed += count($rows);
            $this->info("Processed $processed / $total");
        });

        $this->info('Sync complete.');
    }
}
