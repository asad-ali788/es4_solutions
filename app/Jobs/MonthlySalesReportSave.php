<?php

namespace App\Jobs;

use App\Models\AmzReportsLog;
use App\Models\MonthlySales;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable as BusDispatchable;
use App\Services\AmazonReportParser;
use App\Services\DailyReportGetService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonthlySalesReportSave implements ShouldQueue
{
    use BusDispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): string
    {
        Log::channel('spApi')->info('✅ MonthlySalesReportSave Started');

        ini_set('memory_limit', '10240M');

        $marketTz = config('timezone.market');
        $base = Carbon::now($marketTz)->subMonthsNoOverflow();

        $startMonth = $base->copy()
            ->startOfMonth()
            ->timezone('UTC');

        $endMonth = $base->copy()
            ->endOfMonth()
            ->timezone('UTC');
        $parser     = new AmazonReportParser();
        $service    = app(DailyReportGetService::class);

        $report = AmzReportsLog::where('start_date', $startMonth)
            ->where('end_date', $endMonth)
            ->first();

        if (!$report) {
            Log::channel('spApi')->warning('📭 Monthly Sales - No reports in progress for Monthly starting ' . $startMonth->toDateString());
            return '📭 Monthly Sales - No reports in progress for Monthly starting ' . $startMonth->toDateString();
        }

        try {
            $result = $service->getReportDocument($report->report_id);
            if (!$result) {
                Log::channel('spApi')->warning("⏳  Monthly Sales - Report [{$report->report_id}] is not ready yet.");
            }

            $batchData = [];
            foreach ($parser->parse($report->report_id) as $data) {
                $purchaseDateUTC   = $data['purchase-date'] ?? null;
                $saleDatetimeLocal = $purchaseDateUTC
                    ? Carbon::parse($purchaseDateUTC, 'UTC')->setTimezone($marketTz)
                    : null;

                $batchData[] = [
                    'sku'            => $data['sku'] ?? null,
                    'asin'           => $data['asin'] ?? null,
                    'marketplace_id' => $data['sales-channel'] ?? null,
                    'sale_date'      => $saleDatetimeLocal?->toDateString(),
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
                MonthlySales::insert($chunk);
            }

            AmzReportsLog::where('report_id', $report->report_id)->update([
                'report_status' => 'DONE',
            ]);

            DB::commit();
            Log::channel('spApi')->info('✅ MonthlySalesReportSave Completed');

            return "✅  Monthly Sales - Successfully processed report ID {$report->report_id}";
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::channel('spApi')->error("❌  Monthly Sales - Failed to process report ID {$report->report_id}: " . $e->getMessage());
            return "❌  Monthly Sales - Failed to process report ID {$report->report_id}: " . $e->getMessage();
        }
        Log::channel('spApi')->info('✅ MonthlySalesReportSave Completed');

        return '⚠️  Monthly Sales - No reports were processed.';
    }
}
