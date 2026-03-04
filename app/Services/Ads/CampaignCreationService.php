<?php

declare(strict_types=1);

namespace App\Services\Ads;

use Carbon\Carbon;
use RuntimeException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class CampaignCreationService
{
    /**
     * Generate campaigns payload (name + meta) with evenly distributed budget.
     *
     * @param  array{
     *   asin: string,
     *   sku: array<int,string>,
     *   campaignType: string,     // SP|SB|SD
     *   targetingType: string,    // AUTO|MANUAL
     *   matchType: string,        // BROAD|PHRASE|EXACT
     *   campaignState: string,    // ENABLED|PAUSED
     *   country: string,
     *   campaignCount: int,
     *   totalBudget: float|int,
     *   source?: string,          // default SYSTEM
     *   marketTz?: string,        // default config('timezone.market', 'America/Los_Angeles')
     *   pstDate?: string          // optional override (d-m-Y)
     * } $data
     *
     * @return array{
     *   pstDate: string,
     *   campaigns: array<int,array<string,mixed>>
     * }
     */
    public function generate(array $data): array
    {
        $asin         = (string) ($data['asin'] ?? '');
        $sku          = (array) ($data['sku'] ?? []);
        $type         = strtoupper((string) ($data['campaignType'] ?? 'SP'));
        $targeting    = strtoupper((string) ($data['targetingType'] ?? 'AUTO'));
        $match        = strtoupper((string) ($data['matchType'] ?? 'BROAD'));
        $state        = strtoupper((string) ($data['campaignState'] ?? 'ENABLED'));
        $country      = (string) ($data['country'] ?? 'US');
        $count        = (int) ($data['campaignCount'] ?? 1);
        $total        = (float) ($data['totalBudget'] ?? 0.0);
        $source       = strtoupper((string) ($data['source'] ?? 'SYSTEM'));
        $marketTz     = (string) ($data['marketTz'] ?? config('timezone.market', 'America/Los_Angeles'));
        $pstDateInput = $data['pstDate'] ?? null;

        $pstDate = is_string($pstDateInput) && $pstDateInput !== ''
            ? $pstDateInput
            : Carbon::now($marketTz)->format('d-m-Y');

        if ($count < 1) {
            $count = 1;
        }

        // Budget split
        $per = $count > 0 ? round($total / $count, 2) : 0.00;

        $items = [];
        $baseCmp = $this->randomCmpBase();

        for ($i = 0; $i < $count; $i++) {
            $cmpNumber = ($baseCmp + $i) % 10000;
            $cmp = str_pad((string) $cmpNumber, 4, '0', STR_PAD_LEFT);

            $name = "{$asin}_{$type}_{$targeting}_{$match}_{$source}_{$pstDate}_CMP_{$cmp}";

            $items[] = [
                'name'      => $name,
                'country'   => $country,
                'budget'    => $per,
                'sku'       => array_values($sku),
                'asin'      => $asin,
                'state'     => $state,
                'type'      => $type,
                'targeting' => $targeting,
                'match'     => $match,
                'date'      => $pstDate,
                'cmp'       => $cmp,
            ];
        }

        // Fix rounding diff on last campaign
        $sum  = array_sum(array_map(static fn($r) => (float) ($r['budget'] ?? 0), $items));
        $diff = round($total - $sum, 2);

        if ($diff !== 0.0 && $count > 0) {
            $items[$count - 1]['budget'] = round(((float) $items[$count - 1]['budget']) + $diff, 2);
        }

        return [
            'pstDate'   => $pstDate,
            'campaigns' => $items,
        ];
    }

    private function randomCmpBase(): int
    {
        return random_int(0, 9999);
    }




    /**
     * Build the campaigns JSON payload that is stored in CampaignDraft.campaigns
     * (adds: mode, keywords, targets)
     *
     * @param  array<int, array<string, mixed>> $generatedCampaigns
     * @param  array<int, string|null>          $rowMode
     * @param  array<int, array<int, array<string, mixed>>> $keywordDraft
     * @param  array<int, array<int, array<string, mixed>>> $targetDraft
     * @return array<int, array<string, mixed>>
     */
    public function buildCampaignsPayload(
        array $generatedCampaigns,
        array $rowMode,
        array $keywordDraft,
        array $targetDraft
    ): array {
        $payloadCampaigns = $generatedCampaigns;

        foreach ($payloadCampaigns as $i => &$row) {
            $mode = $rowMode[$i] ?? null;

            $row['mode'] = $mode;

            $row['keywords'] = ($mode === 'keyword')
                ? ($keywordDraft[$i] ?? [])
                : false;

            $row['targets'] = ($mode === 'target')
                ? $this->mapTargetsToApiShape($targetDraft[$i] ?? [])
                : false;
        }
        unset($row);

        return $payloadCampaigns;
    }

    /**
     * @param  array<int, array{type?:string,value?:string,bid?:mixed,state?:string}> $rows
     * @return array<int, array<string, mixed>>
     */
    private function mapTargetsToApiShape(array $rows): array
    {
        return array_map(static function (array $t): array {
            return [
                'adGroupId'      => null,
                'campaignId'     => null,
                'bid'            => (float) ($t['bid'] ?? 0),
                'expressionType' => 'MANUAL',
                'state'          => $t['state'] ?? 'ENABLED',
                'expression'     => [
                    [
                        'type'  => $t['type'] ?? 'ASIN_SAME_AS',
                        'value' => $t['value'] ?? '',
                    ],
                ],
            ];
        }, $rows);
    }

    /**
     * Hydrate editor arrays (rowMode/keywordDraft/targetDraft) from stored campaigns.
     *
     * @param  array<int, array<string,mixed>> $campaigns
     * @return array{rowMode:array<int,string|null>, keywordDraft:array<int,array>, targetDraft:array<int,array>}
     */
    public function hydrateDraftEditor(array $campaigns): array
    {
        $rowMode = [];
        $keywordDraft = [];
        $targetDraft = [];

        foreach ($campaigns as $i => $row) {
            $mode = $row['mode'] ?? null;
            $rowMode[$i] = in_array($mode, ['keyword', 'target'], true) ? $mode : null;

            $kw = $row['keywords'] ?? false;
            $tar = $row['targets'] ?? false;

            // keywords are stored in UI shape already
            $keywordDraft[$i] = is_array($kw) ? $kw : [];

            // targets can be stored in API shape; we keep as-is for editor
            $targetDraft[$i] = is_array($tar) ? $tar : [];
        }

        return [
            'rowMode'      => $rowMode,
            'keywordDraft' => $keywordDraft,
            'targetDraft'  => $targetDraft,
        ];
    }

    /**
     * Merge editor values back into campaigns payload for saving to draft.
     * Keeps stored shape: mode + keywords|false + targets|false
     *
     * @param  array<int, array<string,mixed>> $campaigns
     * @param  array<int, string|null> $rowMode
     * @param  array<int, array<int, array<string,mixed>>> $keywordDraft
     * @param  array<int, array<int, array<string,mixed>>> $targetDraftApiStyle
     * @return array<int, array<string,mixed>>
     */
    public function mergeDraftEditorToCampaigns(
        array $campaigns,
        array $rowMode,
        array $keywordDraft,
        array $targetDraftApiStyle
    ): array {
        $payload = $campaigns;

        foreach ($payload as $i => &$row) {
            $mode = $rowMode[$i] ?? null;
            $row['mode'] = in_array($mode, ['keyword', 'target'], true) ? $mode : null;

            $row['keywords'] = ($row['mode'] === 'keyword')
                ? ($keywordDraft[$i] ?? [])
                : false;

            // For draft editor you already store targets in API-style.
            // So do NOT remap here; store as-is.
            $row['targets'] = ($row['mode'] === 'target')
                ? ($targetDraftApiStyle[$i] ?? [])
                : false;
        }
        unset($row);

        return $payload;
    }

    /**
     * Expression list for Blade dropdown (optional; avoids repeating in blade)
     *
     * @return array<int,string>
     */
    public function expressionTypes(): array
    {
        return [
            "ASIN_AGE_RANGE_SAME_AS",
            "ASIN_BRAND_SAME_AS",
            "ASIN_CATEGORY_SAME_AS",
            "ASIN_EXPANDED_FROM",
            "ASIN_GENRE_SAME_AS",
            "ASIN_IS_PRIME_SHIPPING_ELIGIBLE",
            "ASIN_PRICE_BETWEEN",
            "ASIN_PRICE_GREATER_THAN",
            "ASIN_PRICE_LESS_THAN",
            "ASIN_REVIEW_RATING_BETWEEN",
            "ASIN_REVIEW_RATING_GREATER_THAN",
            "ASIN_REVIEW_RATING_LESS_THAN",
            "ASIN_SAME_AS",
            "KEYWORD_GROUP_SAME_AS",
        ];
    }

    public function importKeywordRows(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if (in_array($ext, ['csv', 'txt'], true)) {
            return $this->parseCsvKeywords($file);
        }

        if ($ext === 'xlsx') {
            return $this->parseXlsxKeywords($file);
        }

        throw new \RuntimeException('Unsupported file type.');
    }
    private function parseXlsxKeywords(UploadedFile $file): array
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
        } catch (\Throwable $e) {
            throw new \RuntimeException('Invalid Excel file.');
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows  = [];
        $header = null;

        foreach ($sheet->toArray(null, true, true, true) as $row) {
            if (!$header) {
                $header = array_map(
                    fn($h) => strtolower(trim((string) $h)),
                    $row
                );
                continue;
            }

            $mapped = [];
            foreach ($header as $col => $key) {
                $mapped[$key] = $row[$col] ?? null;
            }

            $keyword = trim((string) ($mapped['keyword'] ?? ''));
            if ($keyword === '') {
                continue;
            }

            $rows[] = [
                'text'  => $keyword,
                'bid'   => isset($mapped['bid']) ? (float) $mapped['bid'] : null,
                'match' => strtoupper($mapped['match'] ?? 'BROAD'),
            ];
        }

        return $this->normalizeKeywordRows($rows);
    }

    private function parseCsvKeywords(UploadedFile $file): array
    {
        $rows = [];
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            throw new \RuntimeException('Unable to read CSV file.');
        }

        $header = null;

        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            if (!$header) {
                $header = array_map(
                    fn($h) => strtolower(trim($h)),
                    $data
                );
                continue;
            }

            $row = array_combine($header, $data);
            if (!$row) {
                continue;
            }

            $keyword = trim((string) ($row['keyword'] ?? ''));
            if ($keyword === '') {
                continue;
            }

            $rows[] = [
                'text'  => $keyword,
                'bid'   => isset($row['bid']) ? (float) $row['bid'] : null,
                'match' => strtoupper($row['match'] ?? 'BROAD'),
            ];
        }

        fclose($handle);

        return $this->normalizeKeywordRows($rows);
    }

    private function normalizeKeywordRows(array $rows): array
    {
        $allowedMatches = ['BROAD', 'PHRASE', 'EXACT'];

        return collect($rows)
            ->map(function (array $r) use ($allowedMatches) {
                return [
                    'text'  => trim(preg_replace('/\s+/', ' ', $r['text'])),
                    'bid'   => isset($r['bid']) && $r['bid'] > 0 ? (float) $r['bid'] : null,
                    'match' => in_array($r['match'], $allowedMatches, true)
                        ? $r['match']
                        : 'BROAD',
                ];
            })
            ->filter(fn($r) => $r['text'] !== '')
            ->unique('text')
            ->values()
            ->all();
    }


    public function importKeywordsForSingleCampaign(
        UploadedFile $file,
        int $limit = 100
    ): array {
        $rows = $this->importKeywordRows($file);

        if (empty($rows)) {
            throw new RuntimeException('No valid keyword rows found. Use the sample format.');
        }

        // enforce max rows (your UI is /100)
        return array_slice($rows, 0, max(1, $limit));
    }

    /**
     * Apply imported keyword rows to only ONE campaign index.
     * - forces keyword mode
     * - clears targets for that campaign
     *
     * @param  int $index
     * @param  array<int, array{text:string,bid:float,match:string}> $rows
     * @param  array<int, string|null> $rowMode
     * @param  array<int, array<int, array<string,mixed>>> $keywordDraft
     * @param  array<int, array<int, array<string,mixed>>> $targetDraft
     * @return array{rowMode:array, keywordDraft:array, targetDraft:array}
     */
    public function applyKeywordsToCampaignIndex(
        int $index,
        array $rows,
        array $rowMode,
        array $keywordDraft,
        array $targetDraft
    ): array {
        $rowMode[$index] = 'keyword';
        $keywordDraft[$index] = $rows;
        $targetDraft[$index] = []; // clear targets

        return [
            'rowMode'      => $rowMode,
            'keywordDraft' => $keywordDraft,
            'targetDraft'  => $targetDraft,
        ];
    }

    /**
     * Apply imported keyword rows to ALL campaigns.
     * - forces keyword mode for each campaign
     * - replaces keywords for each campaign
     * - clears targets for each campaign
     *
     * @param  array<int, array{text:string,bid:float|null,match:string}> $rows
     * @param  array<int, string|null> $rowMode
     * @param  array<int, array<int, array<string,mixed>>> $keywordDraft
     * @param  array<int, array<int, array<string,mixed>>> $targetDraft
     * @param  array<int, array<string,mixed>> $generatedCampaigns
     * @return array{rowMode:array, keywordDraft:array, targetDraft:array}
     */
    public function applyKeywordsToAllCampaigns(
        array $rows,
        array $rowMode,
        array $keywordDraft,
        array $targetDraft,
        array $generatedCampaigns
    ): array {
        foreach ($generatedCampaigns as $i => $_row) {
            $rowMode[$i] = 'keyword';
            $keywordDraft[$i] = $rows;   // replace existing
            $targetDraft[$i] = [];       // clear targets
        }

        return [
            'rowMode'      => $rowMode,
            'keywordDraft' => $keywordDraft,
            'targetDraft'  => $targetDraft,
        ];
    }
}
