<div>
    @php
        $tz = config('timezone.market', 'America/Los_Angeles');
    @endphp
    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-2">
                <div class="card-body">

                    <div class="row">

                        {{-- TITLE --}}
                        <div class="col-lg-4">
                            <p class="mb-2">Active Campaigns</p>
                        </div>

                        {{-- COUNTS --}}
                        <div class="align-self-center col-lg-4">
                            <div class="text-lg-center mt-4 mt-lg-0">
                                    <div class="row">

                                        {{-- SP --}}
                                        <div class="col-4">
                                            <p class="text-muted mb-1">SP</p>
                                            <h5 class="mb-0">{{ $spEnabled }}</h5>
                                            <small class="text-muted d-block">
                                                {{ $spLastUpdated ? \Carbon\Carbon::parse($spLastUpdated)->setTimezone($tz)->format('h:i A T') : '—' }}
                                            </small>
                                        </div>

                                        {{-- SB --}}
                                        <div class="col-4">
                                            <p class="text-muted mb-1">SB</p>
                                            <h5 class="mb-0">{{ $sbEnabled }}</h5>
                                            <small class="text-muted d-block">
                                                {{ $sbLastUpdated ? \Carbon\Carbon::parse($sbLastUpdated)->setTimezone($tz)->format('h:i A T') : '—' }}
                                            </small>
                                        </div>

                                        {{-- SD --}}
                                        <div class="col-4">
                                            <p class="text-muted mb-1">SD</p>
                                            <h5 class="mb-0">{{ $sdEnabled }}</h5>
                                            <small class="text-muted d-block">
                                                {{ $sdLastUpdated ? \Carbon\Carbon::parse($sdLastUpdated)->setTimezone($tz)->format('h:i A T') : '—' }}
                                            </small>
                                        </div>

                                    </div>

                            </div>
                        </div>

                        {{-- ACTIONS --}}
                        <div class="d-none d-lg-block col-lg-4">
                            <div class="float-end d-flex align-items-center gap-2">

                                {{-- Refresh --}}
                                <button type="button" class="btn btn-light btn-rounded" wire:click="refreshCountsAction"
                                    wire:loading.attr="disabled" wire:target="refreshCountsAction" title="Refresh">
                                    <i class="mdi mdi-refresh" wire:loading.class="mdi-spin"
                                        wire:target="refreshCountsAction"></i>
                                </button>

                                {{-- Create --}}
                                <button type="button" class="btn btn-success btn-rounded" wire:click="openCreateModal">
                                    <i class="mdi mdi-plus me-1"></i>
                                    Create Campaign
                                </button>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($showCreateModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.5);">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title">Generate Campaign Names</h5>
                        <button type="button" class="btn-close" wire:click="closeCreateModal"></button>
                    </div>

                    <div class="modal-body">
                        {{-- Title Preview (no input boxes) --}}
                        <div class="mb-3">
                            <div class="text-muted small mb-1">Preview</div>
                            <div class="bg-light rounded px-3 py-2 d-flex align-items-center justify-content-between">
                                <code class="mb-0">
                                    {{ $asin }}
                                    <span class="text-muted">________________</span>
                                    SYSTEM_{{ $pstDate ?? \Carbon\Carbon::now('America/Los_Angeles')->format('d-m-Y') }}_CMP_<span
                                        class="text-muted">__</span>
                                </code>

                                <span class="badge rounded-pill bg-info-subtle text-info">
                                    {{ strtoupper($campaignType ?? 'SP') }}
                                </span>
                            </div>
                        </div>

                        {{-- One-line inputs --}}
                        {{-- Inputs (3 per row desktop) --}}
                        <div class="row g-2 align-items-end">

                            {{-- SKU --}}
                            <div class="col-12 col-md-8">
                                <label class="form-label mb-1">SKU</label>
                                <select class="form-select" wire:model.defer="selectedSku">
                                    @forelse ($skuOptions as $sku)
                                        <option value="{{ $sku }}">{{ $sku }}</option>
                                    @empty
                                        <option value="">No SKU found</option>
                                    @endforelse
                                </select>
                                @error('selectedSku')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            {{-- Count --}}
                            <div class="col-12 col-md-3">
                                <label class="form-label mb-1">Count</label>
                                <input type="number" class="form-control" wire:model.defer="campaignCount"
                                    min="1" max="50">
                                @error('campaignCount')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            {{-- Total Budget --}}
                            <div class="col-12 col-md-3">
                                <label class="form-label mb-1">Total Budget</label>
                                <input type="number" step="0.01" class="form-control" wire:model.defer="totalBudget"
                                    min="0.01">
                                @error('totalBudget')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            {{-- Targeting --}}
                            <div class="col-12 col-md-3">
                                <label class="form-label mb-1">Targeting</label>
                                <select class="form-select" wire:model.defer="targetingType">
                                    <option value="AUTO" selected>AUTO</option>
                                    {{-- <option value="MANUAL" disabled>MANUAL</option> --}}
                                </select>
                                @error('targetingType')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            {{-- Match --}}
                            <div class="col-12 col-md-3">
                                <label class="form-label mb-1">Match</label>
                                <select class="form-select" wire:model.defer="matchType">
                                    <option value="BROAD">BROAD</option>
                                    <option value="PHRASE">PHRASE</option>
                                    <option value="EXACT">EXACT</option>
                                </select>
                                @error('matchType')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            {{-- Start Index --}}
                            {{-- <div class="col-12 col-md-3">
                                <label class="form-label mb-1">Start (CMP)</label>
                                <input type="number" class="form-control" wire:model.defer="startIndex"
                                    min="1" max="999">
                                @error('startIndex')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div> --}}

                            {{-- Generate button (full width mobile, aligns right desktop) --}}
                            <div class="col-12 col-md-3 d-grid d-md-flex justify-content-md-end mt-1">
                                <button type="button" class="btn btn-success btn-rounded waves-effect waves-light"
                                    wire:click="generateCampaignNames" wire:loading.attr="disabled"
                                    wire:target="generateCampaignNames">
                                    <span wire:loading.remove wire:target="generateCampaignNames">Generate</span>
                                    <span wire:loading wire:target="generateCampaignNames">
                                        <span class="spinner-border spinner-border-sm me-2"></span> Generating
                                    </span>
                                </button>
                            </div>
                        </div>

                        {{-- Generated list --}}
                        @if (!empty($generatedCampaigns))
                            <hr class="my-3">

                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="fw-semibold">Generated Campaigns</div>
                                <div class="text-muted small">
                                    Budget per campaign: <span class="fw-semibold">
                                        {{ number_format($generatedCampaigns[0]['budget'] ?? 0, 2) }}
                                    </span>
                                </div>
                            </div>

                            <div class="list-group">
                                @foreach ($generatedCampaigns as $row)
                                    <div class="list-group-item d-flex align-items-center justify-content-between">
                                        <code class="mb-0 text-break">{{ $row['name'] }}</code>
                                        <span
                                            class="badge rounded-pill bg-success-subtle text-success ms-2 px-3 py-2 fs-6 fw-semibold">
                                            ${{ number_format($row['budget'], 2) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Submit to normal controller --}}
                            <form method="POST" action="{{ route('admin.campaigns.generated.store') }}"
                                class="mt-3">
                                @csrf

                                {{-- locked meta --}}
                                <input type="hidden" name="asin" value="{{ $asin }}">
                                <input type="hidden" name="sku" value="{{ $selectedSku }}">
                                <input type="hidden" name="country" value="{{ $country }}">
                                <input type="hidden" name="campaign_type" value="{{ $campaignType ?? 'SP' }}">
                                <input type="hidden" name="total_budget" value="{{ $totalBudget }}">
                                <input type="hidden" name="targeting_type" value="{{ $targetingType }}">
                                <input type="hidden" name="match_type" value="{{ $matchType }}">
                                <input type="hidden" name="pst_date" value="{{ $pstDate }}">

                                {{-- array payload --}}
                                @foreach ($generatedCampaigns as $i => $row)
                                    <input type="hidden" name="campaigns[{{ $i }}][name]"
                                        value="{{ $row['name'] }}">
                                    <input type="hidden" name="campaigns[{{ $i }}][budget]"
                                        value="{{ $row['budget'] }}">
                                @endforeach

                                <div class="d-flex gap-2">
                                    <button type="submit"
                                        class="btn btn-success btn-rounded waves-effect waves-light">
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

                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
