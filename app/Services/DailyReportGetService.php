<?php

namespace App\Services;

use App\Models\AmzReportsLog;
use Illuminate\Support\Facades\Log;
use SellingPartnerApi\Seller\ReportsV20210630\Dto\CreateReportSpecification;
use SellingPartnerApi\Seller\SellerConnector;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class DailyReportGetService
{
    /**
     * Inject the Amazon SellerConnector to access SP API.
     */
    public function __construct(
        protected SellerConnector $connector
    ) {}

    /**
     * Creates a daily report request via Amazon SP API and logs it to the database.
     *
     * @param string $reportType
     * @param array $marketplaceIds
     * @return array
     */

    public function getReportDocument(string $reportId): string|false
    {
        try {
            $api = $this->connector->reportsV20210630();
            $report = $api->getReport($reportId)->json();
            if (empty($report)) {
                Log::warning("Report not found for ID {$reportId}");
                return false;
            }

            // Check if the report Fatel ot cancelled if exit the code
            $processingStatus = $report['processingStatus'] ?? null;
            if (in_array($processingStatus, ['FATAL', 'CANCELLED'])) {
                Log::warning("Report ID {$reportId} has status {$processingStatus}");
                AmzReportsLog::where('report_id', $reportId)->update([
                    'report_status' => $processingStatus,
                ]);
                return false;
            }

            // get Document ID
            $documentId = $report['reportDocumentId'] ?? null;
            if (!$documentId) {
                Log::error("Missing reportDocumentId for report ID {$reportId} (Report Not Ready)");
                return false;
            }

            $document = $api->getReportDocument($documentId, $report['reportType'])->json();
            $url = $document['url'] ?? null;
            $compression = $document['compressionAlgorithm'] ?? null;

            if (!$url) {
                Log::error("Signed URL missing in report document for ID {$reportId}");
                return false;
            }

            $reportContent = file_get_contents($url);
            if ($reportContent === false) {
                Log::error("Failed to download report content for ID {$reportId}");
                return false;
            }

            if ($compression === 'GZIP') {
                $reportContent = gzdecode($reportContent);
                if ($reportContent === false) {
                    Log::error("GZIP decoding failed for report ID {$reportId}");
                    return false;
                }
            }

            $filename = now('UTC')->format('Y-m-d') . "_report_{$reportId}.txt";
            $storagePath = "api/reports/{$filename}";
            Storage::disk('public')->put($storagePath, $reportContent);

            AmzReportsLog::where('report_id', $reportId)->update([
                'report_document_id' => $documentId,
            ]);

            return $reportId;
        } catch (\Throwable $e) {
            Log::error("SP API Report Error for ID {$reportId}: " . $e->getMessage());
            return false;
        }
    }
}
