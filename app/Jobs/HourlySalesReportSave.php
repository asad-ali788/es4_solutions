<?php

namespace App\Jobs;

use App\Models\AmzReportsLog;
use App\Models\HourlySales;
use App\Models\HourlySalesSnapshot;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable as BusDispatchable;
use App\Services\AmazonReportParser;
use App\Services\DailyReportGetService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HourlySalesReportSave implements ShouldQueue
{
    use BusDispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): string
    {
        Log::channel('spApi')->info('✅ HourlySalesReportSave Started');

        $marketTz = config('timezone.market');

        // Today in LA (used to check against existing sale_date)
        $todayLA = Carbon::now($marketTz)->toDateString();

        $startUTC   = Carbon::now($marketTz)->startOfDay()->subDay()->timezone('UTC');
        $endUTC     = Carbon::now($marketTz)->endOfDay()->timezone('UTC');
        $beforeCron = HourlySales::whereIn('marketplace_id', ['Amazon.com', 'Amazon.ca', 'Amazon.com.mx'])->whereDate('sale_date', $todayLA)->sum('total_units');
        $parser     = new AmazonReportParser();
        $service    = app(DailyReportGetService::class);

        $logReports = AmzReportsLog::where('report_status', 'IN_PROGRESS')->where('report_frequency', 'hourly')->where('report_type', 'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL')
            ->whereBetween('start_date', [$startUTC, $endUTC])
            ->get();  
              
        if ($logReports->isEmpty()) {
            return '📭 Hourly Sales - No reports in progress for ' . $todayLA;
        }

        $results = [];

        foreach ($logReports as $report) {
            try {
                $result = $service->getReportDocument($report->report_id);

                if (!$result) {
                    $results[] = "⏳ Hourly Sales - Report {$report->report_id} is not ready yet.";
                    continue;
                }

                $batchData = [];
                foreach ($parser->parse($report->report_id) as $data) {
                    $purchaseDateUTC = $data['purchase-date'] ?? null;
                    $saleDatetimeLA  = Carbon::parse($purchaseDateUTC, 'UTC')->setTimezone($marketTz);
                    $batchData[] = [
                        'sku'            => $data['sku'] ?? null,
                        'asin'           => $data['asin'] ?? null,
                        'marketplace_id' => $data['sales-channel'] ?? null,
                        'sale_date'      => $saleDatetimeLA->format('Y-m-d H:i:s'),
                        'total_units'    => isset($data['quantity']) ? (int) $data['quantity'] : null,
                        'total_cost'     => (float) ($data['item-price'] ?? 0),
                        'total_revenue'  => isset($data['item-price']) ? (float) $data['item-price'] : null,
                        'currency'       => $data['currency'] ?? null,
                        'created_at'     => now(),
                    ];
                }

                $grouped = [];
                foreach ($batchData as $row) {
                    $key = $row['marketplace_id'] . '|' . $row['sku'];
                    if (!isset($grouped[$key])) {
                        $grouped[$key] = $row;
                    } else {
                        $grouped[$key]['total_units']   += $row['total_units'];
                        $grouped[$key]['total_revenue']  = round($grouped[$key]['total_revenue'] + $row['total_revenue'], 2);
                    }
                }

                $finalData = array_values($grouped);
                // Fetch existing sales for the same date range
                $existingSales = HourlySales::whereBetween(
                    'sale_date',
                    [Carbon::now($marketTz)->startOfDay(), Carbon::now($marketTz)]
                )->get()->keyBy(function ($item) use ($marketTz) {
                    return $item->marketplace_id . '|' . $item->sku . '|' . Carbon::parse($item->sale_date,$marketTz)->toDateString();
                });

                $toInsert = [];
                $toUpdate = [];

                foreach ($finalData as $row) {
                    $key = $row['marketplace_id'] . '|' . $row['sku'] . '|' . Carbon::parse($row['sale_date'],$marketTz)->toDateString();

                    if ($existingSales->has($key)) {
                        $existing                = $existingSales[$key];
                        $existing->total_units   = $row['total_units'];
                        $existing->total_revenue = $row['total_revenue'];
                        $existing->total_cost    = $row['total_cost'];
                        $existing->currency      = $row['currency'];
                        $existing->updated_at    = now();

                        $toUpdate[] = $existing;
                    } else {
                        $toInsert[] = $row;
                    }
                }


                DB::beginTransaction();

                foreach (array_chunk($toInsert, 500) as $chunk) {
                    HourlySales::insert($chunk);
                }

                foreach (array_chunk($toUpdate, 500) as $chunk) {
                    foreach ($chunk as $record) {
                        $record->save();
                    }
                }
                $afterCron = HourlySales::whereIn('marketplace_id', ['Amazon.com', 'Amazon.ca', 'Amazon.com.mx'])->whereDate('sale_date', $todayLA)->sum('total_units');
                $newUnitsSold = max(0, $afterCron - $beforeCron);
                // Insert snapshot
                HourlySalesSnapshot::create([
                    'snapshot_time' => $report->end_date ?? Carbon::now($marketTz)->timezone('UTC'),
                    'total_units'   => $newUnitsSold,
                ]);
                AmzReportsLog::where('report_id', $report->report_id)->update([
                    'report_status' => 'DONE',
                ]);

                DB::commit();
                Cache::forget('hourly_sales_today');
                $results[] = "✅ Hourly Sales - Report ID {$report->report_id} processed successfully.";
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::channel('spApi')->error($e);
                $results[] = "❌ Hourly Sales - Failed to process report ID {$report->report_id}";
            }
            sleep(3);
        }
        Log::channel('spApi')->info('✅ HourlySalesReportSave Completed');

        return implode(PHP_EOL, $results);
    }
}
