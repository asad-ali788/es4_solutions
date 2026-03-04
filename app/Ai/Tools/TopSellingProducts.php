<?php

namespace App\Ai\Tools;

use App\Services\Ai\AiChatBotServices;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

final class TopSellingProducts implements Tool
{
    /**
     * Internal timezone for "yesterday / historical days" logic.
     * Keep timezone internal so the agent can't vary it.
     */
    private string $marketTz = 'America/Los_Angeles';

    public function description(): Stringable|string
    {
        return implode(' ', [
            'Fetch top-selling products for a historical report date (yesterday or earlier) using the configured market timezone.',
            'IMPORTANT: This tool does NOT support the current day (today) because results would be partial/volatile.',
            'If the user asks a plain question like "top selling products", this tool defaults to: date=yesterday, marketplace_id=ALL allowed, limit=10.',
            'Optional inputs: date (YYYY-MM-DD), marketplace_id (Amazon.com | Amazon.ca | Amazon.com.mx), limit (1..50).',
            'Returns JSON: { items, meta } or { error, items: [], meta }.',
            'Each item includes: asin (child_asin), product_name (child_short_name if available), sale_date, total_units, total_cost (spend), total_revenue.',
        ]);
    }

    public function handle(Request $request): Stringable|string
    {
        try {
            /** @var AiChatBotServices $service */
            $service = app(AiChatBotServices::class);

            $now           = Carbon::now($this->marketTz);
            $todayDate     = $now->copy()->toDateString();
            $yesterdayDate = $now->copy()->subDay()->toDateString();

            // Defaults for plain questions (no params)
            $date            = $yesterdayDate;
            $marketplaceId   = null;
            $limit           = 10;
            $usedDefaultDate = true;

            // Override if AI/user supplied inputs exist
            if (isset($request['date']) && is_string($request['date']) && trim($request['date']) !== '') {
                $date = trim($request['date']);
                $usedDefaultDate = false;
            }

            if (isset($request['marketplace_id']) && is_string($request['marketplace_id']) && trim($request['marketplace_id']) !== '') {
                $marketplaceId = trim($request['marketplace_id']);
            }

            if (isset($request['limit'])) {
                $limit = (int) $request['limit'];
            }

            // Guardrails
            if ($limit < 1) {
                $limit = 1;
            }

            if ($limit > 50) {
                $limit = 50;
            }

            // Validate date format strictly (YYYY-MM-DD).
            $parsed = Carbon::createFromFormat('Y-m-d', $date, $this->marketTz);
            if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
                throw new \InvalidArgumentException('Invalid date. Expected format: YYYY-MM-DD.');
            }

            // Enforce "yesterday or earlier" (no current-day).
            $today = $now->copy()->startOfDay();
            if ($parsed->startOfDay()->greaterThanOrEqualTo($today)) {
                throw new \InvalidArgumentException(
                    "Top-selling is available only for yesterday and previous days (not for the current day). Today is {$todayDate}; latest complete date is {$yesterdayDate}."
                );
            }

            $items = $service->topSellingProducts(
                date: $date,
                marketplaceId: $marketplaceId,
                limit: $limit,
                marketTz: $this->marketTz
            );

            // Ensure plain array output.
            if ($items instanceof \Illuminate\Support\Collection) {
                $items = $items->values()->all();
            }

            return json_encode([
                'items' => $items,
                'meta' => [
                    'date'                 => $date,
                    'limit'                => $limit,
                    'marketplace_id'       => $marketplaceId,     // null means ALL allowed marketplaces
                    'timezone'             => $this->marketTz,
                    'today_date'           => $todayDate,
                    'latest_complete_date' => $yesterdayDate,
                    'used_default_date'    => $usedDefaultDate,
                    'defaults'             => [
                        'date'                  => 'yesterday',
                        'resolved_default_date' => $yesterdayDate,
                        'marketplace_id'        => 'ALL allowed marketplaces',
                        'limit'                 => 10,
                    ],
                    'note' => 'Historical results only (yesterday/previous days). Current day is not supported.',
                ],
            ], JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            $now = Carbon::now($this->marketTz);
            return json_encode([
                'error' => $e->getMessage(),
                'items' => [],
                'meta'  => [
                    'timezone'             => $this->marketTz,
                    'today_date'           => $now->copy()->toDateString(),
                    'latest_complete_date' => $now->copy()->subDay()->toDateString(),
                    'note'                 => 'Historical results only (yesterday/previous days). Current day is not supported.',
                ],
            ], JSON_THROW_ON_ERROR);
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'date' => $schema
                ->string()
                ->description('Report date in YYYY-MM-DD. Must be yesterday or earlier. If omitted, defaults to yesterday.')
                ->nullable(),

            'marketplace_id' => $schema
                ->string()
                ->description('Optional marketplace filter. Must be one of: Amazon.com, Amazon.ca, Amazon.com.mx. If omitted, uses all allowed marketplaces.')
                ->nullable(),

            'limit' => $schema
                ->integer()
                ->min(1)
                ->max(50)
                ->description('Number of products to return (1..50). If omitted, defaults to 10.')
                ->nullable(),
        ];
    }
}
