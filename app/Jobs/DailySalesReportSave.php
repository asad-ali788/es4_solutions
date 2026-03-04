<?php

namespace App\Jobs;

use App\Models\AmzReportsLog;
use App\Models\DailySales;
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

class DailySalesReportSave implements ShouldQueue
{
    use BusDispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): string
    {
        Log::channel('spApi')->info('✅ DailySalesReportSave Started');

        $parser   = new AmazonReportParser();
        $service  = app(DailyReportGetService::class);
        $marketTz = config('timezone.market');

        $cutoff = Carbon::now($marketTz)->startOfDay()->timezone('UTC');
        $from   = Carbon::now($marketTz)->subDays(4)->timezone('UTC');

        $logReports = AmzReportsLog::where('report_status', 'IN_PROGRESS')
            ->whereBetween('start_date', [$from, $cutoff])
            ->where('report_frequency', 'daily')
            ->get();

        if ($logReports->isEmpty()) {
            return '📭Daily Sales - No reports in progress for ' . $cutoff->toDateString();
        }

        $results = [];

        foreach ($logReports as $report) {
            try {
                $result = $service->getReportDocument($report->report_id);

                if (!$result) {
                    $results[] = "⏳Daily Sales - Report [{$report->report_id}] is not ready yet.";
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
                        'sale_date'      => $saleDatetimeLA->toDateString(),
                        'sale_datetime'  => $saleDatetimeLA->format('Y-m-d H:i:s'),
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

                DB::beginTransaction();
                foreach (array_chunk($finalData, 500) as $chunk) {
                    DailySales::insert($chunk);
                }

                AmzReportsLog::where('report_id', $report->report_id)->update([
                    'report_status' => 'DONE',
                ]);
                
                DB::commit();
                // HourlySalesSnapshot::truncate();
                Cache::forget('daily_sales');
                Cache::forget('top_selling_products');
                $results[] = "✅Daily Sales - Report ID {$report->report_id} processed successfully.";
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::channel('spApi')->warning($e);
                $results[] = "❌Daily Sales - Failed to process report ID {$report->report_id}";
            }
        }
        Log::channel('spApi')->info('✅ DailySalesReportSave Completed');

        return implode(PHP_EOL, $results);
    }
}
