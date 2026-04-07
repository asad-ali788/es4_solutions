<?php

declare(strict_types=1);

namespace App\Ai\Tools\Lite;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

final class SpSearchTermSummaryTool extends BaseSqlTool
{
    private const TABLE_NAME = 'sp_search_term_summary_reports';

    public function name(): string
    {
        return 'sp_search_term_summary_sql';
    }

    public function description(): Stringable|string
    {
        return <<<TEXT
            PURPOSE: Advanced read-only MySQL query tool for Amazon Sponsored Products search term analysis.
            TABLES ALLOWED:
            1. `sp_search_term_summary_reports` (ST): Primary table for search term metrics.
               Columns: id, campaign_id, ad_group_id, keyword_id, country, date, keyword, search_term, impressions, clicks, cost, purchases_1d, sales_1d, keyword_bid, keyword_type, match_type, targeting, ad_keyword_status.
            2. `amz_ads_products` (P): Join via `campaign_id` to get `asin`.
            3. `amz_campaigns` (C): Join via `campaign_id` to get `campaign_name`.
            4. `product_categorisations` (PC): Join via `P.asin = PC.child_asin` to get `child_short_name` (product_name).

            BUSINESS LOGIC & ANALYSIS:
            - HIGH PERFORMING: Terms with `sales_1d > 0` or high conversion.
            - UNDER PERFORMING: Terms with high `cost` but `sales_1d = 0` (wasted spend).
            - MATCHING: `search_term = keyword`.
            - NOT MATCHING: `search_term != keyword`.
            - TRENDS: Use `SUM()` and `GROUP BY search_term`.
            
            RULES:
            - ALWAYS return the LATEST available data by default unless a time-series/trend is requested using date.
            - If you do not know the latest date, run a query to find the latest value: `SELECT MAX(date) FROM sp_search_term_summary_reports`.
            - To get the LATEST data once the date is known, use `WHERE date = 'YYYY-MM-DD'`.
            - To get ASIN or Campaign Name, use JOINs with the allowed tables above.
            - Only read-only SELECT queries are allowed.
            PARTIAL MATCHING: When filtering by `search_term`, `keyword`, or `child_short_name`, ALWAYS use the `LIKE` operator with wildcards (e.g., `search_term LIKE '%Accordion%'`) to ensure partial matches are caught (e.g., "Accordion" should match "Accordion Small" or "Accordion Large").
            CRITICAL RULE: DO NOT MAKE UP OR HALLUCINATE DATA.
            TEXT;
    }

