<?php

namespace App\Jobs;

use App\Models\AfnInventoryData;
use App\Models\AmzReportsLog;
use App\Models\Notification;
use App\Models\NotificationDetails;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Bus\Dispatchable as BusDispatchable;
use App\Services\AmazonReportParser;
use App\Services\DailyReportGetService;
use Illuminate\Foundation\Queue\Queueable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AfnInventoryDataReportSave implements ShouldQueue
{
    use BusDispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $startDate = Carbon::now('UTC')->subDay()->startOfDay();

        $parser = new AmazonReportParser();
        $service = app(DailyReportGetService::class);
        Log::channel('spApi')->info('✅ AfnInventoryDataReportSave Started');

        $report = AmzReportsLog::where('report_type', 'GET_AFN_INVENTORY_DATA')
            ->where('report_status', 'IN_PROGRESS')
            ->first();

        if (!$report) {
            return '📭 AFN Inventory - No reports in progress for ' . $startDate->toDateString();
        }

        try {
            $result = $service->getReportDocument($report->report_id);

            if (!$result) {
                return "⏳AFN Inventory - Report [{$report->report_id}] is not ready yet.";
            }

            $lowStockItems = [];
            $batchData = [];

            foreach ($parser->parse($report->report_id) as $data) {
                $quantity = (int) ($data['Quantity Available'] ?? 0);
                $sku = $data['seller-sku'] ?? null;

                if (!$sku) {
                    continue;
                }

                $batchData[] = [
                    'seller_sku'               => $sku,
                    'fulfillment_channel_sku'  => $data['fulfillment-channel-sku'] ?? null,
                    'asin'                     => $data['asin'] ?? null,
                    'condition_type'           => $data['condition-type'] ?? null,
                    'warehouse_condition_code' => $data['Warehouse-Condition-code'] ?? null,
                    'quantity_available'       => $quantity,
                    'created_at'               => now(),
                    'updated_at'               => now(),
                ];

                if ($quantity < 10) {
                    $lowStockItems[] = [
                        'sku' => $sku,
                        'quantity_available' => $quantity,
                    ];
                }
            }

            foreach (array_chunk($batchData, 500) as $chunk) {
                $insertedSkus = [];
                $updatedSkus = [];

                foreach ($chunk as $data) {
                    $existing = AfnInventoryData::where('seller_sku', $data['seller_sku'])->first();

                    if ($existing) {
                        $hasChanged = false;
                        $fieldsToCompare = [
                            'fulfillment_channel_sku',
                            'asin',
                            'condition_type',
                            'warehouse_condition_code',
                            'quantity_available',
                        ];

                        foreach ($fieldsToCompare as $field) {
                            if ($existing->$field != $data[$field]) {
                                $hasChanged = true;
                                break;
                            }
                        }

                        if ($hasChanged) {
                            $existing->update($data);
                            $updatedSkus[] = $data['seller_sku'];
                        }
                    } else {
                        AfnInventoryData::create($data);
                        $insertedSkus[] = $data['seller_sku'];
                    }
                }
            }

            /**
             * Notifications for low stock items
             */

            $oldLowStock = NotificationDetails::where('stock_status', 0)->pluck('sku')->toArray();
            $newLowSkus = array_column($lowStockItems, 'sku');

            // → Items restocked
            $restockedSkus = array_diff($oldLowStock, $newLowSkus);
            if (!empty($restockedSkus)) {
                $restockedItems = array_filter(
                    $batchData,
                    fn($item) => in_array($item['seller_sku'], $restockedSkus)
                );

                $restockNotification = Notification::create([
                    'notification_id' => 'N-' . str_pad(Notification::max('id') + 1, 3, '0', STR_PAD_LEFT),
                    'title' => 'Amazon Stock Checked In',
                    'level' => 1,
                    'status' => 0,
                    'created_date' => now(),
                ]);

                $restockDetails = [];
                foreach ($restockedItems as $item) {
                    $restockDetails[] = [
                        'notification_id'      => $restockNotification->id,
                        'sku'                  => $item['seller_sku'],
                        'quantity_available'   => $item['quantity_available'],
                        'stock_status'         => 1,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ];
                }

                NotificationDetails::insert($restockDetails);
            }

            // → Fresh low stock items
            $freshLowStockItems = array_filter(
                $lowStockItems,
                fn($item) => !in_array($item['sku'], $oldLowStock)
            );

            if (!empty($freshLowStockItems)) {
                $notification = Notification::create([
                    'notification_id' => 'N-001',
                    'title'           => 'Low Amazon Stock',
                    'level'           => 1,
                    'status'          => 0,
                    'created_date'    => now(),
                ]);

                $detailsToInsert = [];
                foreach ($freshLowStockItems as $item) {
                    $detailsToInsert[] = [
                        'notification_id'      => $notification->id,
                        'sku'                  => $item['sku'],
                        'quantity_available'   => $item['quantity_available'],
                        'stock_status'         => 0,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ];
                }

                NotificationDetails::insert($detailsToInsert);
            }

            AmzReportsLog::where('report_id', $report->report_id)->update([
                'report_status' => 'DONE'
            ]);

            Log::channel('spApi')->info("✅ AFN Inventory - Report ID {$report->report_id} Completed.");
        } catch (\Throwable $e) {
            Log::channel('spApi')->error("❌ AFN Inventory - Failed to process report ID {$report->report_id}");
        }
    }
}