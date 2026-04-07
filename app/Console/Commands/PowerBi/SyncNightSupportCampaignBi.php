<?php

namespace App\Console\Commands\PowerBi;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncNightSupportCampaignBi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'powerbi:sync-night-support-campaign-bi {mode? : all=sync last 7 days, omit for today only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync night_support_campaign_1 data from PowerBI DB to night_support_campaign_bi table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $marketTz = config('timezone.market');
        $mode = $this->argument('mode');
        $batchSize = 1000;
        if ($mode === 'all') {
            $fromDate = Carbon::now($marketTz)->subDays(7)->startOfDay();
            $toDate = Carbon::now($marketTz)->endOfDay();
            $this->info('Syncing last 7 days from PowerBI night_support_campaign_1...');
            $query = DB::connection('powerbi')
                ->table('night_support_campaign_1')
                ->whereBetween('Date', [$fromDate, $toDate]);
        } else {
            $today = Carbon::today($marketTz);
            $this->info('Syncing today\'s records from PowerBI night_support_campaign_1...');
            $query = DB::connection('powerbi')
                ->table('night_support_campaign_1')
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
                    'campaign'       => $row->Campaigns,
                    'state'          => $row->State,
                    'status'         => $row->Status,
                    'type'           => $row->Type,
                    'targeting'      => mb_substr($row->Targeting, 0, 191),
                    'impressions'    => $row->Impressions,
                    'clicks'         => $row->Clicks,
                    'ctr'            => $row->CTR,
                    'spend_usd'      => $row->Spend_USD,
                    'cpc_usd'        => $row->CPC_USD,
                    'orders'         => $row->Orders,
                    'sales_usd'      => $row->Sales_USD,
                    'units_sold'     => $row->Units_sold,
                    'updated_at'     => $now,
                    'created_at'     => $now,
                ];
            }
            DB::table('night_support_campaign_bi')->upsert(
                $data,
                [
                    'report_date',
                    'campaign',
                    'type',
                    'targeting',
                ],
                [
                    'state',
                    'status',
                    'impressions',
                    'clicks',
                    'ctr',
                    'spend_usd',
                    'cpc_usd',
                    'orders',
                    'sales_usd',
                    'units_sold',
                    'updated_at',
                ]
            );
            $processed += count($rows);
            $this->info("Processed $processed / $total");
        });

        $this->info('Sync complete.');
    }
}
