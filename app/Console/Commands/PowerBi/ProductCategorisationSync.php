<?php

namespace App\Console\Commands\PowerBi;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductCategorisationSync extends Command
{
    protected $signature = 'app:product-categorisation-sync';
    protected $description = 'Sync product_categorisation from powerbi DB into local product_categorisations table.';

    private const CHUNK_SIZE = 500;
    private const RETRIES = 3;
    private const RETRY_SLEEP_SECONDS = 2;

    public function handle(): int
    {
        try {
            $this->ensurePowerBiConnection();

            $processed = 0;

            DB::connection('powerbi')
                ->table('product_categorisation')
                ->select([
                    DB::raw('`Parent Short name` AS parent_short_name'),
                    DB::raw('`Child Short Name` AS child_short_name'),
                    DB::raw('`Parent ASIN` AS parent_asin'),
                    DB::raw('`Child ASIN` AS child_asin'),
                    DB::raw('`MarketPlace` AS marketplace'),
                    DB::raw('`Season` AS seasonal_type'),
                ])
                ->whereNotNull(DB::raw('`Child ASIN`'))
                ->orderBy(DB::raw('`Child ASIN`'))
                ->chunk(self::CHUNK_SIZE, function ($rows) use (&$processed) {
                    $payload = $this->mapRowsToPayload($rows);

                    if (empty($payload)) {
                        return;
                    }

                    $this->withRetry(function () use ($payload) {
                        DB::table('product_categorisations')->upsert(
                            $payload,
                            ['parent_asin', 'child_asin', 'marketplace'],
                            ['seasonal_type','parent_short_name', 'child_short_name', 'updated_at']
                        );
                    });

                    $processed += count($payload);
                });

            $this->info("Product categorisation sync completed. Rows processed: {$processed}");

            return self::SUCCESS;
        } catch (Throwable $e) {
            Log::error('ProductCategorisationSync failed', [
                'message' => $e->getMessage(),
            ]);

            $this->error('Sync failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }

    private function ensurePowerBiConnection(): void
    {
        $this->withRetry(function () {
            DB::connection('powerbi')->select('SELECT 1');
        });
    }

    private function mapRowsToPayload($rows): array
    {
        $now = now();
        $payload = [];

        foreach ($rows as $row) {
            $payload[] = [
                'parent_short_name' => $row->parent_short_name ?? null,
                'child_short_name'  => $row->child_short_name ?? null,
                'parent_asin'       => $row->parent_asin ?? null,
                'child_asin'        => $row->child_asin ?? null,
                'marketplace'       => $row->marketplace ?? null,
                'seasonal_type'     => $row->seasonal_type ?? null,
                'created_at'        => $now,
                'updated_at'        => $now,
            ];
        }

        return $payload;
    }

    private function withRetry(callable $fn): void
    {
        $attempt = 1;

        while (true) {
            try {
                $fn();
                return;
            } catch (Throwable $e) {
                if ($attempt >= self::RETRIES) {
                    throw $e;
                }

                Log::warning('ProductCategorisationSync retry', [
                    'attempt' => $attempt,
                    'message' => $e->getMessage(),
                ]);

                sleep(self::RETRY_SLEEP_SECONDS);
                $attempt++;
            }
        }
    }
}