    protected function allowedTables(): array
    {
        return [
            'sp_search_term_summary_reports',
            'amz_ads_products',
            'amz_campaigns',
            'product_categorisations',
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $sql = $request['sql'] ?? '';
        if (!is_string($sql)) {
            throw new InvalidArgumentException('The sql field is required and must be a string.');
        }

        $positionalBindings = $this->extractPositionalBindings($request);
        $namedBindings = $this->extractNamedBindings($request);

        try {
            $this->ensureBindingModeIsConsistent($sql, $positionalBindings, $namedBindings);

            $bindings = $namedBindings !== [] ? $namedBindings : $positionalBindings;
            $resolvedDate = $this->resolvePreferredReportDate();

            [$preparedSql, $preparedBindings] = $this->prepareSqlAndBindings(
                sql: $sql,
                bindings: $bindings,
                resolvedDate: $resolvedDate
            );

            // Re-inject the prepared SQL into the request so executeSql uses it.
            $request['sql'] = $preparedSql;

            return $this->executeSql($request, $this->allowedTables(), null, $preparedBindings);
        } catch (Throwable $e) {
            return json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'sql' => $schema
                ->string()
                ->description(
                    'REQUIRED. Read-only SQL query for sp_search_term_summary_reports. Prefer WHERE date = :report_date for daily queries. The tool does not modify SQL. This is used for the lightweight chat preview.'
                )
                ->required(),

            'export_sql' => $schema
                ->string()
                ->nullable()
                ->description('OPTIONAL. A detailed read-only MySQL SELECT query with all relevant columns for Excel export.'),

            'bindings' => $schema
                ->array()
                ->items(
                    $schema->string()->description('Optional positional binding value for ? placeholders.')
                )
                ->description(
                    'Optional positional bindings. Use only for ? placeholders.'
                ),

            'named_bindings' => $schema
                ->object()
                ->description(
                    'Optional named bindings for placeholders like :asin, :country, :campaign_id, :report_date.'
                ),

            'max_rows' => $schema
                ->integer()
                ->description('Optional max rows to return. Default 200. Hard cap 500.'),
        ];
    }

    private function prepareSqlAndBindings(string $sql, array $bindings, ?string $resolvedDate): array
    {
        if ($this->extractHardcodedDate($sql) !== null) {
            return [$sql, $bindings];
        }

        if (preg_match('/:report_date\b/i', $sql) === 1) {
            [$bindings] = $this->injectDateBindingIfMissing($bindings, 'report_date', $resolvedDate);
            return [$sql, $bindings];
        }

        if (preg_match('/:date\b/i', $sql) === 1) {
            [$bindings] = $this->injectDateBindingIfMissing($bindings, 'date', $resolvedDate);
            return [$sql, $bindings];
        }

        return [$sql, $bindings];
    }

    private function injectDateBindingIfMissing(array $bindings, string $placeholder, ?string $resolvedDate): array
    {
        if ($bindings !== [] && array_keys($bindings) === range(0, count($bindings) - 1)) {
             return [$bindings, null];
        }

        if (array_key_exists($placeholder, $bindings) && $bindings[$placeholder] !== null && $bindings[$placeholder] !== '') {
            return [$bindings, (string) $bindings[$placeholder]];
        }

        if ($resolvedDate !== null) {
            $bindings[$placeholder] = $resolvedDate;
        }

        return [$bindings, $resolvedDate];
    }

    private function extractHardcodedDate(string $sql): ?string
    {
        if (preg_match("/\bdate\s*=\s*'([0-9]{4}-[0-9]{2}-[0-9]{2})'/i", $sql, $matches) === 1) {
            return $matches[1];
        }
        return null;
    }

    private function resolvePreferredReportDate(): ?string
    {
        $marketTz = config('timezone.market') ?: config('app.timezone');
        $yesterday = CarbonImmutable::now($marketTz)->subDay()->toDateString();

        if (DB::table(self::TABLE_NAME)->whereDate('date', $yesterday)->exists()) {
            return $yesterday;
        }

        $latestDate = DB::table(self::TABLE_NAME)->max('date');
        return $latestDate !== null ? (string) $latestDate : null;
    }

    private function ensureBindingModeIsConsistent(string $sql, array $positional, array $named): void
    {
        if ($positional !== [] && $named !== []) {
            throw new InvalidArgumentException('Use either positional bindings or named bindings, not both.');
        }

        $usesNamed = preg_match('/:[a-zA-Z_][a-zA-Z0-9_]*/', $sql) === 1;
        $usesPositional = str_contains($sql, '?');

        if ($usesNamed && $positional !== []) {
            throw new InvalidArgumentException('SQL uses named placeholders, provide named_bindings.');
        }

        if ($usesPositional && $named !== []) {
            throw new InvalidArgumentException('SQL uses positional placeholders, provide bindings.');
        }
    }

    private function extractPositionalBindings(Request $request): array
    {
        $bindings = $request['bindings'] ?? [];
        return is_array($bindings) ? array_values(array_map([$this, 'normalizeBindingValue'], $bindings)) : [];
    }

    private function extractNamedBindings(Request $request): array
    {
        $bindings = $request['named_bindings'] ?? [];
        if (!is_array($bindings)) return [];
        $normalized = [];
        foreach ($bindings as $key => $value) {
            if (is_string($key)) $normalized[trim($key)] = $this->normalizeBindingValue($value);
        }
        return $normalized;
    }

    private function normalizeBindingValue(mixed $value): mixed
    {
        if (is_bool($value) || is_int($value) || is_float($value) || $value === null) return $value;
        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') return '';
            if (is_numeric($trimmed)) return str_contains($trimmed, '.') ? (float) $trimmed : (int) $trimmed;
            return $trimmed;
        }
        return (string) $value;
    }
}