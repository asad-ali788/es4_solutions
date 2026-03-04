<div wire:key="editor-root-{{ $draftId }}">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <div class="fw-semibold">
                {{-- ASIN: {{ $asin ?? '—' }} --}}
                SKUs:
                @if (!empty($sku))
                    <span class="text-muted">{{ implode(', ', $sku) }}</span>
                @else
                    —
                @endif
            </div>

            <div class="text-muted small">
                Draft #{{ $draftId }}
                • {{ strtoupper($campaignType ?? '-') }}
                • {{ strtoupper($targetingType ?? '-') }}
                @if ($lastSavedAt)
                    • Saved: {{ $lastSavedAt }}
                @endif
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-light" wire:click="saveDraft">
                <i class="mdi mdi-content-save-outline me-1"></i> Save
            </button>

            @php
                $isAuto = strtoupper((string) ($targetingType ?? '')) === 'AUTO';
                $createRoute = $isAuto
                    ? route('admin.campaigns.auto.store', $draftId)
                    : route('admin.campaigns.manual.store', $draftId);
            @endphp

            <form method="POST" action="{{ $createRoute }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-success">
                    <i class="mdi mdi-rocket-launch-outline me-1"></i> Create
                </button>
            </form>
        </div>
    </div>

    @error('draft')
        <div class="alert alert-warning py-2">{{ $message }}</div>
    @enderror

    {{-- =========================
         AUTO: ONLY CAMPAIGN LIST
         ========================= --}}
    @if (strtoupper($targetingType ?? '') === 'AUTO')
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="mb-0">Generated Campaigns (Auto)</h6>
                    <span class="text-muted small">{{ count($campaigns) }} campaigns</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:60%;">Campaign Name</th>
                                <th style="width:15%;">Country</th>
                                <th style="width:15%;">Budget</th>
                                <th style="width:10%;">State</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($campaigns as $row)
                                <tr>
                                    <td><code>{{ $row['name'] ?? '-' }}</code></td>
                                    <td class="text-muted">{{ $row['country'] ?? '-' }}</td>
                                    <td class="text-muted">{{ number_format((float) ($row['budget'] ?? 0), 2) }}</td>
                                    <td>
                                        @php $st = strtoupper((string)($row['state'] ?? '')); @endphp
                                        <span class="badge {{ $st === 'PAUSED' ? 'bg-warning' : 'bg-success' }}">
                                            {{ $st ?: '-' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted small">No campaigns found in this draft.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="text-muted small mt-2">
                    Auto campaigns don’t require keywords/targets. Proceed to Create.
                </div>
            </div>
        </div>
    @endif

    {{-- =========================
         MANUAL: FULL KEYWORD/TARGET EDITOR
         ========================= --}}
    <div class="accordion" id="kwAccordion-{{ $draftId }}">
        @foreach ($campaigns as $i => $row)
            @php
                $mode = $rowMode[$i] ?? null;
                $isKeyword = $mode === 'keyword';
                $isTarget = $mode === 'target';

                $kwCount = count($keywordDraft[$i] ?? []);
                $tarCount = count($targetDraft[$i] ?? []);
                $count = $isTarget ? $tarCount : ($isKeyword ? $kwCount : 0);

                $rows = $isTarget ? $targetDraft[$i] ?? [] : $keywordDraft[$i] ?? [];
            @endphp

            <div class="accordion-item" wire:key="camp-{{ $draftId }}-{{ $i }}">
                <h2 class="accordion-header" id="heading-{{ $draftId }}-{{ $i }}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapse-{{ $draftId }}-{{ $i }}" aria-expanded="false">

                        <div class="w-100 d-flex justify-content-between align-items-center">
                            <code>{{ $row['name'] ?? '-' }}</code>

                            <span class="badge rounded-pill bg-info-subtle text-info ms-2">
                                {{ $count }}/10
                                {{ $isTarget ? 'Targets' : ($isKeyword ? 'Keywords' : 'Items') }}
                            </span>
                        </div>
                    </button>
                </h2>

                <div id="collapse-{{ $draftId }}-{{ $i }}" class="accordion-collapse collapse"
                    wire:ignore.self data-bs-parent="#kwAccordion-{{ $draftId }}">

                    <div class="accordion-body">

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
                                    {{ $isKeyword ? 'disabled' : '' }}>
                                    <i class="mdi mdi-target me-1"></i> Targeting
                                </button>

                                <button type="button" class="btn btn-sm btn-light"
                                    wire:click="clearRowMode({{ $i }})" {{ $mode ? '' : 'disabled' }}>
                                    <i class="mdi mdi-close-circle-outline me-1"></i> Clear
                                </button>
                            </div>

                            <div>
                                <button type="button" class="btn btn-sm btn-outline-success"
                                    wire:click="{{ $isTarget ? "addTargetRow($i)" : "addKeywordRow($i)" }}"
                                    {{ $mode ? '' : 'disabled' }} {{ $count >= 10 ? 'disabled' : '' }}>
                                    <i class="mdi mdi-plus me-1"></i> Add
                                </button>
                            </div>
                        </div>

                        @if (!$mode)
                            <div class="alert alert-warning py-2 small mb-2">
                                Select <b>Keyword</b> or <b>Targeting</b> first.
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:40%;">{{ $isTarget ? 'Value' : 'Keyword' }}</th>
                                        <th style="width:15%;">Bid</th>
                                        <th style="width:35%;">{{ $isTarget ? 'Expression' : 'Match' }}</th>
                                        @if ($isTarget)
                                            <th style="width:10%;">State</th>
                                        @endif
                                        <th style="width:5%;"></th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse($rows as $k => $input)
                                        <tr
                                            wire:key="row-{{ $draftId }}-{{ $i }}-{{ $mode }}-{{ $k }}">
                                            <td>
                                                @if ($isTarget)
                                                    <input type="text"
                                                        class="form-control form-control-sm @error("targetDraft.$i.$k.expression.0.value") is-invalid @enderror"
                                                        wire:model.live.debounce.500ms="targetDraft.{{ $i }}.{{ $k }}.expression.0.value"
                                                        placeholder="Enter value">
                                                    <div class="invalid-feedback d-block" style="min-height:16px;">
                                                        @error("targetDraft.$i.$k.expression.0.value")
                                                            {{ $message }}
                                                        @enderror
                                                    </div>
                                                @else
                                                    <input type="text"
                                                        class="form-control form-control-sm @error("keywordDraft.$i.$k.text") is-invalid @enderror"
                                                        wire:model.live.debounce.500ms="keywordDraft.{{ $i }}.{{ $k }}.text"
                                                        placeholder="Enter keyword">
                                                    <div class="invalid-feedback d-block" style="min-height:16px;">
                                                        @error("keywordDraft.$i.$k.text")
                                                            {{ $message }}
                                                        @enderror
                                                    </div>
                                                @endif
                                            </td>

                                            <td>
                                                @if ($isTarget)
                                                    <input type="number" step="0.01"
                                                        class="form-control form-control-sm @error("targetDraft.$i.$k.bid") is-invalid @enderror"
                                                        wire:model.live.debounce.500ms="targetDraft.{{ $i }}.{{ $k }}.bid"
                                                        placeholder="0.75">
                                                    <div class="invalid-feedback d-block" style="min-height:16px;">
                                                        @error("targetDraft.$i.$k.bid")
                                                            {{ $message }}
                                                        @enderror
                                                    </div>
                                                @else
                                                    <input type="number" step="0.01"
                                                        class="form-control form-control-sm @error("keywordDraft.$i.$k.bid") is-invalid @enderror"
                                                        wire:model.live.debounce.500ms="keywordDraft.{{ $i }}.{{ $k }}.bid"
                                                        placeholder="0.75">
                                                    <div class="invalid-feedback d-block" style="min-height:16px;">
                                                        @error("keywordDraft.$i.$k.bid")
                                                            {{ $message }}
                                                        @enderror
                                                    </div>
                                                @endif
                                            </td>

                                            <td>
                                                @if ($isTarget)
                                                    <select
                                                        class="form-select form-select-sm @error("targetDraft.$i.$k.expression.0.type") is-invalid @enderror"
                                                        wire:model.live="targetDraft.{{ $i }}.{{ $k }}.expression.0.type">
                                                        @foreach ($expressionTypes as $expr)
                                                            <option value="{{ $expr }}">{{ $expr }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="invalid-feedback d-block" style="min-height:16px;">
                                                        @error("targetDraft.$i.$k.expression.0.type")
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
                                                    <div class="invalid-feedback d-block" style="min-height:16px;">
                                                        @error("keywordDraft.$i.$k.match")
                                                            {{ $message }}
                                                        @enderror
                                                    </div>
                                                @endif
                                            </td>

                                            @if ($isTarget)
                                                <td>
                                                    <select
                                                        class="form-select form-select-sm @error("targetDraft.$i.$k.state") is-invalid @enderror"
                                                        wire:model.live="targetDraft.{{ $i }}.{{ $k }}.state">
                                                        <option value="ENABLED">ENABLED</option>
                                                        <option value="PAUSED">PAUSED</option>
                                                        <option value="ARCHIVED">ARCHIVED</option>
                                                    </select>
                                                    <div class="invalid-feedback d-block" style="min-height:16px;">
                                                        @error("targetDraft.$i.$k.state")
                                                            {{ $message }}
                                                        @enderror
                                                    </div>
                                                </td>
                                            @endif

                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    wire:click="{{ $isTarget ? "removeTargetRow($i, $k)" : "removeKeywordRow($i, $k)" }}">
                                                    <i class="mdi mdi-delete-outline"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $isTarget ? 5 : 4 }}" class="text-muted small">
                                                {{ $mode ? 'No rows yet. Click Add.' : 'Select Keyword or Targeting to start.' }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="text-muted small mt-2">
                            Autosaves while typing
                        </div>

                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
