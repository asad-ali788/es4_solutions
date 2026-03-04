<?php

namespace App\Jobs;

use App\Models\FbaInventoryUsa;
use App\Models\AmzReportsLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable as BusDispatchable;
use App\Services\AmazonReportParser;
use App\Services\DailyReportGetService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Queue\Queueable;

class FbaInventoryUsaReportSave implements ShouldQueue
{
    use BusDispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $parser = new AmazonReportParser();
        $service = app(DailyReportGetService::class);
        $logReports = AmzReportsLog::where('report_status', 'IN_PROGRESS')
            ->where('report_type', 'GET_FBA_MYI_ALL_INVENTORY_DATA')
            ->get();

        if ($logReports->isEmpty()) {
            return '📭 FBA Inventory USA - No reports in progress';
        }

        foreach ($logReports as $report) {
            try {
                $isReady = $service->getReportDocument($report->report_id);

                if (!$isReady) {
                    return "⏳ FBA Inventory USA - Report [{$report->report_id}] is not ready yet.";
                }

                $batchData = [];
                foreach ($parser->parse($report->report_id) as $data) {
                    $sku = $data['sku'] ?? null;
                    if (!$sku) {
                        continue;
                    }

                    $batchData[] = [
                        'sku'           => $sku,
                        'instock'       => (int)($data['afn-fulfillable-quantity'] ?? 0),
                        'totalstock'    => (int)($data['afn-total-quantity'] ?? 0),
                        'reserve_stock' => (int)($data['afn-reserved-quantity'] ?? 0),
                        'country'       => 'USA',
                        'add_date'      => now(),
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                }

                DB::beginTransaction();

                foreach (array_chunk($batchData, 300) as $chunk) {
                    $insertedSkus = [];
                    $updatedSkus = [];

                    foreach ($chunk as $data) {
                        $existing = FbaInventoryUsa::where('sku', $data['sku'])->first();

                        if ($existing) {
                            $hasChanged = false;
                            $fieldsToCompare = [
                                'instock',
                                'totalstock',
                                'reserve_stock',
                                'country',
                                'add_date',
                            ];

                            foreach ($fieldsToCompare as $field) {
                                if ($existing->$field != $data[$field]) {
                                    $hasChanged = true;
                                    break;
                                }
                            }

                            if ($hasChanged) {
                                $existing->update($data);
                                $updatedSkus[] = $data['sku'];
                            }
                        } else {
                            FbaInventoryUsa::create($data);
                            $insertedSkus[] = $data['sku'];
                        }
                    }

                    // if (!empty($insertedSkus)) {
                    //     Log::info("🆕 Inserted FBA inventory records for SKUs: " . implode(', ', $insertedSkus));
                    // }
                    // if (!empty($updatedSkus)) {
                    //     Log::info("🔁 Updated FBA inventory records for SKUs: " . implode(', ', $updatedSkus));
                    // }
                }

                AmzReportsLog::where('report_id', $report->report_id)->update([
                    'report_status' => 'DONE',
                ]);

                DB::commit();

                Log::info("✅ FBA Inventory USA - Report ID {$report->report_id} processed successfully.");
                return "✅ FBA Inventory USA - Report ID {$report->report_id} processed successfully.";
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error(" FBA Inventory USA - Error processing Report ID {$report->report_id} : {$e->getMessage()}");
                return "❌ FBA Inventory USA - Report ID [{$report->report_id} failed. Error: {$e->getMessage()}";
            }
        }

        return "Fba Inventory Usa Report Save Results";
    }
}
