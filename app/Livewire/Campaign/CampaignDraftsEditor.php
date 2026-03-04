<?php

namespace App\Livewire\Campaign;

use Livewire\Component;
use App\Models\CampaignDraft;
use App\Services\Ads\CampaignCreationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CampaignDraftsEditor extends Component
{
    public int $draftId;

    public ?string $asin = null;

    /**
     * MULTI SKU
     *
     * @var array<int,string>
     */
    public array $sku = [];

    public ?string $campaignType = null;
    public ?string $targetingType = null;

    /**
     * Stored campaigns payload from DB
     *
     * @var array<int, array<string,mixed>>
     */
    public array $campaigns = [];

    public array $rowMode = [];      // keyword|target|null
    public array $keywordDraft = []; // rows
    public array $targetDraft = [];  // rows (API-style)

    public ?string $lastSavedAt = null;

    public function mount(int $draftId): void
    {
        $this->draftId = $draftId;
        $this->loadDraft();
    }

    public function loadDraft(): void
    {
        $draft = CampaignDraft::query()
            ->where('id', $this->draftId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $this->asin = $draft->asin;
        $this->sku = is_array($draft->sku) ? $draft->sku : [];
        $this->campaignType = $draft->campaign_type;
        $this->targetingType = $draft->targeting_type;

        $this->campaigns = is_array($draft->campaigns) ? $draft->campaigns : [];

        $hydrated = app(CampaignCreationService::class)->hydrateDraftEditor($this->campaigns);

        $this->rowMode = $hydrated['rowMode'];
        $this->keywordDraft = $hydrated['keywordDraft'];
        $this->targetDraft = $hydrated['targetDraft'];

        $this->lastSavedAt = optional($draft->updated_at)->format('d M, H:i');

        $this->resetValidation();
    }

    // ---------------------------
    // Mode switching
    // ---------------------------
    public function setRowMode(int $i, string $mode): void
    {
        if (!in_array($mode, ['keyword', 'target'], true)) return;

        $this->rowMode[$i] = $mode;

        if ($mode === 'keyword') {
            $this->targetDraft[$i] = [];
        } else {
            $this->keywordDraft[$i] = [];
        }

        $this->resetValidation();
        $this->saveDraft();
    }

    public function clearRowMode(int $i): void
    {
        $this->rowMode[$i] = null;
        $this->keywordDraft[$i] = [];
        $this->targetDraft[$i] = [];

        $this->resetValidation();
        $this->saveDraft();
    }

    // ---------------------------
    // Keyword rows
    // ---------------------------
    public function addKeywordRow(int $i): void
    {
        if (($this->rowMode[$i] ?? null) !== 'keyword') return;

        $this->keywordDraft[$i] = $this->keywordDraft[$i] ?? [];
        if (count($this->keywordDraft[$i]) >= 10) return;

        $this->keywordDraft[$i][] = [
            'text'  => '',
            'bid'   => null,
            'match' => 'BROAD',
        ];

        $this->saveDraft();
    }

    public function removeKeywordRow(int $i, int $k): void
    {
        if (!isset($this->keywordDraft[$i][$k])) return;

        unset($this->keywordDraft[$i][$k]);
        $this->keywordDraft[$i] = array_values($this->keywordDraft[$i]);

        $this->resetValidation();
        $this->saveDraft();
    }

    // ---------------------------
    // Target rows (API style)
    // ---------------------------
    public function addTargetRow(int $i): void
    {
        if (($this->rowMode[$i] ?? null) !== 'target') return;

        $this->targetDraft[$i] = $this->targetDraft[$i] ?? [];
        if (count($this->targetDraft[$i]) >= 10) return;

        $this->targetDraft[$i][] = [
            'bid'            => null,
            'expressionType' => 'MANUAL',
            'state'          => 'ENABLED',
            'expression'     => [
                [
                    'type'  => 'ASIN_SAME_AS',
                    'value' => '',
                ],
            ],
        ];

        $this->saveDraft();
    }

    public function removeTargetRow(int $i, int $k): void
    {
        if (!isset($this->targetDraft[$i][$k])) return;

        unset($this->targetDraft[$i][$k]);
        $this->targetDraft[$i] = array_values($this->targetDraft[$i]);

        $this->resetValidation();
        $this->saveDraft();
    }

    // ---------------------------
    // Live validate + autosave
    // ---------------------------
    public function updated($name, $value): void
    {
        if (str_starts_with($name, 'keywordDraft.') && str_ends_with($name, '.text') && is_string($value)) {
            $clean = trim(preg_replace('/\s+/', ' ', $value));
            data_set($this->keywordDraft, str_replace('keywordDraft.', '', $name), $clean);
        }

        if (str_starts_with($name, 'targetDraft.') && str_ends_with($name, '.expression.0.value') && is_string($value)) {
            $clean = trim(preg_replace('/\s+/', ' ', $value));
            data_set($this->targetDraft, str_replace('targetDraft.', '', $name), $clean);
        }

        $rules = $this->validateOnlyRules($name);
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

    protected function rules(): array
    {
        return [
            'rowMode.*' => ['nullable', 'in:keyword,target'],

            'keywordDraft.*.*.text'  => ['nullable', 'string', 'min:1', 'max:80'],
            'keywordDraft.*.*.bid'   => ['nullable', 'numeric', 'min:0.02'],
            'keywordDraft.*.*.match' => ['nullable', 'in:BROAD,PHRASE,EXACT'],

            'targetDraft.*.*.bid' => ['nullable', 'numeric', 'min:0.02'],
            'targetDraft.*.*.state' => ['nullable', 'in:ENABLED,PAUSED,ARCHIVED'],
            'targetDraft.*.*.expression.0.type' => ['nullable', 'in:' . implode(',', app(CampaignCreationService::class)->expressionTypes())],
            'targetDraft.*.*.expression.0.value' => ['nullable', 'string', 'min:1', 'max:80'],
        ];
    }

    private function validateOnlyRules(string $name): array
    {
        if (str_starts_with($name, 'keywordDraft.')) {
            if (str_ends_with($name, '.text'))  return [$name => ['required', 'string', 'min:1', 'max:80']];
            if (str_ends_with($name, '.bid'))   return [$name => ['required', 'numeric', 'min:0.02']];
            if (str_ends_with($name, '.match')) return [$name => ['required', 'in:BROAD,PHRASE,EXACT']];
        }

        if (str_starts_with($name, 'targetDraft.')) {
            if (str_ends_with($name, '.bid')) return [$name => ['required', 'numeric', 'min:0.02']];
            if (str_ends_with($name, '.state')) return [$name => ['required', 'in:ENABLED,PAUSED,ARCHIVED']];
            if (str_ends_with($name, '.expression.0.type')) return [$name => ['required', 'in:' . implode(',', app(CampaignCreationService::class)->expressionTypes())]];
            if (str_ends_with($name, '.expression.0.value')) return [$name => ['required', 'string', 'min:1', 'max:80']];
        }

        return [];
    }

    // ---------------------------
    // Save Draft
    // ---------------------------
    public function saveDraft(): void
    {
        try {
            $payload = app(CampaignCreationService::class)->mergeDraftEditorToCampaigns(
                $this->campaigns,
                $this->rowMode,
                $this->keywordDraft,
                $this->targetDraft
            );

            CampaignDraft::query()
                ->where('id', $this->draftId)
                ->where('user_id', Auth::id())
                ->update([
                    'campaigns' => $payload,
                    'status'    => 'draft',
                    'error'     => null,
                ]);

            $this->campaigns = $payload;
            $this->lastSavedAt = Carbon::now()->format('d M, H:i');
        } catch (\Throwable $e) {
            Log::error('Draft editor save failed', [
                'draft_id' => $this->draftId,
                'error'    => $e->getMessage(),
            ]);

            $this->addError('draft', 'Draft save failed. Please try again.');
        }
    }

    public function render()
    {
        return view('livewire.campaign.campaign-drafts-editor', [
            'expressionTypes' => app(CampaignCreationService::class)->expressionTypes(),
        ]);
    }
}
