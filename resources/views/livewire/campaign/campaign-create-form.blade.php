<div>
    {{-- ASIN + SKU --}}
    <div class="row g-2 align-items-end mb-3">
        {{-- ASIN --}}
        <div class="col-12 col-md-5">
            <label class="form-label mb-1">ASIN</label>
            <livewire:asin-search />
        </div>
        {{-- SKU --}}
        <div class="col-12 col-md-5">
            <label class="form-label mb-1 d-flex justify-content-between">
                <span>SKU</span>
            </label>

            <livewire:sku-search :asin="$asin" wire:key="sku-{{ $asin ?? 'none' }}" :error="$errors->first('sku')" />
        </div>
        {{-- Clear --}}
        <div class="col-12 col-md-2 d-flex gap-2">
            @if ($asin)
                <button type="button" class="btn btn-light flex-fill" wire:click="clear">
                    Clear all
                </button>
            @endif
        </div>
    </div>

    {{-- <div class="text-muted mb-3">
        <span class="fw-semibold">Selected:</span>
        ASIN: {{ $asin ?? '—' }} |
        SKU: {{ $sku ?? '—' }}
    </div> --}}
    {{-- Preview --}}
    <div class="mb-3">
        <div class="text-muted small mb-1">Preview</div>
        <div class="bg-light rounded px-3 py-2 d-flex align-items-center justify-content-between">
            <code class="mb-0 fs-6">
                {{ $asin ?? 'ASIN' }}________________
                SYSTEM_{{ $pstDate }}_CMP___
            </code>

            <span class="badge rounded-pill ms-2 px-3 fs-6 fw-semibold bg-primary-subtle text-primary">
                {{ strtoupper($campaignType) }}
            </span>
        </div>
    </div>

    {{-- Generate --}}
    <div class="row g-2 align-items-end">

        {{-- Count --}}
        <div class="col-12 col-md-2">
            <label class="form-label mb-1">Number of Campaign</label>
            <input type="number" class="form-control @error('campaignCount') is-invalid @enderror"
                wire:model.live="campaignCount" min="1" max="50" @disabled($generatedCampaigns)>
            <div class="invalid-feedback d-block" style="min-height:18px;">
                @error('campaignCount')
                    {{ $message }}
                @enderror
            </div>
        </div>

        {{-- Total Budget --}}
        <div class="col-12 col-md-2">
            <label class="form-label mb-1">Total Budget</label>
            <input type="number" step="0.01" class="form-control @error('totalBudget') is-invalid @enderror"
                wire:model.live="totalBudget" min="0.01" @disabled($generatedCampaigns)>

            <div class="invalid-feedback d-block" style="min-height:18px;">
                @error('totalBudget')
                    {{ $message }}
                @enderror
            </div>
        </div>

        {{-- Targeting --}}
        <div class="col-12 col-md-2">
            <label class="form-label mb-1">Targeting Type</label>
            <select class="form-select @error('targetingType') is-invalid @enderror" wire:model.live="targetingType"
                @disabled($generatedCampaigns)>
                <option value="AUTO">AUTO</option>
                <option value="MANUAL">MANUAL</option>
            </select>

            <div class="invalid-feedback d-block" style="min-height:18px;">
                @error('targetingType')
                    {{ $message }}
                @enderror
            </div>
        </div>

        {{-- Match --}}
        <div class="col-12 col-md-2">
            <label class="form-label mb-1">Match Type</label>
            <select class="form-select @error('matchType') is-invalid @enderror" wire:model.live="matchType"
                @disabled($generatedCampaigns)>
                @foreach (['BROAD', 'PHRASE', 'EXACT'] as $m)
                    <option value="{{ $m }}">{{ $m }}</option>
                @endforeach
            </select>

            <div class="invalid-feedback d-block" style="min-height:18px;">
                @error('matchType')
                    {{ $message }}
                @enderror
            </div>
        </div>
        {{-- Campaign State --}}
        <div class="col-12 col-md-2">
            <label class="form-label mb-1">Campaign State</label>
            <select class="form-select @error('campaign_state') is-invalid @enderror"
                wire:model.live="campaign_state"@disabled($generatedCampaigns)>
                <option value="ENABLED">Enabled</option>
                <option value="PAUSED">Paused</option>
            </select>

            <div class="invalid-feedback d-block" style="min-height:18px;">
                @error('campaign_state')
                    {{ $message }}
                @enderror
            </div>
        </div>
        {{-- Country --}}
        <div class="col-12 col-md-2">
            <label class="form-label mb-1">Country</label>
            <select class="form-select @error('country') is-invalid @enderror" wire:model.live="country"
                @disabled(true)>
                <option value="US">US</option>
                <option value="CA" disabled>CA</option>
            </select>

            <div class="invalid-feedback d-block" style="min-height:18px;">
                @error('country')
                    {{ $message }}
                @enderror
            </div>
        </div>


        {{-- Generate button --}}
        <div class="col-12 col-md-2 d-grid">
            <button type="button" class="btn btn-success btn-rounded waves-effect waves-light"
                wire:click="generateCampaignNames" wire:loading.attr="disabled" {{ empty($asin) ? 'disabled' : '' }}>
                <span wire:loading.remove wire:target="generateCampaignNames">
                    <i class="mdi mdi-auto-fix me-1"></i> Generate
                </span>
                <span wire:loading wire:target="generateCampaignNames">
                    Generating...
                </span>
            </button>
            {{-- reserve space like others (optional, keeps row height consistent) --}}
            <div style="min-height:18px;"></div>
        </div>
    </div>

    @if ($targetingType == 'AUTO')
        {{-- Generated list --}}
        @if (!empty($generatedCampaigns))
            <hr class="mb-2">
            <h6 class="my-2">Generated Campaigns - AUTO</h6>
            <div class="list-group">
                @foreach ($generatedCampaigns as $row)
                    @php
                        $isLowBudget = ($row['budget'] ?? 0) < 1;
                    @endphp
                    <div class="list-group-item d-flex align-items-center justify-content-between">
                        <code class="mb-0">{{ $row['name'] }}</code>
                        <span
                            class="badge rounded-pill ms-2 px-3 py-2 fs-6 fw-semibold
                        {{ $isLowBudget ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }}">
                            ${{ number_format($row['budget'], 2) }}
                        </span>
                    </div>
                @endforeach
            </div>
            <form method="POST" action="{{ route('admin.campaigns.auto.store', ['draft' => $draftId]) }}">
                @csrf
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success btn-rounded waves-effect waves-light">
                        <i class="mdi mdi-check me-1"></i>
                        Create All Campaigns
                    </button>

                    <button type="button" class="btn btn-light btn-rounded waves-effect waves-light"
                        wire:click="closeCreateModal">
                        Cancel
                    </button>
                </div>
            </form>
        @endif
    @elseif ($showKeywords)
        <hr class="mb-2">
        <div class="d-flex align-items-center justify-content-between my-2">
            <h6 class="mb-0">Generated Campaigns (Manual)</h6>

            <div class="d-flex gap-2">
                {{-- NEW: global keyword import --}}
                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="openKeywordImportAll"
                    @disabled(empty($generatedCampaigns) || strtoupper($targetingType) !== 'MANUAL')>
                    <i class="mdi mdi-file-excel-outline me-1"></i> Bulk Keywords (All)
                </button>

                {{-- existing --}}
                <button type="button" class="btn btn-sm btn-light" wire:click="saveDraft">
                    <i class="mdi mdi-content-save-outline me-1"></i> Save Draft
                </button>
            </div>

            @error('import')
                <div class="alert alert-warning py-2 mt-2">{{ $message }}</div>
            @enderror
        </div>


        @error('draft')
            <div class="alert alert-warning py-2">{{ $message }}</div>
        @enderror

        <div class="accordion" id="kwAccordion">
            @foreach ($generatedCampaigns as $i => $row)
                @php
                    $mode = $rowMode[$i] ?? null;
                    $kwCount = count($keywordDraft[$i] ?? []);
                    $tarCount = count($targetDraft[$i] ?? []);
                    $count = $mode === 'target' ? $tarCount : ($mode === 'keyword' ? $kwCount : 0);
                    $isKeyword = $mode === 'keyword';
                    $isTarget = $mode === 'target';
                    $rows = $isTarget ? $targetDraft[$i] ?? [] : $keywordDraft[$i] ?? [];
                @endphp

                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading-{{ $i }}">
                        <button class="accordion-button collapsed d-flex align-items-center justify-content-between"
                            type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $i }}"
                            aria-expanded="false" aria-controls="collapse-{{ $i }}">

                            <span class="me-2">
                                <code>{{ $row['name'] }}</code>
                            </span>

                            <span class="badge rounded-pill bg-info-subtle text-info ms-2">
                                {{ $count }}/100
                                {{ $isTarget ? 'Targets' : ($isKeyword ? 'Keywords' : 'Items') }}
                            </span>
                        </button>
                    </h2>

                    <div id="collapse-{{ $i }}" class="accordion-collapse collapse" wire:ignore.self
                        aria-labelledby="heading-{{ $i }}" data-bs-parent="#kwAccordion">

                        <div class="accordion-body">

                            {{-- Mode Buttons --}}
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex gap-2">
                                    <button type="button"
                                        class="btn btn-sm {{ $isKeyword ? 'btn-primary' : 'btn-outline-primary' }}"
                                        wire:click="setRowMode({{ $i }}, 'keyword')"
                                        {{ $isTarget ? 'disabled' : '' }}>
                                        <i class="mdi mdi-format-list-bulleted me-1"></i> Keyword
                                    </button>

                                    <button type="button"
                                        class="btn btn-sm {{ $isTarget ? 'btn-primary' : 'btn-outline-primary' }}"
                                        wire:click="setRowMode({{ $i }}, 'target')"
                                        {{ $isKeyword ? 'disabled' : '' }}
                                        {{ $keywordImported[$i] ?? false ? 'disabled' : '' }}>
                                        <i class="mdi mdi-target me-1"></i> Targeting
                                    </button>


                                    <button type="button" class="btn btn-sm btn-light"
                                        wire:click="clearRowMode({{ $i }})" {{ $mode ? '' : 'disabled' }}>
                                        <i class="mdi mdi-close-circle-outline me-1"></i> Clear
                                    </button>
                                </div>

                                {{-- Add row --}}
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                        wire:click="openKeywordImportFor({{ $i }})"
                                        @disabled(!$mode || $mode === 'target')>
                                        <i class="mdi mdi-file-excel-outline me-1"></i> Bulk Keywords
                                    </button>

                                    <button type="button" class="btn btn-sm btn-outline-success"
                                        wire:click="{{ $isTarget ? "addTargetRow($i)" : "addKeywordRow($i)" }}"
                                        {{ $mode ? '' : 'disabled' }} {{ $count >= 100 ? 'disabled' : '' }}>
                                        <i class="mdi mdi-plus me-1"></i> Add
                                    </button>
                                </div>
                            </div>

                            {{-- SHOW ROW MODE ERROR --}}
                            @error("rowMode.$i")
                                <div class="alert alert-danger py-2 small mb-2">
                                    {{ $message }}
                                </div>
                            @enderror

                            {{-- SHOW EMPTY LIST ERRORS --}}
                            @error("keywordDraft.$i")
                                <div class="alert alert-danger py-2 small mb-2">
                                    {{ $message }}
                                </div>
                            @enderror
                            @error("targetDraft.$i")
                                <div class="alert alert-danger py-2 small mb-2">
                                    {{ $message }}
                                </div>
                            @enderror

                            {{-- Hint --}}
                            @if (!$mode)
                                <div class="alert alert-warning py-2 small mb-2">
                                    Select <b>Keyword</b> or <b>Targeting</b> first for this campaign.
                                </div>
                            @endif

                            {{-- Table --}}
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width:45%;">{{ $isTarget ? 'Value' : 'Keyword' }}</th>
                                            <th style="width:20%;">Bid</th>
                                            <th style="width:30%;">{{ $isTarget ? 'Expression Type' : 'Match' }}</th>
                                            <th style="width:5%;"></th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @forelse($rows as $k => $rowInput)
                                            <tr
                                                wire:key="row-{{ $i }}-{{ $mode }}-{{ $k }}">
                                                {{-- VALUE / KEYWORD --}}
                                                <td>
                                                    @if ($isTarget)
                                                        <input type="text"
                                                            class="form-control form-control-sm @error("targetDraft.$i.$k.value") is-invalid @enderror"
                                                            placeholder="Enter value (ASIN / keyword group / etc.)"
                                                            wire:model.live.debounce.500ms="targetDraft.{{ $i }}.{{ $k }}.value">
                                                        <div class="invalid-feedback d-block">
                                                            @error("targetDraft.$i.$k.value")
                                                                {{ $message }}
                                                            @enderror
                                                        </div>
                                                    @else
                                                        <input type="text"
                                                            class="form-control form-control-sm @error("keywordDraft.$i.$k.text") is-invalid @enderror"
                                                            placeholder="Enter keyword"
                                                            wire:model.live.debounce.500ms="keywordDraft.{{ $i }}.{{ $k }}.text">
                                                        <div class="invalid-feedback d-block">
                                                            @error("keywordDraft.$i.$k.text")
                                                                {{ $message }}
                                                            @enderror
                                                        </div>
                                                    @endif
                                                </td>

                                                {{-- BID --}}
                                                <td>
                                                    @if ($isTarget)
                                                        <input type="number" step="0.01"
                                                            class="form-control form-control-sm @error("targetDraft.$i.$k.bid") is-invalid @enderror"
                                                            placeholder="0.75"
                                                            wire:model.live.debounce.500ms="targetDraft.{{ $i }}.{{ $k }}.bid">
                                                        <div class="invalid-feedback d-block">
                                                            @error("targetDraft.$i.$k.bid")
                                                                {{ $message }}
                                                            @enderror
                                                        </div>
                                                    @else
                                                        <input type="number" step="0.01"
                                                            class="form-control form-control-sm @error("keywordDraft.$i.$k.bid") is-invalid @enderror"
                                                            placeholder="0.75"
                                                            wire:model.live.debounce.500ms="keywordDraft.{{ $i }}.{{ $k }}.bid">
                                                        <div class="invalid-feedback d-block">
                                                            @error("keywordDraft.$i.$k.bid")
                                                                {{ $message }}
                                                            @enderror
                                                        </div>
                                                    @endif
                                                </td>

                                                {{-- TYPE/MATCH --}}
                                                <td>
                                                    @if ($isTarget)
                                                        <select
                                                            class="form-select form-select-sm @error("targetDraft.$i.$k.type") is-invalid @enderror"
                                                            wire:model.live="targetDraft.{{ $i }}.{{ $k }}.type">
                                                            @foreach (['ASIN_AGE_RANGE_SAME_AS', 'ASIN_BRAND_SAME_AS', 'ASIN_CATEGORY_SAME_AS', 'ASIN_EXPANDED_FROM', 'ASIN_GENRE_SAME_AS', 'ASIN_IS_PRIME_SHIPPING_ELIGIBLE', 'ASIN_PRICE_BETWEEN', 'ASIN_PRICE_GREATER_THAN', 'ASIN_PRICE_LESS_THAN', 'ASIN_REVIEW_RATING_BETWEEN', 'ASIN_REVIEW_RATING_GREATER_THAN', 'ASIN_REVIEW_RATING_LESS_THAN', 'ASIN_SAME_AS', 'KEYWORD_GROUP_SAME_AS'] as $expr)
                                                                <option value="{{ $expr }}">
                                                                    {{ $expr }}</option>
                                                            @endforeach
                                                        </select>
                                                        <div class="invalid-feedback d-block">
                                                            @error("targetDraft.$i.$k.type")
                                                                {{ $message }}
                                                            @enderror
                                                        </div>
                                                    @else
                                                        <select
                                                            class="form-select form-select-sm @error("keywordDraft.$i.$k.match") is-invalid @enderror"
                                                            wire:model.live="keywordDraft.{{ $i }}.{{ $k }}.match">
                                                            <option value="BROAD">BROAD</option>
                                                            <option value="PHRASE">PHRASE</option>
                                                            <option value="EXACT">EXACT</option>
                                                        </select>
                                                        <div class="invalid-feedback d-block">
                                                            @error("keywordDraft.$i.$k.match")
                                                                {{ $message }}
                                                            @enderror
                                                        </div>
                                                    @endif
                                                </td>

                                                {{-- DELETE --}}
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                        wire:click="{{ $isTarget ? "removeTargetRow($i, $k)" : "removeKeywordRow($i, $k)" }}">
                                                        <i class="mdi mdi-delete-outline"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-muted small">
                                                    {{ $mode ? 'No rows yet. Click Add.' : 'Select Keyword or Targeting to start.' }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-muted small mt-2">
                                Draft ID: {{ $draftId ?? '—' }} • Autosaves when you edit
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Verify / Proceed --}}
        <div class="d-flex justify-content-start gap-2 mt-3">
            @if ($draftId)
                @if (!$verifiedReady)
                    <button type="button" class="btn btn-outline-primary btn-rounded"
                        wire:click="verifyBeforeSubmit" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="verifyBeforeSubmit">
                            <i class="mdi mdi-shield-check-outline me-1"></i> Verify Inputs
                        </span>
                        <span wire:loading wire:target="verifyBeforeSubmit">
                            <i class="spinner-border spinner-border-sm me-2"></i> Verifying...
                        </span>
                    </button>

                    <button type="button" class="btn btn-light btn-rounded waves-effect waves-light"
                        wire:click="closeCreateModal">
                        Cancel
                    </button>
                @else
                    <form method="POST" action="{{ route('admin.campaigns.manual.store', ['draft' => $draftId]) }}">
                        @csrf
                        <button type="submit" class="btn btn-success btn-rounded">
                            <i class="mdi mdi-rocket-launch-outline me-1"></i> Proceed to Create
                        </button>
                    </form>

                    <button type="button" class="btn btn-light btn-rounded waves-effect waves-light"
                        wire:click="closeCreateModal">
                        Cancel
                    </button>

                    <span class="text-success small align-self-center ms-2">
                        <i class="mdi mdi-check-circle-outline"></i> Verified All
                    </span>
                @endif
            @endif
        </div>
    @endif

    {{-- Keyword upload pop up  --}}

    <div class="modal fade" id="keywordImportModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 shadow-sm">
                {{-- Header --}}
                <div class="modal-header py-3 border-0">
                    <h6 class="modal-title fw-semibold">
                        <i class="mdi mdi-file-excel-outline me-1 text-success"></i>
                        Keyword Import
                        <span class="text-muted fw-normal">
                            ({{ $importCampaignIndex === null ? 'All campaigns' : 'This campaign only' }})
                        </span>
                    </h6>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="closeKeywordImport"></button>
                </div>
                {{-- Body --}}
                <div class="modal-body pt-0">
                    {{-- Info --}}
                    <div class="alert alert-info py-2 small rounded-3 mb-3">
                        <b>Accepted format:</b>
                        CSV / XLSX with columns
                        <code>keyword</code>, <code>bid</code>, <code>match</code>
                        <br>
                        <span class="text-muted">
                            Keyword mode will be applied and targets cleared.
                            Max <b>100 rows</b>.
                        </span>
                    </div>
                    {{-- Example --}}
                    <div class="mb-3">
                        <div class="fw-semibold small mb-2">Example structure</div>

                        <div class="table-responsive-sm">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>keyword</th>
                                        <th>bid</th>
                                        <th>match</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Test Keyword</td>
                                        <td>0.75</td>
                                        <td>BROAD</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    {{-- Upload --}}
                    <div class="mb-1">
                        <label class="form-label small fw-semibold mb-1">
                            Upload file
                        </label>
                        <input type="file" class="form-control" wire:model="keywordImportFile"
                            accept=".csv,.txt,.xlsx">

                        @error('keywordImportFile')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Footer --}}
                <div class="modal-footer border-0 pt-2">
                    <div class="d-flex justify-content-between align-items-center w-100 gap-2">
                        {{-- Cancel --}}
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4"
                            data-bs-dismiss="modal" wire:click="closeKeywordImport">
                            Cancel
                        </button>
                        {{-- Import --}}
                        <button type="button" class="btn btn-success rounded-pill px-4" wire:click="importKeywords"
                            wire:loading.attr="disabled" wire:target="importKeywords,keywordImportFile">

                            <span wire:loading.remove wire:target="importKeywords">
                                <i class="mdi mdi-upload me-1"></i> Import Keywords
                            </span>
                            <span wire:loading wire:target="importKeywords">
                                <i class="spinner-border spinner-border-sm me-2"></i> Importing…
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {
                const modalEl = document.getElementById('keywordImportModal');

                window.addEventListener('open-keyword-import-modal', () => {
                    if (!modalEl) return;
                    const m = bootstrap.Modal.getOrCreateInstance(modalEl);
                    m.show();
                });

                window.addEventListener('close-keyword-import-modal', () => {
                    if (!modalEl) return;
                    const m = bootstrap.Modal.getOrCreateInstance(modalEl);
                    m.hide();
                });
            });
        </script>
    @endpush
</div>
