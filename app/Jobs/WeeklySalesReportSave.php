<?php

namespace App\Jobs;

use App\Models\AmzReportsLog;
use App\Models\WeeklySales;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable as BusDispatchable;
use App\Services\AmazonReportParser;
use App\Services\DailyReportGetService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WeeklySalesReportSave implements ShouldQueue
{
    use BusDispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): string
    {
        $marketTz = config('timezone.market');

        $startWeek = Carbon::now($marketTz)
            ->subWeek()
            ->startOfWeek(Carbon::MONDAY)
            ->timezone('UTC');

        $endWeek = Carbon::now($marketTz)
            ->subWeek()
            ->endOfWeek(Carbon::SUNDAY)
            ->timezone('UTC');

        $parser    = new AmazonReportParser();
        $service   = app(DailyReportGetService::class);

        $report = AmzReportsLog::where('report_status', 'IN_PROGRESS')
            ->where('start_date', $startWeek)
            ->where('end_date', $endWeek)
            ->first();

        if (!$report) {
            return '📭 Weekly Sales - No reports in progress for week starting ' . $startWeek->toDateString();
        }

        try {
            if (empty($report->report_id)) {
                return "⚠️ Weekly Sales - Missing report_id.";
            }

            $result = $service->getReportDocument($report->report_id);

            if (!$result) {
                return "⏳ Weekly Sales - Report [{$report->report_id}] is not ready yet.";
            }

            $batchData = [];
            foreach ($parser->parse($report->report_id) as $data) {
                $batchData[] = [
                    'sku'            => $data['sku'] ?? null,
                    'asin'           => $data['asin'] ?? null,
                    'marketplace_id' => $data['sales-channel'] ?? null,
                    'sale_date'      => isset($data['purchase-date']) ? Carbon::parse($data['purchase-date'], 'UTC')->format('Y-m-d H:i:s') : null,
                    'total_units'    => (int) ($data['quantity'] ?? 0),
                    'total_revenue'  => (float) ($data['item-price'] ?? 0),
                    'total_cost'     => (float) ($data['item-price'] ?? 0),
                    'currency'       => $data['currency'] ?? null,
                    'week_number'    => Carbon::parse($data['purchase-date'], 'UTC')->isoWeek() ?? null,
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
                    $grouped[$key]['total_revenue'] += $row['total_revenue'];
                }
            }

            $finalData = array_values($grouped);

            DB::beginTransaction();
            foreach (array_chunk($finalData, 500) as $chunk) {
                WeeklySales::insert($chunk);
            }

            AmzReportsLog::where('report_id', $report->report_id)->update([
                'report_status' => 'DONE',
            ]);

            DB::commit();
            return "✅  Weekly Sales - Report ID {$report->report_id} processed successfully.";
        } catch (\Throwable $e) {
            DB::rollBack();
            return "❌  Weekly Sales - Report ID {$report->report_id} failed: " . $e->getMessage();
        }

        return '✅  Weekly Sales - All weekly reports processed.';
    }
}
