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
    @endphp


    @php
        // Always use the same selectedDate logic as the Livewire component
        $selectedDate = request('date', now(config('timezone.market'))->subDay()->toDateString());

        $withFilters = function (array $extra = []) use ($asin, $selectedDate) {
            $base = [
                'period' => request('period', '1d'),
                'source' => request('source', 'ads-item'),
                'date'   => $selectedDate,
            ];
            $asin = request()->input('asin', $asin);
            if (!empty($asin)) {
                $base['asins[]'] = $asin;
            }
            return array_merge($base, $extra);
        };
    @endphp

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

                    {{-- Campaign / Keyword pills --}}
                    {{-- <ul class="nav nav-pills bg-light rounded p-1 gap-1 m-0">
                        <li class="nav-item">
                            <a href="{{ route('admin.selling.adsItems.details', $asin) }}"
                                class="nav-link px-3 py-2 {{ request()->routeIs('admin.selling.adsItems.details') ? 'active' : '' }}">
                                <i class="mdi mdi-bullhorn-outline me-1"></i> Campaign
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="{{ route('admin.selling.adsItems.keywordsDetail', ['asin' => $asin]) }}"
                                class="nav-link px-3 py-2 {{ request()->routeIs('admin.selling.adsItems.keywordsDetail') ? 'active' : '' }}">
                                <i class="mdi mdi-key-outline me-1"></i> Keyword
                            </a>
                        </li>
                    </ul> --}}
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

    <livewire:selling.ads-item.ads-keyword-overview :asin="$asin" :selectedDate="$selectedDate" defer />

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


    <livewire:selling.ads-item.active-keywords-card :asin="$asin" :country="$camp->country ?? 'US'" defer />

    {{-- @dd($keywords->toArray()) --}}
    <div class="card">
        <div class="card-body">

            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-4">
                <div>
                    <h4 class="mb-0">Keywords for - <span class="text-success">{{ $asin ?? 'ASIN' }}</span></h4>
                    <div class="text-muted small">Showing keywords with their recommendations</div>
                </div>
                <form method="GET" id="filterForm" class="d-flex align-items-end gap-3 flex-wrap ms-auto">
                    @php
                        $selectedDate = now(config('timezone.market'))->subDay()->toDateString();
                        $selectedAcos = request('acos', 'all');
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
                        {{-- <select name="acos" class="form-select form-select-sm" style="width:130px"
                            onchange="this.form.submit()">
                            <option value="all" {{ $selectedAcos === 'all' ? 'selected' : '' }}>All</option>
                            <option value="30" {{ $selectedAcos === '30' ? 'selected' : '' }}>ACOS ≤ 30, spend &gt; 0
                            </option>
                            <option value="31" {{ $selectedAcos === '31' ? 'selected' : '' }}>ACOS &gt; 30, spend
                                &gt;
                                0</option>
                            <option value="0" {{ $selectedAcos === '0' ? 'selected' : '' }}>ACOS = 0, spend &gt; 0
                            </option>
                            <option value="none" {{ $selectedAcos === 'none' ? 'selected' : '' }}>ACOS = 0, spend = 0
                            </option>
                        </select> --}}

                        <select name="acos" class="form-select form-select-sm" style="width:130px"
                            onchange="this.form.submit()">
                            <option value="all" {{ $selectedAcos === 'all' ? 'selected' : '' }}>All
                            </option>

                            <option value="30" {{ $selectedAcos === '30' ? 'selected' : '' }}>ACOS <= 30, spend> 0
                            </option>
                            <option value="31" {{ $selectedAcos === '31' ? 'selected' : '' }}>ACOS >
                                30, spend > 0
                            </option>
                            <option value="0" {{ $selectedAcos === '0' ? 'selected' : '' }}>
                                ACOS = 0 , spend > 0</option>
                            <option value="none" {{ $selectedAcos === 'none' ? 'selected' : '' }}>
                                ACOS = 0 , spend = 0</option>
                        </select>
                    </div>
                    {{-- Targeting Type --}}

                    {{-- Search --}}


                    <div class="d-flex flex-column">
                        <label class="form-label small mb-1">Keyword</label>

                        <div class="position-relative" style="width:260px;">
                            <input type="text" name="search" id="search-keyword"
                                class="form-control form-control-sm pe-4" value="{{ request('search', $keywordQ ?? '') }}"
                                placeholder="Keyword name / ID" oninput="toggleClearButton('keyword')">

                            {{-- Clear icon --}}
                            <span id="clear-keyword"
                                class="position-absolute top-50 translate-middle-y end-0 me-2 text-muted cursor-pointer"
                                style="{{ request('search', $keywordQ ?? '') ? '' : 'display:none;' }}"
                                onclick="clearSearch('keyword')" title="Clear">
                                <i class="mdi mdi-close-circle"></i>
                            </span>
                        </div>
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


            @if ($keywords->count() === 0)
                <div class="alert alert-warning mb-0">
                    No keywords found for this ASIN (for selected date).
                </div>
            @else
                <div class="accordion" id="campaignAccordion">
                    @foreach ($keywords as $idx => $key)
                        @php
                            $cid = (string) ($key->campaign_id ?? '');
                            $kid = (string) ($key->keyword_id ?? '');
                            // keywords/reco are arrays in new format
                            // $keywords = collect($key->keywords ?? []);
                            $reco = collect($key->recommended ?? []);

                            $headingId = 'keyHeading_' . $cid;
                            $collapseId = 'keyCollapse_' . $cid;
                        @endphp

                        <div class="card mb-3 shadow-sm">
                            <div class="card-body py-4">

                                <div class="w-100">

                                    <!-- TOP ROW: Keyword title -->
                                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                                        <div>
                                            <div class="fs-5 fw-bold mb-1 d-flex align-items-center gap-1 flex-nowrap w-100"
                                                style="min-width:0;">
                                                <span class="campaign-name-truncate">
                                                    {{ $key->keyword ?? 'Keyword ' . $kid }}
                                                </span>

                                                <i class="mdi mdi-content-copy text-muted small cursor-pointer flex-shrink-0"
                                                    title="Copy keyword"
                                                    onclick="copyToClipboard(@js($key->keyword ?? 'Keyword ' . $kid), this)">
                                                </i>

                                                <span
                                                    class="badge rounded-pill bg-success-subtle text-success ms-1 flex-shrink-0">
                                                    Active
                                                </span>
                                            </div>

                                            <div class="d-flex gap-2 flex-wrap align-items-center">
                                                <span class="badge rounded-pill bg-info-subtle text-info">
                                                    {{ $key->campaign_types ?? 'SP' }}
                                                </span>

                                                <span class="badge rounded-pill bg-light text-dark">
                                                    {{ $key->country ?? 'US' }}
                                                </span>

                                                <span class="text-muted small d-inline-flex align-items-center gap-1">
                                                    Keyword ID:
                                                    <span class="fw-semibold">{{ $key->keyword_id ?? $kid }}</span>

                                                    <i class="mdi mdi-content-copy cursor-pointer" title="Copy keyword ID"
                                                        onclick="copyToClipboard('{{ $key->keyword_id ?? $kid }}', this)">
                                                    </i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- METRICS GRID -->
                                    <div class="row g-3 text-start">

                                        <div class="col-6 col-md-3">
                                            <div class="text-muted small">BID</div>
                                            <div class="fw-semibold fs-5">
                                                ${{ number_format((float) ($key->bid ?? 0), 2) }}
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-3">
                                            <div class="text-muted small">SPEND</div>
                                            <div class="fw-semibold fs-5">
                                                ${{ number_format((float) ($key->total_spend ?? 0), 2) }}
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-3">
                                            <div class="text-muted small">SALES</div>
                                            <div class="fw-semibold fs-5 text-success">
                                                ${{ number_format((float) ($key->total_sales ?? 0), 2) }}
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-3">
                                            <div class="text-muted small">ACOS</div>
                                            <div class="fw-semibold fs-5 text-primary">
                                                {{ $key->acos ?? 0 }} %
                                            </div>
                                        </div>


                                        <div class="col-6 col-md-3">
                                            <div class="text-muted small">UNITS</div>
                                            <div class="fw-semibold fs-5">
                                                {{ (int) ($key->purchases1d ?? 0) }}
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-3">
                                            <div class="text-muted small">SPEND 7d</div>
                                            <div class="fw-semibold fs-5">
                                                ${{ number_format((float) ($key->total_spend_7d ?? 0), 2) }}
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-3">
                                            <div class="text-muted small">SALES 7d</div>
                                            <div class="fw-semibold fs-5 text-success">
                                                ${{ number_format((float) ($key->total_sales_7d ?? 0), 2) }}
                                            </div>
                                        </div>

                                        <div class="col-6 col-md-3">
                                            <div class="text-muted small">ACOS 7d</div>
                                            <div class="fw-semibold fs-5 text-primary">
                                                {{ $key->acos_7d ?? 0 }} %
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-12">
                                            <div class="text-muted small">RECOMMENDATION</div>
                                            <div class="fw-semibold fs-5">
                                                {{ $key->recommendation ?? 'None' }}
                                            </div>
                                        </div>

                                    </div>

                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-3">
                    {{ $keywords->appends(request()->query())->links('pagination::bootstrap-5') }}
                </div>
            @endif

        </div>
    </div>

    {{-- Campaign AI Assistant (Orb in bottom right) --}}
    @livewire('ai.campaign-assistant', ['asin' => $asin])

@endsection
