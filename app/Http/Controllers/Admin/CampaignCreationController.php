<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Seller\SellingAdsItemService;
use Illuminate\Support\Facades\Log;
use App\Models\CampaignDraft;

class CampaignCreationController extends Controller
{
    public function __construct(
        protected SellingAdsItemService $sellingAdsItemService,
    ) {}

    public function autoCreateFromDraft(int $draft)
    {
        $draft = CampaignDraft::query()
            ->where('id', $draft)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $data = [
            'asin'           => (string) $draft->asin,
            'campaign_type'  => (string) $draft->campaign_type,
            'country'        => (string) $draft->country,
            'targeting_type' => strtoupper((string) ($draft->targeting_type ?: data_get($draft->campaigns, '0.targeting', 'AUTO'))),
            'match_type'     => strtoupper((string) data_get($draft->campaigns, '0.match', 'BROAD')),
            'pst_date'       => (string) data_get($draft->campaigns, '0.date', ''),
            'campaign_state' => strtoupper((string) data_get($draft->campaigns, '0.state', 'ENABLED')),
            'sku'            => $this->normalizeSkus(is_array($draft->sku) ? $draft->sku : []),

            'campaigns' => collect(is_array($draft->campaigns) ? $draft->campaigns : [])
                ->filter(fn($row) => is_array($row))
                ->map(fn($row) => [
                    'name'   => (string) data_get($row, 'name', ''),
                    'budget' => (float) data_get($row, 'budget', 0),
                ])
                ->values()
                ->all(),

            'total_budget' => (float) round(
                collect(is_array($draft->campaigns) ? $draft->campaigns : [])
                    ->sum(fn($row) => (float) data_get($row, 'budget', 0)),
                2
            ),
        ];

        if (empty($data['sku'])) {
            return back()->withErrors(['sku' => 'Please select at least 1 SKU.']);
        }

        if (empty($data['campaigns'])) {
            return back()->withErrors(['campaigns' => 'No campaigns found in this draft.']);
        }

        $profileId = $this->sellingAdsItemService->resolveProfileId($data['country']);

        $results = [
            'success' => [],
            'failed'  => [],
        ];

        $minDailyBudget = 1.00;

        // optional: mark draft processing (if you use status like in manual flow)
        $draft->update([
            'status' => 'processing',
            'error'  => null,
        ]);

        foreach ($data['campaigns'] as $index => $c) {
            $rowNo = $index + 1;

            $campaignId = null;
            $adGroupId  = null;
            $finalName  = $c['name'] ?? null;

            // collect created product ads so we can upsert once
            $createdAds = [];

            try {
                $budget = (float) ($c['budget'] ?? 0);

                Log::info('SP AUTO create flow started', [
                    'draft_id' => $draft->id,
                    'asin'     => $data['asin'],
                    'row'      => $rowNo,
                    'name'     => $c['name'] ?? null,
                    'budget'   => $budget,
                    'state'    => $data['campaign_state'],
                    'skus'     => $data['sku'],
                ]);

                // 1) Campaign
                $camp = $this->sellingAdsItemService->spCreateCampaignWithRetry(
                    $profileId,
                    $c['name'],
                    $budget,
                    [
                        'maxAttempts'    => 4,
                        'minDailyBudget' => $minDailyBudget,
                    ],
                    'AUTO',
                    $data['campaign_state']
                );

                if (!$camp['ok']) {
                    throw new \RuntimeException('Campaign: ' . implode(' | ', $camp['messages']));
                }

                $campaignId = $camp['id'];
                $finalName  = $camp['name_used'] ?? $c['name'];

                // 2) AdGroup
                $adGroupName = $data['asin'] . '_AdGroup_' . $rowNo;

                $ag = $this->sellingAdsItemService->spCreateAdGroup(
                    $profileId,
                    $campaignId,
                    $adGroupName,
                    0.10
                );

                if (!$ag['ok']) {
                    throw new \RuntimeException('AdGroup: ' . implode(' | ', $ag['messages']));
                }

                $adGroupId = $ag['id'];

                // 3) Product Ads for all SKUs (same campaign + adgroup)
                foreach ($data['sku'] as $sku) {
                    try {
                        $pa = $this->sellingAdsItemService->spCreateProductAd(
                            $profileId,
                            $campaignId,
                            $adGroupId,
                            $data['asin'],
                            $sku
                        );

                        if (!$pa['ok']) {
                            throw new \RuntimeException('ProductAd: ' . implode(' | ', $pa['messages']));
                        }

                        $createdAds[] = [
                            'row'        => $rowNo,
                            'sku'        => $sku,
                            'campaignId' => $campaignId,
                            'adGroupId'  => $adGroupId,
                            'adId'       => $pa['id'],
                            'name'       => $finalName,
                            'budget'     => $budget,
                        ];

                        $results['success'][] = end($createdAds);
                    } catch (\Throwable $skuErr) {
                        Log::error('SP AUTO product ad failed for SKU', [
                            'draft_id'   => $draft->id,
                            'asin'       => $data['asin'],
                            'row'        => $rowNo,
                            'sku'        => $sku,
                            'campaignId' => $campaignId,
                            'adGroupId'  => $adGroupId,
                            'error'      => $skuErr->getMessage(),
                        ]);

                        $results['failed'][] = [
                            'row'   => $rowNo,
                            'sku'   => $sku,
                            'name'  => $c['name'] ?? '-',
                            'error' => $skuErr->getMessage(),
                        ];
                    }
                }

                // If no SKU succeeded, treat row as critical failure => rollback campaign/adgroup
                if (count($createdAds) === 0) {
                    throw new \RuntimeException("All Product Ads failed for Row {$rowNo}. Rolling back Campaign/AdGroup.");
                }

                // 4) Upsert (multiple ads)
                $dataForUpsert = $data;
                // Keep compatibility if your upsert expects sku sometimes; but we pass full rows anyway
                $dataForUpsert['sku'] = null;

                $this->sellingAdsItemService->upsertCreatedProductAdsAndCampaigns(
                    $dataForUpsert,
                    $createdAds
                );

                Log::info('SP AUTO create flow completed', [
                    'draft_id'   => $draft->id,
                    'row'        => $rowNo,
                    'campaignId' => $campaignId,
                    'adGroupId'  => $adGroupId,
                    'created'    => count($createdAds),
                ]);
            } catch (\Throwable $e) {
                // rollback best-effort
                try {
                    if ($adGroupId) {
                        $this->sellingAdsItemService->archiveAdGroup($profileId, $adGroupId);
                    }
                } catch (\Throwable $x) {
                    Log::warning('Rollback: archiveAdGroup failed', [
                        'draft_id'  => $draft->id,
                        'row'       => $rowNo,
                        'adGroupId' => $adGroupId,
                        'error'     => $x->getMessage(),
                    ]);
                }

                try {
                    if ($campaignId) {
                        $this->sellingAdsItemService->archiveCampaign($profileId, $campaignId);
                    }
                } catch (\Throwable $x) {
                    Log::warning('Rollback: archiveCampaign failed', [
                        'draft_id'   => $draft->id,
                        'row'        => $rowNo,
                        'campaignId' => $campaignId,
                        'error'      => $x->getMessage(),
                    ]);
                }

                Log::error('SP AUTO create flow failed', [
                    'draft_id' => $draft->id,
                    'asin'     => $data['asin'],
                    'row'      => $rowNo,
                    'name'     => $c['name'] ?? null,
                    'error'    => $e->getMessage(),
                ]);

                // row-level failure entry
                $results['failed'][] = [
                    'row'   => $rowNo,
                    'sku'   => '-',
                    'name'  => $c['name'] ?? '-',
                    'error' => $e->getMessage(),
                ];
            }
        }

        // finalize status like manual flow (optional but consistent)
        $errorText = null;
        if (!empty($results['failed'])) {
            $errorText = collect($results['failed'])
                ->map(fn($f) => "SKU {$f['sku']} · Row {$f['row']} ({$f['name']}): {$f['error']}")
                ->implode("\n");
        }

        $draft->update([
            'status' => $errorText ? 'failed' : 'submitted',
            'error'  => $errorText,
        ]);

        $toastSuccess = array_map(
            fn($s) => "SKU {$s['sku']} · Row {$s['row']}: Campaign {$s['campaignId']} · AdGroup {$s['adGroupId']} · Ad {$s['adId']} created",
            $results['success']
        );

        $toastErrors = array_map(
            fn($f) => "SKU {$f['sku']} · Row {$f['row']} ({$f['name']}): {$f['error']}",
            $results['failed']
        );

        $summary = 'Created: ' . count($results['success']) . ' | Failed: ' . count($results['failed']);

        return back()->with([
            'success'       => $summary,
            'toast_success' => $toastSuccess,
            'toast_errors'  => $toastErrors,
        ]);
    }

