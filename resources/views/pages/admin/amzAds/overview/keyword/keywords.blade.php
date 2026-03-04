@extends('layouts.app')

@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">
                    Amazon Ads - Keyword Overview
                </h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        @php
                            $asins = request('asins', []);
                            $asin = is_array($asins) ? $asins[0] ?? null : $asins;
                        @endphp
                        <li class="breadcrumb-item active">
                            @if (request('source') === 'ads-item' && $asin)
                                <a
                                    href="{{ route('admin.selling.adsItems.details', ['asin' => $asin, 'period' => request('period', '1d')]) }}">
                                    <i class="bx bx-left-arrow-alt me-1"></i>
                                    Back to Selling Ads Item Dashboard
                                </a>
                            @else
                                <a
                                    href="{{ route('admin.ads.overview.keywordDashboard', ['period' => request('period', '7d')]) }}">
                                    <i class="bx bx-left-arrow-alt me-1"></i>
                                    Back to Ads Keyword Dashboard
                                </a>
                            @endif
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <!-- FILTERS -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <form method="GET" action="{{ route('admin.ads.overview.keywordOverview') }}" id="filterForm"
                        class="row g-2 mb-3">
                        {{-- Preserve sort_acos filter --}}
                        <input type="hidden" name="sort_acos" value="{{ request('sort_acos', '') }}">
                        <input type="hidden" name="sort_direction" value="{{ request('sort_direction', '') }}">

                        {{-- Search --}}
                        <x-elements.search-box />

                        {{-- Country --}}
                        <x-elements.country-select :countries="['us' => 'US', 'ca' => 'CA']" />

                        {{-- Campaign Type --}}
                        <x-elements.campaign-select :campaigns="['SP' => 'SP', 'SB' => 'SB']" />

                        {{-- Keyword State --}}
                        <div class="col-6 col-md-auto">
                            <div class="form-floating">
                                <select class="form-select custom-dropdown-small" name="keyword_state"
                                    onchange="this.form.submit()">
                                    @php $ks = request('keyword_state','all'); @endphp
                                    <option value="all" {{ $ks === 'all' ? 'selected' : '' }}>All</option>
                                    <option value="ENABLED" {{ $ks === 'ENABLED' ? 'selected' : '' }}>ENABLED</option>
                                    <option value="PAUSED" {{ $ks === 'PAUSED' ? 'selected' : '' }}>PAUSED</option>
                                    <option value="na" {{ $ks === 'na' ? 'selected' : '' }}>N/A</option>
                                </select>
                                <label>Keyword State</label>
                            </div>
                        </div>

                        {{-- Period --}}
                        <div class="col-6 col-md-auto">
                            <div class="form-floating">
                                @php $period = request('period','all'); @endphp
                                <select class="form-select custom-dropdown-small" name="period"
                                    onchange="this.form.submit()">
                                    <option value="all" {{ $period === 'all' ? 'selected' : '' }}>All</option>
                                    <option value="1d" {{ $period === '1d' ? 'selected' : '' }}>Yesterday (1d)</option>
                                    <option value="7d" {{ $period === '7d' ? 'selected' : '' }}>7 Days</option>
                                    <option value="14d" {{ $period === '14d' ? 'selected' : '' }}>14 Days</option>
                                    <option value="30d" {{ $period === '30d' ? 'selected' : '' }}>30 Days</option>
                                </select>
                                <label>Period</label>
                            </div>
                        </div>

                        {{-- ACOS --}}
                        @php
                            $asins = (array) request('asins', []);
                        @endphp
                        <input type="hidden" name="acos" value="{{ request('acos') }}">

                        <div class="col-6 col-md-auto">
                            <div class="form-floating">
                                <select class="form-select custom-dropdown-small" name="acos"
                                    onchange="document.getElementById('filterForm').submit()">
                                    @php $selectedAcos = request('acos', 'all'); @endphp
                                    <option value="all" {{ $selectedAcos === 'all' ? 'selected' : '' }}>All
                                    </option>

                                    <option value="30" {{ $selectedAcos === '30' ? 'selected' : '' }}>ACOS <= 30,
                                            spend> 0
                                    </option>
                                    <option value="31" {{ $selectedAcos === '31' ? 'selected' : '' }}>ACOS >
                                        30, spend > 0
                                    </option>
                                    <option value="0" {{ $selectedAcos === '0' ? 'selected' : '' }}>
                                        ACOS = 0 , spend > 0</option>
                                    <option value="none" {{ $selectedAcos === 'none' ? 'selected' : '' }}>
                                        ACOS = 0 , spend = 0</option>
                                </select>
                                <label for="period">ACoS</label>
                            </div>
                        </div>

                        {{-- Date --}}
                        <div class="col-6 col-md-auto">
                            <div class="form-floating">
                                @php $maxDate = now(config('timezone.market'))->subDay()->toDateString(); @endphp
                                <input type="date" class="form-control" name="date"
                                    value="{{ request('date', $maxDate) }}" max="{{ $maxDate }}"
                                    onchange="this.form.submit()" onclick="this.showPicker()">
                                <label>Report Date</label>
                            </div>
                        </div>

                        {{-- Preserve ASINs --}}
                        @foreach ((array) request('asins', []) as $asin)
                            <input type="hidden" name="asins[]" value="{{ $asin }}">
                        @endforeach


                        <div class="col-6 col-lg-auto ms-lg-auto d-flex justify-content-end">
                            <div class="dropdown">
                                <button class="btn btn-light w-100 d-flex align-items-center justify-content-center menuBtn"
                                    type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="mdi mdi-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end mt-2" aria-labelledby="dropdownMenuButton1"
                                    style="--bs-dropdown-item-padding-y: 0.4rem;">
                                    <small class="text-muted fw-semibold ms-3">More Actions</small>
                                    <li>
                                        <a class="dropdown-item d-flex align-items-center" href="#"
                                            data-bs-toggle="modal" data-bs-target="#columnFilterPopupModal">
                                            <i class="bx bx-columns font-size-18 text-primary me-1"></i>
                                            Show / Hide Columns
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </form>

                    @php
                        $periodConfig = [
                            '1d' => [
                                'label' => '1d',
                                'spend' => 'total_spend',
                                'sales' => 'total_sales',
                                'purchase' => 'purchases1d',
                                'acos' => 'acos',
                                'cell' => 'table-light',
                            ],
                            '7d' => [
                                'label' => '7d',
                                'spend' => 'total_spend_7d',
                                'sales' => 'total_sales_7d',
                                'purchase' => 'purchases1d_7d',
                                'acos' => 'acos_7d',
                                'cell' => 'table-warning',
                            ],
                            '14d' => [
                                'label' => '14d',
                                'spend' => 'total_spend_14d',
                                'sales' => 'total_sales_14d',
                                'purchase' => 'purchases1d_14d',
                                'acos' => 'acos_14d',
                                'cell' => 'table-success',
                            ],
                            '30d' => [
                                'label' => '30d',
                                'spend' => 'total_spend_30d',
                                'sales' => 'total_sales_30d',
                                'purchase' => 'purchases7d_30d',
                                'acos' => 'acos_30d',
                                'cell' => 'table-primary',
                            ],
                        ];

                        $activePeriods = $period === 'all' ? array_keys($periodConfig) : [$period];
                    @endphp
                    {{-- Column Visibility --}}
                    <x-elements.column-visibility :columns="[
                        'keyword_id' => 'Keyword ID',
                        'campaign_name' => 'Campaign Name',
                        'k_campaign_id' => 'Campaign ID',
                        'campaign_type' => 'Campaign Type',
                        'asin' => 'ASIN',
                        'date' => 'Date',
                        'country' => 'Country',
                        'bid_start' => 'Bid Start',
                        'bid_suggest' => 'Bid Suggestion',
                        'bid_end' => 'Bid End',
                        'suggested_bid' => 'Suggested Budget',
                        'ai_recommendation' => 'Ai Recommendation ✨',
                    ]" :default-visible="['country', 'campaign_type', 'asin', 'ai_recommendation']" />
                    {{--  Column Visibility --}}

                    <!-- TABLE -->
                    <div class="table-responsive custom-sticky-wrapper">
                        <table class="table align-middle table-nowrap custom-sticky-table">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th data-col="keyword_id">Keyword Id</th>
                                    <th data-col="campaign_name">Campaign Name</th>
                                    <th>Keyword</th>
                                    <th data-col="k_campaign_id">Campaign Id</th>
                                    <th data-col="asin">ASIN</th>
                                    <th data-col="campaign_type">Type</th>
                                    <th data-col="date">Date</th>
                                    <th data-col="country">Country</th>

                                    @foreach ($activePeriods as $p)
                                        <th>Spend {{ $periodConfig[$p]['label'] }}</th>
                                        <th>Sales {{ $periodConfig[$p]['label'] }}</th>
                                        <th>Purchase {{ $periodConfig[$p]['label'] }}</th>
                                        @php
                                            $isActive = request('sort_acos') === $periodConfig[$p]['acos'];
                                            $isDesc = request('sort_direction') === 'desc';
                                            $sortClass = $isActive
                                                ? ($isDesc
                                                    ? 'sorting sorting_desc'
                                                    : 'sorting sorting_asc')
                                                : 'sorting';
                                            $params = array_merge(
                                                request()->except(['sort_acos', 'sort_direction', 'page']),
                                                [
                                                    'sort_acos' => $periodConfig[$p]['acos'],
                                                    'sort_direction' => $isActive && !$isDesc ? 'desc' : 'asc',
                                                ],
                                            );
                                            $url = url()->current() . '?' . http_build_query($params);
                                        @endphp
                                        <th colspan="1" class="{{ $sortClass }} pointer"
                                            onclick="window.location='{{ $url }}'">
                                            <div class="select-none d-flex align-items-center">
                                                ACoS {{ $periodConfig[$p]['label'] }}
                                                <span class="ms-1">
                                                    <i
                                                        class="mdi mdi-arrow-up-bold{{ $isActive && !$isDesc ? ' text-dark' : ' text-muted' }}"></i>
                                                    <i
                                                        class="mdi mdi-arrow-down-bold{{ $isActive && $isDesc ? ' text-dark' : ' text-muted' }}"></i>
                                                </span>
                                            </div>
                                        </th>
                                    @endforeach
                                    <th data-col="bid_start">Bid Start</th>
                                    <th data-col="bid_suggest">Bid Suggestion</th>
                                    <th data-col="bid_end">Bid End</th>
                                    <th>Status</th>
                                    <th>Current Bid</th>
                                    <th class="wide-col" data-col="ai_recommendation">
                                        <span class="ai-gradient-text">AI Suggested Bid ✨</span>
                                    </th>
                                    <th data-col="suggested_bid">Suggested Bid</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($keywords as $kw)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td data-col="keyword_id">{{ $kw->keyword_id }}</td>

                                        @php
                                            $campaignName = $kw->campaign_types === 'SP' ? $kw->c_name : $kw->sb_c_name;
                                        @endphp
                                        <td class="ellipsis-text" data-col="campaign_name">{{ $campaignName }}</td>
                                        <td>{{ $kw->keyword }}</td>
                                        <td data-col="k_campaign_id">{{ $kw->campaign_id }}</td>

                                        @php
                                            $asins = is_string($kw->related_asin)
                                                ? json_decode($kw->related_asin, true)
                                                : $kw->related_asin;
                                        @endphp
                                        <td style="white-space:pre-line;" data-col="asin">
                                            {{ $asins ? implode("\n", $asins) : $kw->asin }}
                                        </td>

                                        <td data-col="campaign_type">{{ $kw->campaign_types }}</td>
                                        <td data-col="date">{{ $kw->date }}</td>
                                        <td data-col="country">{{ $kw->country }}</td>

                                        @foreach ($activePeriods as $p)
                                            @php
                                                $cfg = $periodConfig[$p];
                                            @endphp
                                            <td class="{{ $cfg['cell'] }}">
                                                ${{ number_format(data_get($kw, $cfg['spend'], 0), 2) }}
                                            </td>
                                            <td class="{{ $cfg['cell'] }}">
                                                ${{ number_format(data_get($kw, $cfg['sales'], 0), 2) }}
                                            </td>
                                            <td class="{{ $cfg['cell'] }}">
                                                {{ data_get($kw, $cfg['purchase'], 0) }}
                                            </td>
                                            <td class="{{ $cfg['cell'] }}">
                                                {{ data_get($kw, $cfg['acos'], 0) }}%
                                            </td>
                                        @endforeach

                                        <td data-col="bid_start"
                                            class="{{ $kw->targeting_type === 'MANUAL' ? 'text-success' : 'text-primary' }}">
                                            @if ($kw->targeting_type === 'MANUAL')
                                                ${{ number_format($kw->manual_bid_start ?? 0, 2) ?? 'N/A' }}
                                            @else
                                                ${{ number_format($kw->auto_bid_start ?? 0, 2) ?? 'N/A' }}
                                            @endif
                                        </td>

                                        <td data-col="bid_suggest"
                                            class="{{ $kw->targeting_type === 'MANUAL' ? 'text-success' : 'text-primary' }}">
                                            @if ($kw->targeting_type === 'MANUAL')
                                                ${{ number_format($kw->manual_bid_suggestion ?? 0, 2) ?? 'N/A' }}
                                            @else
                                                ${{ number_format($kw->auto_bid_median ?? 0, 2) ?? 'N/A' }}
                                            @endif
                                        </td>

                                        <td data-col="bid_end"
                                            class="{{ $kw->targeting_type === 'MANUAL' ? 'text-success' : 'text-primary' }}">
                                            @if ($kw->targeting_type === 'MANUAL')
                                                ${{ number_format($kw->manual_bid_end ?? 0, 2) ?? 'N/A' }}
                                            @else
                                                ${{ number_format($kw->auto_bid_end ?? 0, 2) ?? 'N/A' }}
                                            @endif
                                        </td>
                                        @php
                                            $rawState =
                                                !empty($kw->sp_state) && strtoupper($kw->sp_state) !== 'N/A'
                                                    ? $kw->sp_state
                                                    : (!empty($kw->sb_state) && strtoupper($kw->sb_state) !== 'N/A'
                                                        ? $kw->sb_state
                                                        : null);
                                            // Normalize once
                                            $state = $rawState ? strtoupper(trim($rawState)) : null;

                                            $badgeClass = match ($state) {
                                                'ENABLED' => 'success',
                                                'PAUSED' => 'warning',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <td>
                                            <span class="badge bg-{{ $badgeClass }}">
                                                {{ $state ?? 'N/A' }}
                                            </span>
                                        </td>

                                        <td>${{ number_format($kw->bid ?? 0, 2) }}</td>
                                        <td data-col="ai_recommendation">
                                            {{ is_numeric($kw->ai_suggested_bid) ? '$' . number_format($kw->ai_suggested_bid, 2) : '--' }}
                                        </td>

                                        <td data-col="suggested_bid">{{ '$' . $kw->suggested_bid ?? '--' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="100%" class="text-center">No keywords available</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-2">
                        {{ $keywords->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
