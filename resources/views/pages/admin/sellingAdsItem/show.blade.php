@extends('layouts.app')

@section('content')
    <style>
        .accordion-button {
            background-color: #fff;
        }

        .accordion-button:not(.collapsed) {
            background-color: #f8f9fb;
        }

        .accordion-button::after {
            transform: scale(1.2);
        }

        .accordion-button .mdi-content-copy {
            opacity: 0;
        }

        .accordion-button:hover .mdi-content-copy {
            opacity: 1;
        }
    </style>
    @php
        $flagMap = config('flagmap');
        // Pick which segment you are showing (now: SP MANUAL)
        $type = request('campaign', 'SP');
        $mode = request('sp_targeting_type', 'MANUAL');
        // DRY: Use a robust withFilters helper that always preserves all query params (including date)
        $withFilters = function (array $extra = []) use ($asin) {
            $selectedDate = request('date', now(config('timezone.market'))->subDay()->toDateString());
            $base = [
                'period' => request('period', '1d'),
                'source' => request('source', 'ads-item'),
                'date'   => $selectedDate,
            ];
            $asin = request()->input('asin', $asin);
            if (!empty($asin)) {
                $base['asins[]'] = $asin;
            }
            // Merge all current query params (except for page) to preserve filters
            $current = request()->except(['page']);
            return array_merge($base, $current, $extra);
        };
    @endphp

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between flex-wrap gap-3">

                {{-- Left: Title + Pills --}}
                <div class="d-flex align-items-center flex-wrap gap-3">

                    <h4 class="mb-0 d-flex align-items-center gap-2">
                        <span>Selling Ads Item Dashboard</span>
                        <span class="text-muted">ASIN -</span>
                        <span class="text-primary">{{ $asin ?? 'N/A' }}</span>
                    </h4>
                </div>

                {{-- Right: Breadcrumb --}}
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            <a href="{{ route('admin.selling.adsItems.index') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Selling Ads Items
                            </a>
                        </li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <div class="card mb-1">
        <div class="card-body m-1 p-1">
            <div class="d-flex flex-column flex-md-row align-items-md-center">
                <ul role="tablist" class="nav nav-tabs nav-tabs-custom card-header-tabs flex-column flex-sm-row">
                    <li class="nav-item">
                        <a href="{{ route('admin.selling.adsItems.details', $asin) }}"
                            class="nav-link w-100 w-sm-auto {{ request()->routeIs('admin.selling.adsItems.details') ? 'active' : '' }}">
                            Campaign Dashboard
                        </a>
                    </li>
                    <hr class="d-sm-none my-0">
                    <li class="nav-item">
                        <a href="{{ route('admin.selling.adsItems.keywordsDetail', ['asin' => $asin]) }}"
                            class="nav-link w-100 w-sm-auto  {{ request()->routeIs('admin.selling.adsItems.keywordsDetail') ? 'active' : '' }}">
                            Keyword Dashboard
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <livewire:selling.ads-item.sales-data :asin="$asin" defer />

    <livewire:selling.ads-item.ads-overview :asin="$asin" :selectedDate="$selectedDate" defer />

    @if (session('toast_success'))
        <script>
            window.addEventListener('load', () => {
                @foreach (session('toast_success') as $msg)
                    showToast('success', @json($msg), 10000);
                @endforeach
            });
        </script>
    @endif

    @if (session('toast_errors'))
        <script>
            window.addEventListener('load', () => {
                @foreach (session('toast_errors') as $msg)
                    showToast('error', @json($msg), 10000);
                @endforeach
            });
        </script>
    @endif


    <livewire:selling.ads-item.active-campaigns-card :asin="$asin" :country="$camp->country ?? 'US'" defer  />

    {{-- =========================
        Campaigns (Paginated)
    ========================== --}}

    <div class="card">
        <div class="card-body">

            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
                <div>
                    <h4 class="mb-0">Campaigns for - <span class="text-success">{{ $asin ?? 'ASIN' }}</span></h4>
                    <div class="text-muted small">Showing campaigns with their keywords & recommendations</div>
                </div>
                <form method="GET" id="filterForm" class="d-flex align-items-end gap-3 flex-wrap ms-auto">
                    @php
                        $selectedDate = now(config('timezone.market'))->subDay()->toDateString();
                        $selectedAcos = request('acos', 'all');
                        $selectedTargetType = request('sp_targeting_type', 'MANUAL');
                    @endphp
                    {{-- Date --}}
                    <div class="d-flex flex-column">
                        <label class="form-label small mb-1">Date</label>
                        <input type="date" name="date" class="form-control form-control-sm"
                            value="{{ request('date', $selectedDate) }}" max="{{ $selectedDate }}" style="width:130px"
                            onchange="this.form.submit()" onclick="this.showPicker()">
                    </div>
                    {{-- ACOS --}}
                    <div class="d-flex flex-column">
                        <label class="form-label small mb-1">ACOS</label>
                        <select name="acos" class="form-select form-select-sm" style="width:130px"
                            onchange="this.form.submit()">
                            <option value="all" {{ $selectedAcos === 'all' ? 'selected' : '' }}>All</option>
                            <option value="30" {{ $selectedAcos === '30' ? 'selected' : '' }}>ACOS ≤ 30, spend &gt; 0</option>
                            <option value="31" {{ $selectedAcos === '31' ? 'selected' : '' }}>ACOS &gt; 30, spend &gt; 0</option>
                            <option value="0" {{ $selectedAcos === '0' ? 'selected' : '' }}>ACOS = 0, spend &gt; 0</option>
                            <option value="none" {{ $selectedAcos === 'none' ? 'selected' : '' }}>ACOS = 0, spend = 0</option>
                        </select>
                    </div>
                    {{-- Targeting Type --}}
                    <div class="d-flex flex-column">
                        <label class="form-label small mb-1">Targeting Type</label>
                        <select class="form-select form-select-sm" name="sp_targeting_type"
                            onchange="document.getElementById('filterForm').submit()">
                            <option value="all" {{ $selectedTargetType === 'all' ? 'selected' : '' }}>All</option>
                            <option value="AUTO" {{ $selectedTargetType === 'AUTO' ? 'selected' : '' }}>AUTO</option>
                            <option value="MANUAL" {{ $selectedTargetType === 'MANUAL' ? 'selected' : '' }}>MANUAL</option>
                        </select>
                    </div>
                    {{-- Search --}}
                    <div class="d-flex flex-column">
                        <label class="form-label small mb-1">Campaign</label>
                        <input type="text" name="search" class="form-control form-control-sm"
                            value="{{ request('search', $campaignQ ?? '') }}" placeholder="Campaign name / ID"
                            style="width:260px">
                    </div>
                    {{-- Hidden --}}
                    <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">
                    {{-- Buttons --}}
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-primary" type="submit">
                            <i class="mdi mdi-magnify"></i>
                        </button>
                        <a href="{{ url()->current() }}" class="btn btn-sm btn-light">
                            <i class="mdi mdi-refresh"></i>
                        </a>
                    </div>
                </form>
            </div>


            @if ($campaigns->count() === 0)
                <div class="alert alert-warning mb-0">
                    No campaigns found for this ASIN (for selected date).
                </div>
            @else
                <div class="accordion" id="campaignAccordion">
                    @foreach ($campaigns as $idx => $camp)
                        @php
                            $cid = (string) ($camp->campaign_id ?? '');
                            // keywords/reco are arrays in new format
                            $keywords = collect($camp->keywords ?? []);
                            $reco = collect($camp->recommended ?? []);

                            $headingId = 'campHeading_' . $cid;
                            $collapseId = 'campCollapse_' . $cid;
                        @endphp

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="{{ $headingId }}">
                                <button class="accordion-button collapsed py-4 acc-btn-theme" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#campCollapse_{{ $cid }}"
                                    aria-expanded="false" aria-controls="campCollapse_{{ $cid }}">

                                    <div class="w-100">

                                        <!-- TOP ROW: Campaign title -->
                                        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                                            <div>
                                                <div class="fs-5 fw-bold mb-1 d-flex align-items-center gap-1 flex-nowrap w-100"
                                                    style="min-width:0;">
                                                    <span class="campaign-name-truncate">
                                                        {{ $camp->campaign_name ?? 'Campaign ' . $cid }}
                                                    </span>
                                                    <i class="mdi mdi-content-copy text-muted small cursor-pointer flex-shrink-0"
                                                        title="Copy campaign name"
                                                        onclick="event.stopPropagation(); event.preventDefault(); copyToClipboard(@js($camp->campaign_name ?? 'Campaign ' . $cid), this)">
                                                    </i>
                                                    <span
                                                        class="badge rounded-pill bg-success-subtle text-success ms-1 flex-shrink-0">
                                                        Active
                                                    </span>
                                                </div>
                                                <div class="d-flex gap-2 flex-wrap align-items-center">
                                                    <span class="badge rounded-pill bg-info-subtle text-info">
                                                        {{ $camp->campaign_types ?? 'SP' }}
                                                    </span>

                                                    <span class="badge rounded-pill bg-light text-dark">
                                                        {{ $camp->country ?? 'US' }}
                                                    </span>

                                                    <span class="text-muted small d-inline-flex align-items-center gap-1">
                                                        Campaign ID:
                                                        <span class="fw-semibold">{{ $cid }}</span>

                                                        {{-- Copy campaign ID --}}
                                                        <i class="mdi mdi-content-copy cursor-pointer"
                                                            title="Copy campaign ID"
                                                            onclick="event.stopPropagation(); event.preventDefault(); copyToClipboard('{{ $cid }}', this)"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- METRICS GRID -->
                                        <div class="row g-3 text-start">

                                            <div class="col-6 col-md-3">
                                                <div class="text-muted small">BUDGET</div>
                                                <div class="fw-semibold fs-5">
                                                    ${{ number_format((float) ($camp->total_daily_budget ?? 0), 2) }}
                                                </div>
                                            </div>

                                            <div class="col-6 col-md-3">
                                                <div class="text-muted small">SPEND</div>
                                                <div class="fw-semibold fs-5">
                                                    ${{ number_format((float) ($camp->total_spend ?? 0), 2) }}
                                                </div>
                                            </div>

                                            <div class="col-6 col-md-3">
                                                <div class="text-muted small">SALES</div>
                                                <div class="fw-semibold fs-5 text-success">
                                                    ${{ number_format((float) ($camp->total_sales ?? 0), 2) }}
                                                </div>
                                            </div>

                                            <div class="col-6 col-md-3">
                                                <div class="text-muted small">ACOS</div>
                                                <div class="fw-semibold fs-5 text-primary">

                                                    {{ $camp->acos ?? 0 }} %
                                                </div>
                                            </div>

                                            <div class="col-6 col-md-3">
                                                <div class="text-muted small">KEYWORDS</div>
                                                <div class="fw-semibold fs-5">
                                                    {{ count($camp->keywords ?? []) }}
                                                </div>
                                            </div>

                                            <div class="col-6 col-md-3">
                                                <div class="text-muted small">RECOMMENDED</div>
                                                <div class="fw-semibold fs-5">
                                                    {{ count($camp->recommended ?? []) }}
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </button>

                            </h2>

                            <div id="{{ $collapseId }}" class="accordion-collapse collapse"
                                aria-labelledby="{{ $headingId }}" data-bs-parent="#campaignAccordion">
                                <div class="accordion-body">

                                    {{-- Tabs --}}
                                    <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" data-bs-toggle="tab"
                                                data-bs-target="#pane-keywords-{{ $cid }}" type="button"
                                                role="tab">
                                                Keywords
                                            </button>
                                        </li>

                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" data-bs-toggle="tab"
                                                data-bs-target="#pane-reco-{{ $cid }}" type="button"
                                                role="tab">
                                                Recommended
                                            </button>
                                        </li>
                                    </ul>

                                    <div class="tab-content">

                                        {{-- Keywords --}}
                                        <div class="tab-pane fade show active" id="pane-keywords-{{ $cid }}"
                                            role="tabpanel">
                                            <div class="table-responsive">
                                                <table
                                                    class="table table-sm table-hover align-middle table-nowrap w-100 mb-0 ">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th style="min-width:260px;">Keyword</th>
                                                            <th>Keyword Id</th>
                                                            <th>Bid</th>
                                                            <th>Spend</th>
                                                            <th>Sales</th>
                                                            <th>Click</th>
                                                            <th>Impressions</th>
                                                            <th>State</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($keywords as $k)
                                                            <tr>
                                                                <td class="fw-medium">
                                                                    {{ data_get($k, 'keyword', '—') }}
                                                                </td>
                                                                <td>
                                                                    <span class="text-muted">
                                                                        {{ data_get($k, 'keyword_id', '—') }}
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <span class="text-muted">
                                                                        {{ data_get($k, 'bid', '—') }}
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <span class="text-muted">
                                                                        {{ data_get($k, 'total_spend', '—') }}
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <span class="text-muted">
                                                                        {{ data_get($k, 'total_sales', '—') }}
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <span class="text-muted">
                                                                        {{ data_get($k, 'clicks', '—') }}
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    <span class="text-muted">
                                                                        {{ data_get($k, 'impressions', '—') }}
                                                                    </span>
                                                                </td>
                                                                <td>
                                                                    @php
                                                                        $state = data_get($k, 'sp_state');
                                                                    @endphp

                                                                    @if ($state === 'ENABLED')
                                                                        <span class="badge bg-success">Enabled</span>
                                                                    @elseif($state === 'PAUSED')
                                                                        <span
                                                                            class="badge bg-warning text-dark">Paused</span>
                                                                    @else
                                                                        <span class="text-muted">—</span>
                                                                    @endif
                                                                </td>

                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="2" class="text-muted">No keywords found.
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        {{-- Recommendations --}}
                                        <div class="tab-pane fade" id="pane-reco-{{ $cid }}" role="tabpanel">
                                            <div class="row align-items-center mb-2">
                                                <div class="col">
                                                    <div class="text-muted small">
                                                        Creates all recommended keywords for this campaign
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <form action="{{ route('admin.selling.adsItems.createKeywords') }}"
                                                        method="post"
                                                        onsubmit="return confirm('Are you sure you want to create recommended keywords?');">
                                                        @csrf
                                                        <input type="hidden" name="campaign_id"
                                                            value="{{ $cid }}" required>
                                                        <input type="hidden" name="country"
                                                            value="{{ $camp->country ?? 'US' }}" required>
                                                        <input type="hidden" name="campaign_type"
                                                            value="{{ $camp->campaign_types ?? 'SP' }}" required>
                                                        <button href="{{ request() }}" type="submit"
                                                            @if (count($camp->recommended ?? []) > 0) @else disabled data-bs-toggle="tooltip"
                                                            data-bs-placement="bottom" title="No Recommended keywords Found" @endif
                                                            class="btn btn-success btn-sm waves-effect waves-light btn-rounded">
                                                            Create Keywords
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            <div class="table-responsive">
                                                <table
                                                    class="table table-sm table-hover align-middle table-nowrap w-100 mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th style="min-width:260px;">KEYWORD</th>
                                                            <th>MATCH TYPE</th>
                                                            <th>BID</th>
                                                            <th>AD GROUP</th>
                                                            <th>UPDATED</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($reco as $r)
                                                            <tr>
                                                                <td class="fw-medium">{{ data_get($r, 'keyword', '—') }}
                                                                </td>
                                                                <td>
                                                                    <span class="badge bg-primary-subtle text-primary">
                                                                        {{ data_get($r, 'match_type', '—') }}
                                                                    </span>
                                                                </td>
                                                                <td>{{ data_get($r, 'bid', '—') }}</td>
                                                                <td>{{ data_get($r, 'ad_group_id', '—') }}</td>
                                                                <td class="text-muted">
                                                                    @if (!empty(data_get($r, 'updated_at')))
                                                                        {{ \Carbon\Carbon::parse(data_get($r, 'updated_at'))->timezone(config('timezone.market'))->format('Y-m-d H:i') }}
                                                                        PST
                                                                    @else
                                                                        —
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="5" class="text-muted">No recommendations
                                                                    found.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                    </div>

                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-3">
                    {{ $campaigns->appends(request()->query())->links('pagination::bootstrap-5') }}
                </div>
            @endif

        </div>
    </div>

    {{-- Campaign AI Assistant (Orb in bottom right) --}}
    @livewire('ai.campaign-assistant', ['asin' => $asin])

@endsection
