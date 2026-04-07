<?php

declare(strict_types=1);

namespace App\Console\Commands\PowerBi;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SyncCompetitorRank360Bi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'powerbi:sync-competitor-rank-360-bi {--days=14 : Number of trailing days to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Competitor_Rank_360 data from PowerBI DB to competitor_rank_360_bi table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting synchronization of Competitor_Rank_360 data...');

        try {
            $daysToSync = (int) $this->option('days');
            $marketTz = config('timezone.market', config('app.timezone'));

            $sourceTable = 'Competitor_Rank_360';
            $targetTable = 'competitor_rank_360_bi';
            $chunkSize = 1000;

            if (! Schema::hasTable($targetTable)) {
                $this->error("Target table '{$targetTable}' does not exist in default database.");
                return self::FAILURE;
            }

            $sourceColumns = DB::connection('powerbi')
                ->getSchemaBuilder()
                ->getColumnListing($sourceTable);

            $requiredStaticColumns = ['id', 'ASIN', 'Keyword'];

            $missingStaticColumns = array_diff($requiredStaticColumns, $sourceColumns);

            if (! empty($missingStaticColumns)) {
                $this->error('Missing required source columns: ' . implode(', ', $missingStaticColumns));
                return self::FAILURE;
            }

            $totalSyncedDates = 0;

            for ($i = 0; $i <= $daysToSync; $i++) {
                $currentDate = Carbon::now($marketTz)->subDays($i);
                $sourceDateColumn = $currentDate->format('d-m-Y');
                $reportDate = $currentDate->format('Y-m-d');

                if (! in_array($sourceDateColumn, $sourceColumns, true)) {
                    $this->warn("Column '{$sourceDateColumn}' does not exist in source table. Skipping.");
                    continue;
                }

                $this->info("Syncing date: {$reportDate} (Column: {$sourceDateColumn})");

                $totalFetchedRows = 0;
                $totalProcessedRows = 0;
                $totalInsertedRows = 0;

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
                        &$totalInsertedRows
                    ): void {
                        $totalFetchedRows += $rows->count();

                        $upsertRows = [];

                        foreach ($rows as $row) {
                            $rowData = (array) $row;

                            $asin = $this->nullableString($rowData['ASIN'] ?? null);
                            $keyword = $this->nullableString($rowData['Keyword'] ?? null);
                            $rankValue = $this->nullableString($rowData[$sourceDateColumn] ?? null);

                            // Skip header row if it's duplicated in the data
                            if (strtolower((string) $asin) === 'asin' || strtolower((string) $keyword) === 'keyword') {
                                continue;
                            }

                            if ($asin === null && $keyword === null) {
                                continue;
                            }

                            $upsertRows[] = [
                                'asin'        => $asin,
                                'keyword'     => $keyword,
                                'rank_value'  => $rankValue,
                                'report_date' => $reportDate,
                                'created_at'  => now(),
                                'updated_at'  => now(),
                            ];

                            $totalProcessedRows++;
                        }

                        if (empty($upsertRows)) {
                            return;
                        }

                        DB::table($targetTable)->upsert(
                            $upsertRows,
                            ['asin', 'keyword', 'report_date'],
                            ['rank_value', 'updated_at']
                        );

                        $totalInsertedRows += count($upsertRows);
                    }, 'id');

                $this->line("  => Fetched: {$totalFetchedRows}, Processed: {$totalProcessedRows}, Upserted: {$totalInsertedRows}");
                Log::info("Competitor_Rank_360 synced for {$reportDate}", [
                    'source_date_column' => $sourceDateColumn,
                    'total_upserted_rows' => $totalInsertedRows,
                ]);

                $totalSyncedDates++;
            }

            $this->info("Successfully synced {$totalSyncedDates} dates.");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::error('Failed to sync Competitor_Rank_360 data.', [
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
}
