<?php

declare(strict_types=1);

namespace App\Console\Commands\PowerBi;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SyncKeywordRankReport360Bi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'powerbi:sync-keyword-rank-report-360-bi';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Rank_Report_360 data from PowerBI DB to keyword_rank_report_360_bi table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting synchronization of Rank_Report_360 data...');

        try {
            $marketTz = config('timezone.market', config('app.timezone'));

            $sourceDateColumn = Carbon::now($marketTz)
                ->subDays()
                ->format('d-m-Y');

            $reportDate = Carbon::createFromFormat('d-m-Y', $sourceDateColumn)->format('Y-m-d');

            $sourceTable = 'Rank_Report_360';
            $targetTable = 'keyword_rank_report_360_bi';
            $chunkSize = 1000;
            $sourceColumns = DB::connection('powerbi')
                ->getSchemaBuilder()
                ->getColumnListing($sourceTable);

            if (! in_array($sourceDateColumn, $sourceColumns, true)) {
                $this->warn("Column '{$sourceDateColumn}' does not exist in source table.");
                return self::SUCCESS;
            }

            if (! Schema::hasTable($targetTable)) {
                $this->error("Target table '{$targetTable}' does not exist in default database.");
                return self::FAILURE;
            }

            $requiredStaticColumns = [
                'id',
                'Product',
                'Child',
                'ASIN',
                'Keywords',
                'Match Type',
                'SV',
            ];

            $missingStaticColumns = array_diff($requiredStaticColumns, $sourceColumns);

            if (! empty($missingStaticColumns)) {
                $this->error('Missing required source columns: ' . implode(', ', $missingStaticColumns));
                return self::FAILURE;
            }

            $totalFetchedRows = 0;
            $totalProcessedRows = 0;
            $totalInsertedRows = 0;
            $totalHeaderRowsSkipped = 0;
            $totalEmptyRowsSkipped = 0;
            $printedSampleRow = false;

            DB::connection('powerbi')
                ->table($sourceTable)
                ->select(array_merge($requiredStaticColumns, [$sourceDateColumn]))
                ->orderBy('id')
                ->chunkById($chunkSize, function ($rows) use (
                    $sourceDateColumn,
                    $reportDate,
                    $targetTable,
                    &$totalFetchedRows,
                    &$totalProcessedRows,
                    &$totalInsertedRows,
                    &$totalHeaderRowsSkipped,
                    &$totalEmptyRowsSkipped,
                    &$printedSampleRow
                ): void {
                    $totalFetchedRows += $rows->count();

                    $upsertRows = [];

                    foreach ($rows as $row) {
                        $rowData = (array) $row;

                        if ($this->isHeaderRow($rowData, $sourceDateColumn)) {
                            $totalHeaderRowsSkipped++;
                            continue;
                        }

                        if ($this->isEmptyDataRow($rowData, $sourceDateColumn)) {
                            $totalEmptyRowsSkipped++;
                            continue;
                        }

                        $asin = $this->nullableString($rowData['ASIN'] ?? null);
                        $keyword = $this->nullableString($rowData['Keywords'] ?? null);
                        $matchType = $this->nullableString($rowData['Match Type'] ?? null);

                        if ($asin === null && $keyword === null) {
                            $totalEmptyRowsSkipped++;
                            continue;
                        }

                        $upsertRows[] = [
                            'product'       => $this->nullableString($rowData['Product'] ?? null),
                            'child'         => $this->nullableString($rowData['Child'] ?? null),
                            'asin'          => $asin,
                            'keyword'       => $keyword,
                            'match_type'    => $matchType,
                            'search_volume' => $this->nullableString($rowData['SV'] ?? null),
                            'report_date'   => $reportDate,
                            'rank_value'    => $this->nullableString($rowData[$sourceDateColumn] ?? null),
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ];

                        $totalProcessedRows++;
                    }

                    if (! $printedSampleRow && ! empty($upsertRows)) {
                        $this->line('First valid transformed row:');
                        // dump($upsertRows[0]);
                        $printedSampleRow = true;
                    }

                    if (empty($upsertRows)) {
                        $this->warn('Current chunk had no valid rows after filtering.');
                        return;
                    }

                    DB::table($targetTable)->upsert(
                        $upsertRows,
                        ['asin', 'keyword', 'match_type', 'report_date'],
                        ['product', 'child', 'search_volume', 'rank_value', 'updated_at']
                    );

                    $totalInsertedRows += count($upsertRows);

                    unset($upsertRows);
                }, 'id');

            $savedCount = DB::table($targetTable)
                ->whereDate('report_date', $reportDate)
                ->count();
            Log::info('Rank_Report_360 data synchronized successfully.', [
                'source_date_column' => $sourceDateColumn,
                'report_date' => $reportDate,
                'total_fetched_rows' => $totalFetchedRows,
                'total_valid_processed_rows' => $totalProcessedRows,
                'total_upserted_rows' => $totalInsertedRows,
                'total_header_rows_skipped' => $totalHeaderRowsSkipped,
                'total_empty_rows_skipped' => $totalEmptyRowsSkipped,
                'saved_count_for_date' => $savedCount,
            ]);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::error('Failed to sync Rank_Report_360 data.', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $this->error('Synchronization failed: ' . $exception->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Normalize value to nullable trimmed string.
     */
    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Detect whether the given row is actually a duplicated header row.
     */
    private function isHeaderRow(array $rowData, string $sourceDateColumn): bool
    {
        $product = strtolower((string) ($rowData['Product'] ?? ''));
        $child = strtolower((string) ($rowData['Child'] ?? ''));
        $asin = strtolower((string) ($rowData['ASIN'] ?? ''));
        $keyword = strtolower((string) ($rowData['Keywords'] ?? ''));
        $matchType = strtolower((string) ($rowData['Match Type'] ?? ''));
        $sv = strtolower((string) ($rowData['SV'] ?? ''));
        $rank = strtolower((string) ($rowData[$sourceDateColumn] ?? ''));

        return $product === 'product'
            && $child === 'child'
            && $asin === 'asin'
            && $keyword === 'keywords'
            && $matchType === 'match type'
            && in_array($sv, ['sv', 'search volume'], true)
            && $rank === 'rank';
    }

    /**
     * Detect completely empty / unusable rows.
     */
    private function isEmptyDataRow(array $rowData, string $sourceDateColumn): bool
    {
        $values = [
            $rowData['Product'] ?? null,
            $rowData['Child'] ?? null,
            $rowData['ASIN'] ?? null,
            $rowData['Keywords'] ?? null,
            $rowData['Match Type'] ?? null,
            $rowData['SV'] ?? null,
            $rowData[$sourceDateColumn] ?? null,
        ];

        foreach ($values as $value) {
            if ($this->nullableString($value) !== null) {
                return false;
            }
        }

        return true;
    }
}
