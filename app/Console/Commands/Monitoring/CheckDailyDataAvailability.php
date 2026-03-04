<?php

namespace App\Console\Commands\Monitoring;

use App\Models\AmzKeywordRecommendation;
use App\Models\CampaignRecommendations;
use App\Models\DailySales;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CheckDailyDataAvailability extends Command
{
    protected $signature = 'app:check-daily-data-availability';

    protected $description = 'Check if key daily datasets exist for current IST day and send developer warning email if missing';

    public function handle(): int
    {
        $mailTimezone = config('data_monitoring.timezone', 'Asia/Kolkata');
        $marketTimezone = config('timezone.market', 'America/Los_Angeles');
        $expectedDate = Carbon::now($marketTimezone)->subDay()->toDateString();
        $recipients = config('data_monitoring.recipients', []);

        $rows = [];
        $missing = [];

        try {
            $datasets = [
                [
                    'name' => 'CampaignRecommendations',
                    'column' => 'report_week',
                    'model' => CampaignRecommendations::query(),
                ],
                [
                    'name' => 'AmzKeywordRecommendation',
                    'column' => 'date',
                    'model' => AmzKeywordRecommendation::query(),
                ],
                [
                    'name' => 'DailySales',
                    'column' => 'sale_date',
                    'model' => DailySales::query(),
                ],
            ];

            foreach ($datasets as $dataset) {
                $latestDate = $dataset['model']->max($dataset['column']);
                $isAvailable = !empty($latestDate) && $latestDate >= $expectedDate;

                if (!$isAvailable) {
                    $missing[] = $dataset['name'];
                }

                $rows[] = [
                    $dataset['name'],
                    $dataset['column'],
                    $expectedDate,
                    $latestDate ?: 'N/A',
                    $isAvailable ? 'AVAILABLE' : 'MISSING',
                ];
            }
        } catch (Throwable $e) {
            Log::error('Daily data availability check failed to execute.', [
                'expected_date' => $expectedDate,
                'market_timezone' => $marketTimezone,
                'error' => $e->getMessage(),
            ]);

            if (!empty($recipients)) {
                $subject = "[Error] Daily data availability check failed | {$expectedDate}";
                $body = "Daily data availability check failed to run.\n\n"
                    . "Expected report date: {$expectedDate} ({$marketTimezone})\n"
                    . "Error: {$e->getMessage()}\n";

                Mail::raw($body, function ($message) use ($recipients, $subject) {
                    $message->to($recipients)->subject($subject);
                });
            }

            $this->error('Daily data availability check failed. See logs for details.');
            return self::FAILURE;
        }

        $this->table(['Dataset', 'Date Column', 'Expected Date', 'Latest Date In DB', 'Status'], $rows);

        if (empty($missing)) {
            $this->info("Daily data check passed. Expected report date: {$expectedDate} ({$marketTimezone}).");
            return self::SUCCESS;
        }

        if (empty($recipients)) {
            Log::warning('Daily data check found missing datasets, but no recipients configured.', [
                'expected_date' => $expectedDate,
                'market_timezone' => $marketTimezone,
                'missing' => $missing,
            ]);

            return self::FAILURE;
        }

        $missingSummary = implode(', ', $missing);
        $subject = "[Warning] Missing daily data: {$missingSummary} | Expected {$expectedDate}";

        $tableRows = '';
        foreach ($rows as $row) {
            [$name, $column, $expDate, $latestDate, $status] = $row;
            $statusColor = $status === 'AVAILABLE' ? '#16a34a' : '#dc2626';
            $tableRows .= "<tr>"
                . "<td style='padding:8px;border:1px solid #e5e7eb;'>{$name}</td>"
                . "<td style='padding:8px;border:1px solid #e5e7eb;'>{$column}</td>"
                . "<td style='padding:8px;border:1px solid #e5e7eb;'>{$expDate}</td>"
                . "<td style='padding:8px;border:1px solid #e5e7eb;'>{$latestDate}</td>"
                . "<td style='padding:8px;border:1px solid #e5e7eb;color:{$statusColor};font-weight:700;'>{$status}</td>"
                . "</tr>";
        }

        $mailRunTime = Carbon::now($mailTimezone)->format('Y-m-d h:i A');

        $htmlBody = "
            <div style='font-family:Arial,sans-serif;color:#111827;'>
                <h2 style='margin:0 0 12px;'>Daily Data Availability Alert</h2>
                <p style='margin:0 0 8px;'><strong>Run time:</strong> {$mailRunTime} ({$mailTimezone})</p>
                <p style='margin:0 0 8px;'><strong>Expected report date:</strong> {$expectedDate} ({$marketTimezone})</p>
                <p style='margin:0 0 12px;'><strong>Missing now:</strong> {$missingSummary}</p>
                <table style='border-collapse:collapse;width:100%;font-size:13px;'>
                    <thead>
                        <tr style='background:#f3f4f6;'>
                            <th style='padding:8px;border:1px solid #e5e7eb;text-align:left;'>Dataset</th>
                            <th style='padding:8px;border:1px solid #e5e7eb;text-align:left;'>Date Column</th>
                            <th style='padding:8px;border:1px solid #e5e7eb;text-align:left;'>Expected Date</th>
                            <th style='padding:8px;border:1px solid #e5e7eb;text-align:left;'>Latest Date In DB</th>
                            <th style='padding:8px;border:1px solid #e5e7eb;text-align:left;'>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$tableRows}
                    </tbody>
                </table>
                <p style='margin:12px 0 0;'>Warning to developers: data is not available yet. Please check possible job failures, API/report delays, or processing errors.</p>
            </div>
        ";

        Mail::html($htmlBody, function ($message) use ($recipients, $subject) {
            $message->to($recipients)->subject($subject);
        });

        Log::warning('Daily data availability warning email sent.', [
            'expected_date' => $expectedDate,
            'market_timezone' => $marketTimezone,
            'mail_timezone' => $mailTimezone,
            'recipients' => $recipients,
            'missing' => $missing,
            'rows' => $rows,
        ]);

        $this->warn('Missing data detected and warning email sent.');

        return self::SUCCESS;
    }
}