    public function manualCreateFromDraft(int $draft)
    {
        $draft = CampaignDraft::where('id', $draft)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $data = [
            'asin'           => $draft->asin,
            'sku'            => $draft->sku, // may be array in DB
            'country'        => $draft->country ?? 'US',
            'campaign_type'  => $draft->campaign_type ?? 'SP',
            'targeting_type' => $draft->targeting_type ?? 'MANUAL',
            'pst_date'       => now('America/Los_Angeles')->format('d-m-Y'),
            'campaigns'      => $draft->campaigns ?? [],
        ];

        $skus = $this->normalizeSkus($data['sku']);

        if (empty($skus)) {
            return back()->withErrors(['draft' => 'Draft has no SKU selected.']);
        }

        if (empty($data['campaigns'])) {
            return back()->withErrors(['draft' => 'Draft has no campaigns to create.']);
        }

        $profileId = $this->sellingAdsItemService->resolveProfileId($data['country']);

        $results = [
            'success' => [],
            'failed'  => [],
        ];

        $minDailyBudget = 1.00;

        $draft->update([
            'status' => 'processing',
            'error'  => null,
        ]);

        foreach ($data['campaigns'] as $index => $c) {
            $rowNo = $index + 1;

            $campaignId = null;
            $adGroupId  = null;

            $createdProductAds = [];
            $finalName = $c['name'] ?? null;

            try {
                $budget = (float) ($c['budget'] ?? 0);

                Log::info('SP MANUAL create flow started', [
                    'draft_id' => $draft->id,
                    'asin'     => $data['asin'],
                    'row'      => $rowNo,
                    'name'     => $c['name'] ?? null,
                    'budget'   => $budget,
                    'skus'     => $skus,
                ]);

                $state = strtoupper($c['state'] ?? 'ENABLED');
                if (!in_array($state, ['ENABLED', 'PAUSED'], true)) {
                    $state = 'ENABLED';
                }

                // 1) Campaign (once)
                $camp = $this->sellingAdsItemService->spCreateCampaignWithRetry(
                    $profileId,
                    $c['name'],
                    $budget,
                    [
                        'maxAttempts'    => 4,
                        'minDailyBudget' => $minDailyBudget,
                    ],
                    'MANUAL',
                    $state
                );

                if (!$camp['ok']) {
                    throw new \RuntimeException('Campaign: ' . implode(' | ', $camp['messages']));
                }

                $campaignId = $camp['id'];
                $finalName  = $camp['name_used'] ?? $c['name'];

                // 2) AdGroup (once)
                $adGroupName = $data['asin'] . '_AdGroup_' . $rowNo;

                $ag = $this->sellingAdsItemService->spCreateAdGroup(
                    $profileId,
                    $campaignId,
                    $adGroupName,
                    0.10
                );

                if (!$ag['ok']) {
                    throw new \RuntimeException('AdGroup: ' . implode(' | ', $ag['messages']));
                }

                $adGroupId = $ag['id'];

                // 3) Product Ads (MULTIPLE SKUs)
                foreach ($skus as $sku) {
                    try {
                        $pa = $this->sellingAdsItemService->spCreateProductAd(
                            $profileId,
                            $campaignId,
                            $adGroupId,
                            $data['asin'],
                            $sku
                        );

                        if (!$pa['ok']) {
                            throw new \RuntimeException('ProductAd: ' . implode(' | ', $pa['messages']));
                        }

                        $createdProductAds[] = [
                            'row'        => $rowNo,
                            'sku'        => $sku,
                            'campaignId' => $campaignId,
                            'adGroupId'  => $adGroupId,
                            'adId'       => $pa['id'],
                            'name'       => $finalName,
                            'budget'     => $budget,
                        ];

                        $results['success'][] = end($createdProductAds);
                    } catch (\Throwable $skuErr) {
                        Log::error('SP MANUAL product ad failed for SKU', [
                            'draft_id'   => $draft->id,
                            'row'        => $rowNo,
                            'sku'        => $sku,
                            'campaignId' => $campaignId,
                            'adGroupId'  => $adGroupId,
                            'error'      => $skuErr->getMessage(),
                        ]);

                        $results['failed'][] = [
                            'row'   => $rowNo,
                            'sku'   => $sku,
                            'name'  => $c['name'] ?? '-',
                            'error' => $skuErr->getMessage(),
                        ];
                    }
                }

                if (count($createdProductAds) === 0) {
                    throw new \RuntimeException("All Product Ads failed for Row {$rowNo}. Campaign/AdGroup will be rolled back.");
                }

                // 4) Keywords OR Targets (once per row)
                $keywords = $c['keywords'] ?? false;
                $targets  = $c['targets']  ?? false;

                $hasKeywords = is_array($keywords) && count($keywords) > 0;
                $hasTargets  = is_array($targets)  && count($targets) > 0;

                if ($hasKeywords && $hasTargets) {
                    throw new \RuntimeException("Draft row {$rowNo} has both keywords and targets. Only one allowed.");
                }

                if ($hasKeywords) {
                    $kwResp = $this->sellingAdsItemService->spCreateKeywordsFromDraft(
                        $profileId,
                        $campaignId,
                        $adGroupId,
                        $keywords
                    );

                    if (!$kwResp['ok']) {
                        throw new \RuntimeException('Keywords: ' . implode(' | ', $kwResp['messages']));
                    }
                }

                if ($hasTargets) {
                    $tarResp = $this->sellingAdsItemService->spCreateTargetFromDraft(
                        $profileId,
                        $campaignId,
                        $adGroupId,
                        $targets
                    );

                    if (!$tarResp['ok']) {
                        throw new \RuntimeException('Targets: ' . implode(' | ', $tarResp['messages']));
                    }
                }

                // ✅ 5) Upsert (LEGACY SAFE): run per-SKU so sku is always a STRING
                foreach ($createdProductAds as $row) {
                    $dataForUpsert = $data;
                    $dataForUpsert['campaign_state'] = $state;
                    $dataForUpsert['sku'] = $row['sku']; // ✅ critical fix (string only)

                    $this->sellingAdsItemService->upsertCreatedProductAdsAndCampaigns(
                        $dataForUpsert,
                        [$row]
                    );
                }

                Log::info('SP MANUAL create flow completed', [
                    'draft_id'   => $draft->id,
                    'row'        => $rowNo,
                    'campaignId' => $campaignId,
                    'adGroupId'  => $adGroupId,
                    'created'    => count($createdProductAds),
                ]);
            } catch (\Throwable $e) {
                try {
                    if ($adGroupId) {
                        $this->sellingAdsItemService->archiveAdGroup($profileId, $adGroupId);
                    }
                } catch (\Throwable $x) {
                    Log::warning('Rollback: archiveAdGroup failed', [
                        'draft_id'  => $draft->id,
                        'row'       => $rowNo,
                        'adGroupId' => $adGroupId,
                        'error'     => $x->getMessage(),
                    ]);
                }

                try {
                    if ($campaignId) {
                        $this->sellingAdsItemService->archiveCampaign($profileId, $campaignId);
                    }
                } catch (\Throwable $x) {
                    Log::warning('Rollback: archiveCampaign failed', [
                        'draft_id'   => $draft->id,
                        'row'        => $rowNo,
                        'campaignId' => $campaignId,
                        'error'      => $x->getMessage(),
                    ]);
                }

                Log::error('SP MANUAL create flow failed', [
                    'draft_id' => $draft->id,
                    'asin'     => $data['asin'],
                    'row'      => $rowNo,
                    'name'     => $c['name'] ?? null,
                    'error'    => $e->getMessage(),
                ]);

                $results['failed'][] = [
                    'row'   => $rowNo,
                    'sku'   => '-',
                    'name'  => $c['name'] ?? '-',
                    'error' => $e->getMessage(),
                ];
            }
        }

        $errorText = null;

        if (!empty($results['failed'])) {
            $errorText = collect($results['failed'])
                ->map(fn($f) => "SKU {$f['sku']} · Row {$f['row']} ({$f['name']}): {$f['error']}")
                ->implode("\n");
        }

        $draft->update([
            'status' => $errorText ? 'failed' : 'submitted',
            'error'  => $errorText,
        ]);

        $toastSuccess = array_map(
            fn($s) => "SKU {$s['sku']} · Row {$s['row']}: Campaign {$s['campaignId']} · AdGroup {$s['adGroupId']} · Ad {$s['adId']} created",
            $results['success']
        );

        $toastErrors = array_map(
            fn($f) => "SKU {$f['sku']} · Row {$f['row']} ({$f['name']}): {$f['error']}",
            $results['failed']
        );

        $summary = 'Created: ' . count($results['success']) . ' | Failed: ' . count($results['failed']);

        return back()->with([
            'success'       => $summary,
            'toast_success' => $toastSuccess,
            'toast_errors'  => $toastErrors,
        ]);
    }


    public function draftsPage()
    {
        return view('pages.admin.amzAds.data.campaign.draft');
    }

    /**
     * Normalize SKU input to array<string>
     *
     * @param mixed $value
     * @return array<int, string>
     */
    private function normalizeSkus(mixed $value): array
    {
        $skus = [];

        if (is_array($value)) {
            $skus = $value;
        } elseif (is_string($value) && $value !== '') {
            $skus = [$value];
        }

        $skus = collect($skus)
            ->map(fn($v) => is_string($v) ? trim($v) : '')
            ->filter(fn($v) => $v !== '')
            ->unique()
            ->values()
            ->all();

        return $skus;
    }
}
