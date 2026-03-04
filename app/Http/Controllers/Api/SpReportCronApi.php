<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AmazonReportParser;
use App\Services\DailyReportGetService;
use Carbon\Carbon;
use App\Models\AmzReportsLog;
use Illuminate\Support\Facades\DB;
use SellingPartnerApi\Seller\ReportsV20210630\Dto\CreateReportSpecification;
use App\Models\DailySales;
use App\Models\MonthlySales;
use App\Models\Product;
use Illuminate\Http\Request;
use SellingPartnerApi\Seller\SellerConnector;
use App\Models\WeeklySales;
use Illuminate\Support\Facades\Storage;

class SpReportCronApi extends Controller
{
    // Daily Sales FeedId Generate
    public function dailySalesApi(SellerConnector $connector, Request $request)
    {
        $api            = $connector->reportsV20210630();
        $reportType     = 'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL';
        $marketplaceIds = ['ATVPDKIKX0DER', 'A2EUQ1WTGCTBG2', 'A1AM78C64UM0Y8'];

        $marketTz = config('timezone.market'); // e.g., 'America/Los_Angeles'

        // Normalize and convert to UTC
        $startDate = Carbon::parse($request->input('date'), $marketTz)
            ->startOfDay()
            ->timezone('UTC');

        $endDate = Carbon::parse($request->input('date'), $marketTz)
            ->endOfDay()
            ->timezone('UTC');

        $exists = AmzReportsLog::where('report_type', $reportType)
            ->whereDate('start_date', $startDate->toDateString())
            ->whereDate('end_date', $endDate->toDateString())
            ->where('marketplace_ids', json_encode($marketplaceIds))
            ->where('report_frequency', 'daily')
            ->exists();

        if ($exists) {
            return [
                'success' => false,
                'message' => '⚠️Daily Report already created for the given date and marketplace.',
            ];
        }
        // Build report request payload
        $reportSpec = new CreateReportSpecification(
            reportType: $reportType,
            marketplaceIds: $marketplaceIds,
            dataStartTime: $startDate,
            dataEndTime: $endDate
        );

        $response       = $api->createReport($reportSpec)->json();
        // Log the report request to the local database
        AmzReportsLog::create([
            'report_type'      => $reportType,
            'report_frequency' => 'daily',
            'report_id'        => $response['reportId'] ?? null,
            'report_status'    => $response['processingStatus'] ?? 'IN_PROGRESS',
            'start_date'       => $startDate,
            'end_date'         => $endDate,
            'marketplace_ids'  => $marketplaceIds,
        ]);

        // Log the result for debugging/auditing
        logger()->info('Api Amazon Daily Report Generated', $response);
        return response()->json([
            'success' => true,
            'message' => "Api Amazon Daily Report Generated",
            'feedId'  => $response,
        ], 500);
    }
    // Daily Sales Get and save data
    public function dailySalesApiSave(Request $request)
    {
        $parser   = new AmazonReportParser();
        $service  = app(DailyReportGetService::class);
        $marketTz = config('timezone.market');
        $feedId   = $request->input('feedId');
        if (!$feedId) {
            return '📭 No reportId passed';
        }
        try {
            $result = $service->getReportDocument($feedId);

            if (!$result) {
                return "⏳ Report Daily [{$feedId}] is not ready yet.";
            }

            $batchData = [];
            foreach ($parser->parse($feedId) as $data) {
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

            AmzReportsLog::where('report_id', $feedId)->update([
                'report_status' => 'DONE',
            ]);

            DB::commit();

            return "✅ Report ID Daily {$feedId} processed successfully.";
        } catch (\Throwable $e) {
            DB::rollBack();
            logger()->warning($e);
            return "❌ Failed to process Daily report ID {$feedId}";
        }
    }

    // Weekly Sales FeedId Generate
    public function weeklySalesApi(SellerConnector $connector, Request $request)
    {
        $api            = $connector->reportsV20210630();
        $reportType     = 'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL';
        $marketplaceIds = ['ATVPDKIKX0DER', 'A2EUQ1WTGCTBG2', 'A1AM78C64UM0Y8'];

        // Normalize and convert to UTC
        $marketTz = config('timezone.market'); // e.g., 'America/Los_Angeles'

        $baseDate = Carbon::parse($request->input('date'), $marketTz);
        // Check if the given date is in the current week (Monday to Sunday)
        if ($baseDate->isSameWeek(Carbon::now($marketTz), Carbon::MONDAY)) {
            return response()->json([
                'success' => false,
                'message' => 'Current week is not allowed. Please select a past week.',
            ], 422);
        }
        $startDate = $baseDate->copy()
            ->startOfWeek(Carbon::MONDAY)
            ->timezone('UTC');

        $endDate = $baseDate->copy()
            ->endOfWeek(Carbon::SUNDAY)
            ->timezone('UTC');

        $exists = AmzReportsLog::where('report_type', $reportType)
            ->whereDate('start_date', $startDate->toDateString())
            ->whereDate('end_date', $endDate->toDateString())
            ->where('marketplace_ids', json_encode($marketplaceIds))
            ->where('report_frequency', 'weekly')
            ->exists();

        if ($exists) {
            return [
                'success' => false,
                'message' => '⚠️ Api Weekly Report already created for the given date and marketplace.',
            ];
        }
        // Build report request payload
        $reportSpec = new CreateReportSpecification(
            reportType: $reportType,
            marketplaceIds: $marketplaceIds,
            dataStartTime: $startDate,
            dataEndTime: $endDate
        );

        $response       = $api->createReport($reportSpec)->json();
        // Log the report request to the local database
        AmzReportsLog::create([
            'report_type'      => $reportType,
            'report_frequency' => 'weekly',
            'report_id'        => $response['reportId'] ?? null,
            'report_status'    => $response['processingStatus'] ?? 'IN_PROGRESS',
            'start_date'       => $startDate,
            'end_date'         => $endDate,
            'marketplace_ids'  => $marketplaceIds,
        ]);

        // Log the result for debugging/auditing
        logger()->info('Api Amazon Weekly Report Generated', $response);
        return response()->json([
            'success' => true,
            'message' => "Api Amazon Weekly Report Generated",
            'feedId'  => $response,
        ], 500);
    }

    // Weekly Sales Get and save data
    public function weeklySalesApiSave(Request $request)
    {
        set_time_limit(300); // 5 minutes
        // Optional: memory boost
        ini_set('memory_limit', '512M');

        $marketTz = config('timezone.market');

        $parser  = new AmazonReportParser();
        $service = app(DailyReportGetService::class);
        $feedId  = $request->input('feedId');
        if (!$feedId) {
            return '📭 No reportId passed';
        }
        $report = AmzReportsLog::where('report_status', 'IN_PROGRESS')
            ->where('report_id', $feedId)
            ->where('report_frequency', 'weekly')
            ->first();
        if (!$report) {
            return '📭 No reports in progress for week starting';
        }

        try {

            $result = $service->getReportDocument($report->report_id);

            if (!$result) {
                return "⏳ Report [{$report->report_id}] is not ready yet.";
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
                    'total_units'    => (int) ($data['quantity'] ?? 0),
                    'total_revenue'  => (float) ($data['item-price'] ?? 0),
                    'total_cost'     => (float) ($data['item-price'] ?? 0),
                    'currency'       => $data['currency'] ?? null,
                    'week_number'    => Carbon::parse($data['purchase-date'], 'UTC')->setTimezone('America/Los_Angeles')->isoWeek() ?? null,
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
            return "✅ Report ID {$report->report_id} processed successfully.";
        } catch (\Throwable $e) {
            DB::rollBack();
            return "❌ Report ID {$report->report_id} failed: " . $e->getMessage();
        }

        return '✅ All weekly reports processed.';
    }

    // Monthly Sales FeedId Generate
    public function monthlySalesApi(SellerConnector $connector, Request $request)
    {
        $api            = $connector->reportsV20210630();
        $reportType     = 'GET_FLAT_FILE_ALL_ORDERS_DATA_BY_ORDER_DATE_GENERAL';
        $marketplaceIds = ['ATVPDKIKX0DER', 'A2EUQ1WTGCTBG2', 'A1AM78C64UM0Y8'];

        // Normalize and convert to UTC
        $marketTz = config('timezone.market'); // e.g., 'America/Los_Angeles'

        $baseDate = Carbon::parse($request->input('date'), $marketTz);
        // Check if the given date is in the current month (same year & month)
        if ($baseDate->isSameMonth(Carbon::now($marketTz))) {
            return response()->json([
                'success' => false,
                'message' => 'Current month is not allowed. Please select a past month.',
            ], 422);
        }
        // Get first and last day of the month in UTC
        $startDate = $baseDate->copy()
            ->startOfMonth()
            ->timezone('UTC');

        $endDate = $baseDate->copy()
            ->endOfMonth()
            ->timezone('UTC');

        // Check if report already exists for that month
        $exists = AmzReportsLog::where('report_type', $reportType)
            ->whereDate('start_date', $startDate->toDateString())
            ->whereDate('end_date', $endDate->toDateString())
            ->where('marketplace_ids', json_encode($marketplaceIds))
            ->where('report_frequency', 'monthly')
            ->exists();

        if ($exists) {
            return [
                'success' => false,
                'message' => '⚠️ Api Monthly Report already created for the given date and marketplace.',
            ];
        }
        // Build report request payload
        $reportSpec = new CreateReportSpecification(
            reportType: $reportType,
            marketplaceIds: $marketplaceIds,
            dataStartTime: $startDate,
            dataEndTime: $endDate
        );

        $response       = $api->createReport($reportSpec)->json();
        // Log the report request to the local database
        AmzReportsLog::create([
            'report_type'      => $reportType,
            'report_frequency' => 'monthly',
            'report_id'        => $response['reportId'] ?? null,
            'report_status'    => $response['processingStatus'] ?? 'IN_PROGRESS',
            'start_date'       => $startDate,
            'end_date'         => $endDate,
            'marketplace_ids'  => $marketplaceIds,
        ]);

        // Log the result for debugging/auditing
        logger()->info('Api Amazon Monthly Report Generated', $response);
        return response()->json([
            'success' => true,
            'message' => "Api Amazon Monthly Report Generated",
            'feedId'  => $response,
        ], 500);
    }

    // Monthly Sales Get and save data
    public function monthlySalesApiSave(Request $request)
    {
        set_time_limit(500); // long minutes
        // memory boost
        ini_set('memory_limit', '1024M');

        $marketTz = config('timezone.market');
        $parser  = new AmazonReportParser();
        $service = app(DailyReportGetService::class);
        $feedId  = $request->input('feedId');
        if (!$feedId) {
            return '📭 No reportId passed';
        }
        $report = AmzReportsLog::where('report_status', 'IN_PROGRESS')
            ->where('report_id', $feedId)
            ->where('report_frequency', 'monthly')
            ->first();

        if (!$report) {
            return '📭 No reports in progress for Month starting';
        }
        try {
            $result = $service->getReportDocument($report->report_id);

            if (!$result) {
                return "⏳ Report [{$report->report_id}] is not ready yet.";
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
                    'total_units'    => (int) ($data['quantity'] ?? 0),
                    'total_revenue'  => (float) ($data['item-price'] ?? 0),
                    'total_cost'     => (float) ($data['item-price'] ?? 0),
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
                    $grouped[$key]['total_revenue'] += $row['total_revenue'];
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
            return "✅ Report ID Monthly {$report->report_id} processed successfully.";
        } catch (\Throwable $e) {
            DB::rollBack();
            return "❌ Report ID Monthly {$report->report_id} failed: " . $e->getMessage();
        }

        return '✅ All Monthly reports processed.';
    }

    public function FbaManagerReport(SellerConnector $connector)
    {
        $api            = $connector->reportsV20210630();
        $reportType     = 'GET_FBA_MYI_UNSUPPRESSED_INVENTORY_DATA';
        $marketplaceIds = ['ATVPDKIKX0DER', 'A2EUQ1WTGCTBG2', 'A1AM78C64UM0Y8'];

        // Build report request payload
        $reportSpec = new CreateReportSpecification(
            reportType: $reportType,
            marketplaceIds: $marketplaceIds,
        );

        $response       = $api->createReport($reportSpec)->json();
        // Log the report request to the local database
        AmzReportsLog::create([
            'report_type'      => $reportType,
            'report_id'        => $response['reportId'] ?? null,
            'report_status'    => $response['processingStatus'] ?? 'IN_PROGRESS',
            'marketplace_ids'  => $marketplaceIds,
        ]);

        // Log the result for debugging/auditing
        logger()->info('FBA Inventory to get FNSKU', $response);
        return response()->json([
            'success' => true,
            'message' => "FBA Inventory to get FNSKU",
            'feedId'  => $response,
        ], 200);
    }

    public function FbaManagerReportSave(SellerConnector $connector, $reportId)
    {
        try {
            $api    = $connector->reportsV20210630();
            $report = $api->getReport($reportId)->json();
            $parser = new AmazonReportParser();

            if (($report['processingStatus'] ?? '') !== 'DONE') {
                return response()->json([
                    'error' => true,
                    'message' => 'Report is not ready yet. Status: ' . ($report['processingStatus'] ?? 'unknown'),
                ], 202);
            }

            $documentId = $report['reportDocumentId'] ?? null;

            if (!$documentId) {
                return response()->json([
                    'error' => true,
                    'message' => 'Report document ID not found.',
                ], 404);
            }

            $reportType  = $report['reportType'];
            $document    = $api->getReportDocument($documentId, $reportType)->json();
            $url         = $document['url'] ?? null;
            $compression = $document['compressionAlgorithm'] ?? null;

            if (!$url) {
                return response()->json([
                    'error' => true,
                    'message' => 'Signed URL not found in report document.',
                ], 500);
            }

            $reportContent = file_get_contents($url);
            if ($compression === 'GZIP') {
                $reportContent = gzdecode($reportContent);
            }

            if ($reportContent === false) {
                return response()->json([
                    'error' => true,
                    'message' => 'Failed to download or decode the report content.',
                ], 500);
            }
            $today       = now('UTC')->toDateString();
            $filename    = "{$today}_report_{$reportId}.txt";
            $storagePath = "api/reports/{$filename}";
            Storage::disk('public')->put($storagePath, $reportContent);

            $batchData = [];
            foreach ($parser->parse($reportId) as $data) {
                if (empty($data['sku'])) {
                    continue;
                }

                $batchData[] = [
                    'sku'        => $data['sku'],
                    'fnsku'      => $data['fnsku'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::beginTransaction();
            // Update fnsku for existing products by matching sku
            if (!empty($batchData)) {
                $skus = collect($batchData)->pluck('sku')->all();

                $existingProducts = Product::whereIn('sku', $skus)->get()->keyBy('sku');

                foreach ($batchData as $data) {
                    if (isset($existingProducts[$data['sku']])) {
                        $existingProducts[$data['sku']]->update([
                            'fnsku'      => $data['fnsku'],
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
            AmzReportsLog::where('report_id', $reportId)->update([
                'report_status' => 'DONE',
            ]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return "❌ Report ID {$reportId} failed: " . $e->getMessage();
        }

        return '✅FNSKU Updated';
    }
}
