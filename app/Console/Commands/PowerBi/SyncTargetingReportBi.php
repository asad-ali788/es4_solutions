<?php

namespace App\Console\Commands\PowerBi;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncTargetingReportBi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'powerbi:sync-targeting-report-bi {mode? : all=sync last 2 months, omit for today only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Targeting_Report data from PowerBI DB to TargetingReportBi table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $marketTz = config('timezone.market');
        $mode = $this->argument('mode');
        $batchSize = 3000;
        if ($mode === 'all') {
            $fromDate = Carbon::now($marketTz)->subMonths(2)->startOfDay();
            $toDate = Carbon::now($marketTz)->endOfDay();
            $this->info('Syncing last 2 months from PowerBI Targeting_Report...');
            $query = DB::connection('powerbi')
                ->table('Targeting_Report')
                ->whereBetween('Date', [$fromDate, $toDate]);
        } else {
            $today = Carbon::today($marketTz);
            $this->info('Syncing today\'s records from PowerBI Targeting_Report...');
            $query = DB::connection('powerbi')
                ->table('Targeting_Report')
                ->whereDate('Date', $today);
        }

        $total = $query->count();
        $this->info('Fetched ' . $total . ' rows from PowerBI.');
        $processed = 0;

        $query->orderBy('Date')->chunk($batchSize, function ($rows) use (&$processed, $total) {
            $now = now();
            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'report_date'    => $row->Date,
                    'campaign_name'  => $row->{'Campaign Name'},
                    'portfolio_name' => $row->{'Portfolio name'},
                    'country'        => 'US',
                    'targeting'      => $row->Targeting,
                    'match_type'     => $row->{'Match Type'},
                    'impressions'    => $row->Impressions,
                    'clicks'         => $row->Clicks,
                    'spend'          => $row->Spend,
                    'sales'          => $row->Sales,
                    'units'          => $row->Units,
                    'updated_at'     => $now,
                    'created_at'     => $now,
                ];
            }
            DB::table('targeting_report_bi')->upsert(
                $data,
                [
                    'report_date',
                    'campaign_name',
                    'targeting',
                ],
                [
                    'portfolio_name',
                    'country',
                    'match_type',
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
