<?php

namespace App\Livewire\Campaign;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;
use Carbon\Carbon;
use App\Models\CampaignDraft;
use App\Services\Ads\CampaignCreationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CampaignCreateForm extends Component
{
    use WithFileUploads;

    #[Url(as: 'asin', except: '')]
    public ?string $asin = null;

    /** @var array<int, string> */
    public array $sku = [];

    public string $campaignType = 'SP';
    public ?string $country     = 'US';
    public string $pstDate;

    public int $campaignCount  = 1;
    public ?float $totalBudget = 10;

    public string $targetingType  = 'AUTO';
    public string $matchType      = 'BROAD';
    public string $campaign_state = 'ENABLED';

    public array $generatedCampaigns = [];

    public array $rowMode = [];
    public array $keywordDraft = [];
    public array $targetDraft  = [];

    public bool $showKeywords = false;
    public ?int $draftId = null;

    public bool $verifiedReady = false;
    public bool $skipVerifiedResetOnce = false;

    // import state:
    // importCampaignIndex = null => GLOBAL import (all campaigns)
    // importCampaignIndex = int  => single campaign import
    public ?int $importCampaignIndex = null;

    // One file input used for both single/global
    public $keywordImportFile = null;

    /** @var array<int,bool> */
    public array $keywordImported = [];

    public function mount(): void
    {
        $this->pstDate = Carbon::now('America/Los_Angeles')->format('d-m-Y');
    }

    #[On('asin-selected')]
    public function setAsin(?string $asin): void
    {
        $this->asin = $asin;
        $this->sku = [];

        $this->generatedCampaigns = [];
        $this->rowMode = [];
        $this->keywordDraft = [];
        $this->targetDraft = [];
        $this->keywordImported = [];

        $this->showKeywords = false;
        $this->verifiedReady = false;
        $this->draftId = null;

        $this->importCampaignIndex = null;
        $this->keywordImportFile = null;

        $this->resetValidation();
    }

    #[On('sku-selected')]
    public function setSku(array $skus = []): void
    {
        $this->sku = collect($skus)
            ->filter(fn($v) => filled($v))
            ->unique()
            ->values()
            ->all();

        $this->generatedCampaigns = [];
        $this->verifiedReady = false;

        $this->resetValidation();
    }

    public function clear(): void
    {
        $this->asin = null;
        $this->sku = [];

        $this->generatedCampaigns = [];
        $this->rowMode = [];
        $this->keywordDraft = [];
        $this->targetDraft = [];
        $this->keywordImported = [];

        $this->showKeywords = false;
        $this->verifiedReady = false;
        $this->draftId = null;

        $this->importCampaignIndex = null;
        $this->keywordImportFile = null;

        $this->resetValidation();
        $this->dispatch('asin-clear');
    }

    public function closeCreateModal(): void
    {
        $this->generatedCampaigns = [];
        $this->rowMode = [];
        $this->keywordDraft = [];
        $this->targetDraft = [];
        $this->keywordImported = [];

        $this->showKeywords = false;
        $this->verifiedReady = false;

        $this->importCampaignIndex = null;
        $this->keywordImportFile = null;

        $this->resetValidation();
    }

    // ===========================
    // GENERATE CAMPAIGNS
    // ===========================
    public function generateCampaignNames(): void
    {
        $this->validate([
            'asin'           => ['required', 'string'],
            'sku'            => ['required', 'array', 'min:1'],
            'sku.*'          => ['required', 'string'],

            'campaignCount'  => ['required', 'integer', 'min:1', 'max:50'],
            'totalBudget'    => ['required', 'numeric', 'min:0.01'],
            'targetingType'  => ['required', 'in:AUTO,MANUAL'],
            'matchType'      => ['required', 'in:BROAD,PHRASE,EXACT'],
            'campaignType'   => ['required', 'in:SP,SB,SD'],
            'country'        => ['required', 'string'],
            'campaign_state' => ['required', 'in:ENABLED,PAUSED'],
        ]);

        $service = app(CampaignCreationService::class);

        $result = $service->generate([
            'asin'          => (string) $this->asin,
            'sku'           => $this->sku,
            'campaignType'  => $this->campaignType,
            'targetingType' => $this->targetingType,
            'matchType'     => $this->matchType,
            'campaignState' => $this->campaign_state,
            'country'       => (string) $this->country,
            'campaignCount' => (int) $this->campaignCount,
            'totalBudget'   => (float) $this->totalBudget,
            'source'        => 'SYSTEM',
            'marketTz'      => config('timezone.market', 'America/Los_Angeles'),
        ]);

        $this->pstDate = $result['pstDate'];
        $this->generatedCampaigns = $result['campaigns'];

        $this->rowMode = [];
        $this->keywordDraft = [];
        $this->targetDraft = [];
        $this->keywordImported = [];
        $this->verifiedReady = false;
        $this->draftId = null;

        if (strtoupper($this->targetingType) === 'AUTO') {
            $this->showKeywords = false;
            $this->saveDraft();
            return;
        }

        $this->showKeywords = true;

        foreach ($this->generatedCampaigns as $i => $_row) {
            $this->rowMode[$i] = null;
            $this->keywordDraft[$i] = [];
            $this->targetDraft[$i] = [];
            $this->keywordImported[$i] = false;
        }

        $this->resetValidation();
        $this->saveDraft();
    }

    // ===========================
    // DRAFT SAVE
    // ===========================
    public function saveDraft(): void
    {
        try {
            $payloadCampaigns = app(CampaignCreationService::class)->buildCampaignsPayload(
                $this->generatedCampaigns,
                $this->rowMode,
                $this->keywordDraft,
                $this->targetDraft
            );

            $draft = CampaignDraft::updateOrCreate(
                ['id' => $this->draftId],
                [
                    'user_id'        => Auth::id(),
                    'asin'           => $this->asin,
                    'sku'            => $this->sku,
                    'country'        => $this->country,
                    'campaign_type'  => $this->campaignType,
                    'targeting_type' => $this->targetingType,
                    'status'         => 'draft',
                    'error'          => null,
                    'campaigns'      => $payloadCampaigns,
                ]
            );

            $this->draftId = $draft->id;
        } catch (\Throwable $e) {
            Log::error('Campaign draft save failed', [
                'asin'  => $this->asin,
                'sku'   => $this->sku,
                'error' => $e->getMessage(),
            ]);

            $this->addError('draft', 'Draft save failed. Please try again.');
        }
    }

    // ===========================
    // KEYWORD IMPORT (SINGLE + GLOBAL)
    // ===========================
    public function openKeywordImportFor(int $i): void
    {
        if (strtoupper($this->targetingType) !== 'MANUAL') {
            return;
        }

        if (!isset($this->generatedCampaigns[$i])) {
            return;
        }

        $this->resetErrorBag('import');
        $this->importCampaignIndex = $i; // single campaign
        $this->keywordImportFile = null;

        $this->dispatch('open-keyword-import-modal');
    }

    // NEW: global import
    public function openKeywordImportAll(): void
    {
        if (strtoupper($this->targetingType) !== 'MANUAL') {
            return;
        }

        if (empty($this->generatedCampaigns)) {
            $this->addError('import', 'Generate campaigns first.');
            return;
        }

        $this->resetErrorBag('import');
        $this->importCampaignIndex = null; // all campaigns
        $this->keywordImportFile = null;

        $this->dispatch('open-keyword-import-modal');
    }

    public function closeKeywordImport(): void
    {
        $this->resetErrorBag('import');
        $this->importCampaignIndex = null;
        $this->keywordImportFile = null;

        $this->dispatch('close-keyword-import-modal');
    }

    // NEW: one method used by modal for both cases
    public function importKeywords(): void
    {
        if (strtoupper($this->targetingType) !== 'MANUAL') {
            $this->addError('import', 'Keyword import is only available for MANUAL targeting.');
            return;
        }

        $this->resetErrorBag('import');

        $this->validate([
            'keywordImportFile' => ['required', 'file', 'max:5120', 'mimes:csv,txt,xlsx'],
        ]);

        try {
            $service = app(CampaignCreationService::class);

            $rows = $service->importKeywordsForSingleCampaign($this->keywordImportFile, 100);

            // If index is null => apply to ALL campaigns
            if ($this->importCampaignIndex === null) {
                $applied = $service->applyKeywordsToAllCampaigns(
                    $rows,
                    $this->rowMode,
                    $this->keywordDraft,
                    $this->targetDraft,
                    $this->generatedCampaigns
                );

                $this->rowMode = $applied['rowMode'];
                $this->keywordDraft = $applied['keywordDraft'];
                $this->targetDraft = $applied['targetDraft'];

                // lock targeting for all campaigns
                foreach (array_keys($this->generatedCampaigns) as $i) {
                    $this->keywordImported[$i] = true;
                }
            } else {
                $i = $this->importCampaignIndex;

                if (!isset($this->generatedCampaigns[$i])) {
                    $this->addError('import', 'Invalid campaign selected for import.');
                    return;
                }

                $applied = $service->applyKeywordsToCampaignIndex(
                    $i,
                    $rows,
                    $this->rowMode,
                    $this->keywordDraft,
                    $this->targetDraft
                );

                $this->rowMode = $applied['rowMode'];
                $this->keywordDraft = $applied['keywordDraft'];
                $this->targetDraft = $applied['targetDraft'];

                $this->keywordImported[$i] = true;
            }

            $this->verifiedReady = false;
            $this->saveDraft();

            $this->closeKeywordImport();
        } catch (\Throwable $e) {
            Log::error('Keyword import failed', [
                'draft_id' => $this->draftId,
                'campaign_index' => $this->importCampaignIndex,
                'error'    => $e->getMessage(),
            ]);

            $this->addError('import', $e->getMessage());
        }
    }

    // ===========================
    // ROW MODE
    // ===========================
    public function setRowMode(int $i, string $mode): void
    {
        if (!in_array($mode, ['keyword', 'target'], true)) {
            return;
        }

        if ($mode === 'target' && (($this->keywordImported[$i] ?? false) === true)) {
            $this->addError("rowMode.$i", "Targets are disabled because keywords were imported. Click Clear to unlock.");
            return;
        }

        $this->rowMode[$i] = $mode;
        $this->verifiedReady = false;

        $this->resetErrorBag("rowMode.$i");
        $this->resetErrorBag("keywordDraft.$i");
        $this->resetErrorBag("targetDraft.$i");

        if ($mode === 'keyword') {
            $this->targetDraft[$i] = [];
        } else {
            $this->keywordDraft[$i] = [];
        }

        // manual switch -> unlock import lock for that campaign
        $this->keywordImported[$i] = false;

        $this->saveDraft();
    }

    public function clearRowMode(int $i): void
    {
        $this->rowMode[$i] = null;
        $this->keywordDraft[$i] = [];
        $this->targetDraft[$i] = [];
        $this->verifiedReady = false;

        $this->resetErrorBag("rowMode.$i");
        $this->resetErrorBag("keywordDraft.$i");
        $this->resetErrorBag("targetDraft.$i");

        $this->keywordImported[$i] = false;

        $this->saveDraft();
    }

    // ===========================
    // ADD/REMOVE ROWS
    // ===========================
    public function addKeywordRow(int $i): void
    {
        if (($this->rowMode[$i] ?? null) !== 'keyword') {
            return;
        }

        $this->keywordDraft[$i] = $this->keywordDraft[$i] ?? [];
        if (count($this->keywordDraft[$i]) >= 100) {
            return;
        }

        $this->keywordDraft[$i][] = [
            'text'  => '',
            'bid'   => null,
            'match' => 'BROAD',
        ];

        $this->verifiedReady = false;
        $this->resetErrorBag("keywordDraft.$i");

        $this->keywordImported[$i] = false;

        $this->saveDraft();
    }

    public function addTargetRow(int $i): void
    {
        if (($this->rowMode[$i] ?? null) !== 'target') {
            return;
        }

        $this->targetDraft[$i] = $this->targetDraft[$i] ?? [];
        if (count($this->targetDraft[$i]) >= 100) {
            return;
        }

        $this->targetDraft[$i][] = [
            'type'  => 'ASIN_SAME_AS',
            'value' => '',
            'bid'   => null,
            'state' => 'ENABLED',
        ];

        $this->verifiedReady = false;
        $this->resetErrorBag("targetDraft.$i");

        $this->saveDraft();
    }

    public function removeKeywordRow(int $i, int $k): void
    {
        if (!isset($this->keywordDraft[$i][$k])) {
            return;
        }

        unset($this->keywordDraft[$i][$k]);
        $this->keywordDraft[$i] = array_values($this->keywordDraft[$i]);

        $this->verifiedReady = false;
        $this->resetErrorBag("keywordDraft.$i");

        $this->saveDraft();
    }

    public function removeTargetRow(int $i, int $k): void
    {
        if (!isset($this->targetDraft[$i][$k])) {
            return;
        }

        unset($this->targetDraft[$i][$k]);
        $this->targetDraft[$i] = array_values($this->targetDraft[$i]);

        $this->verifiedReady = false;
        $this->resetErrorBag("targetDraft.$i");

        $this->saveDraft();
    }

    // ===========================
    // LIVE VALIDATION + AUTOSAVE
    // ===========================
    public function updated($name, $value): void
    {
        if ($this->skipVerifiedResetOnce) {
            $this->skipVerifiedResetOnce = false;
        } else {
            $shouldReset =
                str_starts_with($name, 'keywordDraft.') ||
                str_starts_with($name, 'targetDraft.') ||
                str_starts_with($name, 'rowMode.');

            if ($shouldReset) {
                $this->verifiedReady = false;
            }
        }

        if (str_ends_with($name, '.text') && is_string($value) && str_starts_with($name, 'keywordDraft.')) {
            $clean = trim(preg_replace('/\s+/', ' ', $value));
            data_set($this->keywordDraft, str_replace('keywordDraft.', '', $name), $clean);
        }

        if (str_ends_with($name, '.value') && is_string($value) && str_starts_with($name, 'targetDraft.')) {
            $clean = trim(preg_replace('/\s+/', ' ', $value));
            data_set($this->targetDraft, str_replace('targetDraft.', '', $name), $clean);
        }

        $rules = $this->fieldRulesForValidateOnly($name);
        if (!empty($rules)) {
            $this->validateOnly($name, $rules);
        }

        if (
            str_starts_with($name, 'keywordDraft.') ||
            str_starts_with($name, 'targetDraft.') ||
            str_starts_with($name, 'rowMode.')
        ) {
            $this->saveDraft();
        }
    }

    // ===========================
    // RULES + helper
    // ===========================
    protected function rules(): array
    {
        return [
            'rowMode.*' => ['nullable', 'in:keyword,target'],

            'keywordDraft.*.*.text'  => ['nullable', 'string', 'min:1', 'max:80'],
            'keywordDraft.*.*.bid'   => ['nullable', 'numeric', 'min:0.02'],
            'keywordDraft.*.*.match' => ['nullable', 'in:BROAD,PHRASE,EXACT'],

            'targetDraft.*.*.type'  => ['nullable', 'in:' . implode(',', $this->targetTypes())],
            'targetDraft.*.*.value' => ['nullable', 'string', 'min:1', 'max:80'],
            'targetDraft.*.*.bid'   => ['nullable', 'numeric', 'min:0.02'],
            'targetDraft.*.*.state' => ['nullable', 'in:ENABLED,PAUSED,ARCHIVED'],
        ];
    }

    private function targetTypes(): array
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

    private function fieldRulesForValidateOnly(string $name): array
    {
        if (str_starts_with($name, 'keywordDraft.')) {
            if (str_ends_with($name, '.text')) {
                return [$name => ['required', 'string', 'min:1', 'max:80']];
            }
            if (str_ends_with($name, '.bid')) {
                return [$name => ['required', 'numeric', 'min:0.02']];
            }
            if (str_ends_with($name, '.match')) {
                return [$name => ['required', 'in:BROAD,PHRASE,EXACT']];
            }
        }

        if (str_starts_with($name, 'targetDraft.')) {
            if (str_ends_with($name, '.value')) {
                return [$name => ['required', 'string', 'min:1', 'max:80']];
            }
            if (str_ends_with($name, '.bid')) {
                return [$name => ['required', 'numeric', 'min:0.02']];
            }
            if (str_ends_with($name, '.type')) {
                return [$name => ['required', 'in:' . implode(',', $this->targetTypes())]];
            }
            if (str_ends_with($name, '.state')) {
                return [$name => ['required', 'in:ENABLED,PAUSED,ARCHIVED']];
            }
        }

        return [];
    }

    // ===========================
    // VERIFY BEFORE SUBMIT
    // ===========================
    public function verifyBeforeSubmit(): void
    {
        $this->resetValidation();

        foreach ($this->generatedCampaigns as $i => $row) {
            if (!($this->rowMode[$i] ?? null)) {
                $this->addError("rowMode.$i", "Select Keyword or Targeting for this campaign.");
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            $this->verifiedReady = false;
            return;
        }

        foreach ($this->generatedCampaigns as $i => $row) {
            $mode = $this->rowMode[$i];

            if ($mode === 'keyword') {
                $rows = $this->keywordDraft[$i] ?? [];
                if (count($rows) === 0) {
                    $this->addError("keywordDraft.$i", "Add at least 1 keyword.");
                    continue;
                }

                foreach ($rows as $k => $kw) {
                    $this->validate([
                        "keywordDraft.$i.$k.text"  => ['required', 'string', 'min:1', 'max:80'],
                        "keywordDraft.$i.$k.bid"   => ['required', 'numeric', 'min:0.02'],
                        "keywordDraft.$i.$k.match" => ['required', 'in:BROAD,PHRASE,EXACT'],
                    ]);
                }
            }

            if ($mode === 'target') {
                $rows = $this->targetDraft[$i] ?? [];
                if (count($rows) === 0) {
                    $this->addError("targetDraft.$i", "Add at least 1 target.");
                    continue;
                }

                foreach ($rows as $k => $t) {
                    $this->validate([
                        "targetDraft.$i.$k.type"  => ['required', 'in:' . implode(',', $this->targetTypes())],
                        "targetDraft.$i.$k.value" => ['required', 'string', 'min:1', 'max:80'],
                        "targetDraft.$i.$k.bid"   => ['required', 'numeric', 'min:0.02'],
                        "targetDraft.$i.$k.state" => ['required', 'in:ENABLED,PAUSED,ARCHIVED'],
                    ]);
                }
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            $this->verifiedReady = false;
            return;
        }

        $this->skipVerifiedResetOnce = true;
        $this->verifiedReady = true;
        $this->saveDraft();
    }

    public function render()
    {
        return view('livewire.campaign.campaign-create-form');
    }
}
