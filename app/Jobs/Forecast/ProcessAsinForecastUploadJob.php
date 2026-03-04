<?php

namespace App\Jobs\Forecast;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessAsinForecastUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $rows,
        public int $userId,
        public int $forecastId
    ) {}

    public function handle(): void
    {
        collect($this->rows)
            ->chunk(100)
            ->each(function ($chunk) {
                DB::transaction(function () use ($chunk) {
                    foreach ($chunk as $row) {

                        // Normalize ASIN
                        $asin = strtolower(trim($row['asin'] ?? ''));

                        if ($asin === '') {
                            continue;
                        }

                        // Lock row for concurrent safety
                        $snapshot = DB::table('order_forecast_snapshot_asins')
                            ->where('product_asin', $asin)
                            ->where('order_forecast_id', $this->forecastId)
                            ->lockForUpdate()
                            ->first();

                        if (!$snapshot) {
                            continue;
                        }

                        // Decode existing month values
                        $existingValues = json_decode(
                            $snapshot->sold_values_by_month ?? '{}',
                            true
                        );

                        if (!is_array($existingValues)) {
                            $existingValues = [];
                        }

                        // Process month columns
                        foreach ($row as $key => $value) {

                            if ($key === 'asin' || $value === null || $value === '') {
                                continue;
                            }

                            $month = $this->normalizeMonthKey((string) $key);

                            if (!$month) {
                                continue; // invalid header
                            }

                            $existingValues[$month] = (int) $value;
                        }

                        // Persist merged JSON
                        DB::table('order_forecast_snapshot_asins')
                            ->where('id', $snapshot->id)
                            ->update([
                                'sold_values_by_month' => json_encode($existingValues),
                                'updated_at' => now(),
                            ]);
                    }
                });
            });
    }

    /**
     * Normalize various month header formats to YYYY-MM
     */
    private function normalizeMonthKey(string $key): ?string
    {
        $key = strtolower(trim($key));

        // 2026_01, 2026-1
        if (preg_match('/^(20\d{2})[_-](0?[1-9]|1[0-2])$/', $key, $m)) {
            return sprintf('%04d-%02d', $m[1], $m[2]);
        }

        // jan_2026, jan-2026, jan 2026
        if (preg_match('/^(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[\s_-]*(20\d{2})$/', $key, $m)) {
            $months = [
                'jan' => '01',
                'feb' => '02',
                'mar' => '03',
                'apr' => '04',
                'may' => '05',
                'jun' => '06',
                'jul' => '07',
                'aug' => '08',
                'sep' => '09',
                'oct' => '10',
                'nov' => '11',
                'dec' => '12',
            ];

            return $m[2] . '-' . $months[$m[1]];
        }

        return null;
    }
}
