@extends('layouts.app')
@section('content')
    <div>
        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-content-between mb-3">
                    <h4 class="mb-sm-0 font-size-18">Ads Overview</h4>
                </div>
            </div>
        </div>

        <div class="card mb-1">
            <div class="card-body m-1 p-1">
                <div class="d-flex flex-column flex-md-row align-items-md-center">
                    <ul role="tablist" class="nav nav-tabs nav-tabs-custom card-header-tabs flex-column flex-sm-row">
                        @can('amazon-ads.campaign-overview-dashboard')
                            <li class="nav-item">
                                <a href="{{ route('admin.ads.overview.index') }}"
                                    class="nav-link w-100 w-sm-auto {{ request()->routeIs('admin.ads.overview.index') ? 'active' : '' }}">
                                    Campaign Performance Dashboard
                                </a>
                            </li>
                        @endcan
                        <hr class="d-sm-none my-0">
                        @can('amazon-ads.keyword-overview-dashboard')
                            <li class="nav-item">
                                <a href="{{ route('admin.ads.overview.keywordDashboard') }}"
                                    class="nav-link w-100 w-sm-auto {{ request()->routeIs('admin.ads.overview.keywordDashboard') ? 'active' : '' }}">
                                    Keyword Performance Dashboard
                                </a>
                            </li>
                        @endcan
                    </ul>
                </div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
                    {{-- LEFT: Date / Date Range --}}
                    <div class="fw-semibold text-muted">
                        @if ($start == $end)
                            <form method="GET" id="filterForm" class="d-flex align-items-center gap-2">
                                <span class="text-success">Date :</span>
                                <input type="date" name="date" class="form-control form-control-sm"
                                    value="{{ request('date', $start) }}" max="{{ now(config('timezone.market'))->subDay()->toDateString() }}" style="width:130px"
                                    onchange="this.form.submit()" onclick="this.showPicker()">
                            </form>
                        @else
                            <span class="text-success">Date Range :</span>
                            <span>{{ $end ?? '' }} – {{ $start ?? '' }}</span>
                        @endif
                    </div>
                    {{-- RIGHT: Refresh button --}}
                    <div class="d-flex align-items-center justify-content-md-end">
                        <a href="{{ route('admin.ads.overview.cacheClear') }}"
                            class="btn btn-light btn-sm d-flex align-items-center">
                            <i class="bx bx-loader text-primary me-1"></i>
                            Refresh
                        </a>
                    </div>
                </div>
                <!-- Navigation Pills -->
                <div class="row g-3 align-items-center">
                    {{-- Left: Navigation Pills --}}
                    <div class="col-12 col-md-auto">
                        <ul
                            class="nav bg-light rounded nav-pills navtab-bg gap-2 flex-column flex-sm-row overview-period-nav m-0">
                            @php $period = request('period', '1d'); @endphp
                            <li class="nav-item">
                                <a href="{{ request()->fullUrlWithQuery(['period' => '1d']) }}"
                                    class="nav-link {{ $period === '1d' ? 'active' : '' }}">
                                    Yesterday
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ request()->fullUrlWithQuery(['period' => '7d']) }}"
                                    class="nav-link {{ $period === '7d' ? 'active' : '' }}">
                                    7 days
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ request()->fullUrlWithQuery(['period' => '14d']) }}"
                                    class="nav-link {{ $period === '14d' ? 'active' : '' }}">
                                    14 days
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ request()->fullUrlWithQuery(['period' => '30d']) }}"
                                    class="nav-link {{ $period === '30d' ? 'active' : '' }}">
                                    30 days
                                </a>
                            </li>
                        </ul>
                    </div>
                    {{-- Right: ASIN select + Submit --}}
                    <div class="col-12 col-md-auto ms-md-auto">
                        <form action="{{ url()->current() }}" method="GET"
                            class="d-flex flex-column flex-sm-row align-items-sm-center gap-2">
                            {{-- Keep existing filters (except asin) --}}
                            @foreach (request()->except('asin') as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <livewire:asin-search />
                            <livewire:product-search />
                            <button class="btn btn-rounded btn-success mt-sm-0 mt-2">
                                <i class="mdi mdi-magnify"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @php
            $spAuto = $overview['by_type']['SP']['AUTO'] ?? null;
            $spManual = $overview['by_type']['SP']['MANUAL'] ?? null;
            $sb = $overview['by_type']['SB'] ?? null;
            $sd = $overview['by_type']['SD'] ?? null;
            $totalsp = $overview['counts']['SP']['AUTO'] + $overview['counts']['SP']['MANUAL'] ?? null;

            $formatMoney = function ($val) {
                return $val !== null ? '$' . number_format($val, 2) : 'N/A';
            };
            $formatPercent = function ($val) {
                return $val !== null ? number_format($val, 2) . '%' : 'N/A';
            };
            $formatInt = function ($val) {
                return $val !== null ? number_format($val) : 'N/A';
            };

            $total = $overview['total'] ?? null;
        @endphp
        @php
            $withFilters = function (array $extra = []) {
                $base = [
                    'period' => request('period', '1d'),
                    'date' => request('date'),
                ];
                $asin = request()->input('asin'); // can be string or array
                if (!is_null($asin)) {
                    $base['asins[]'] = $asin;
                }
                return array_merge($base, $extra);
            };
        @endphp

        {{-- Row 1: 4 metric cards --}}
        <div class="row">
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Total Campaigns (SP + SB + SD)</h4>
                        <div class="row mb-4">
                            <div class="col-lg-8">
                                <div class="mt-4">
                                    <p>Total Campaigns</p>
                                    <h2 class="text-primary count-animate"
                                        data-target="{{ $overview['counts']['total'] }}"">
                                        {{ $overview['counts']['total'] ?? 'N/A' }}</h2>
                                    <div class="row">
                                        <div class="col-6">
                                            <div>
                                                <p class="mb-2">Total Spend</p>
                                                <h3 class="count-animate" data-prefix="$"
                                                    data-target="{{ $total['spend'] ?? '' }}">
                                                    {{ $total ? $formatMoney($total['spend']) : 'N/A' }}
                                                </h3>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div>
                                                <p class="mb-2">Total Sales</p>
                                                <h3 class="text-success count-animate" data-prefix="$"
                                                    data-target="{{ isset($total['sales']) ? $total['sales'] : '' }}">
                                                    {{ isset($total['sales']) ? $formatMoney($total['sales']) : 'N/A' }}
                                                </h3>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <a class="btn btn-light btn-sm"
                                            href="{{ route('admin.ads.overview.campaignOverview',$withFilters([])) }}">View more <i
                                                class="mdi mdi-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="align-self-center col-lg-4 ps-3">
                                <div class="row d-flex flex-lg-column align-items-lg-center text-lg-center">
                                    <!-- Total Units -->
                                    <div class="col-6 col-lg-12 mt-4 pt-2">
                                        <p class="mb-2">
                                            <i class="mdi mdi-circle align-middle font-size-14 me-2 text-primary"></i>
                                            Total Units
                                        </p>
                                        <h3> {{ $total ? $total['units'] : 'N/A' }}</h3>
                                    </div>
                                    <!-- ACoS -->
                                    <div class="col-6 col-lg-12 mt-4 pt-2">
                                        <p class="mb-2">
                                            <i class="mdi mdi-circle align-middle font-size-14 me-2 text-success"></i>
                                            Total ACoS
                                        </p>
                                        <h3>{{ $total ? $formatPercent($total['acos']) : 'N/A' }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="row">
                    <div class="col-sm-6 pe-1">
                        <a href="{{ route('admin.ads.overview.campaignOverview', $withFilters(['acos' => '30'])) }}"
                            class="text-decoration-none">
                            <div class="card hover-card clickable-card mb-2">
                                <div class="card-body">
                                    <!-- Header -->
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="avatar-xs me-2">
                                            <span class="avatar-title rounded-circle glance glance-success font-size-18">
                                                <i class="bx bx-trending-up"></i>
                                            </span>
                                        </div>
                                        <h5 class="font-size-14 mb-0 text-dark">&lt; 30% ACoS</h5>
                                    </div>
                                    <!-- Campaigns + Units + Spend + Sales -->
                                    <div class="row text-center text-sm-start">
                                        <!-- campaigns -->
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Campaigns</span>
                                            <h5 class="mb-0 text-dark">
                                                {{ $total ? $formatInt($total['buckets']['lt_30']['count'] ?? null) : 'N/A' }}
                                            </h5>
                                        </div>
                                        <!-- Units -->
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Units</span>
                                            <h5 class="mb-0 text-dark">
                                                {{ $total ? $formatInt($total['buckets']['lt_30']['units'] ?? null) : 'N/A' }}
                                                </h6>
                                        </div>
                                        <!-- Spend -->
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Spend</span>
                                            <h5 class="mb-0 text-dark">
                                                {{ $total ? $formatMoney($total['buckets']['lt_30']['spend'] ?? null) : 'N/A' }}
                                            </h5>
                                        </div>
                                        <!-- Sales -->
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Sales</span>
                                            <h5 class="mb-0 text-dark">
                                                {{ $total ? $formatMoney($total['buckets']['lt_30']['sales'] ?? null) : 'N/A' }}
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-sm-6 ps-1">
                        <a href="{{ route('admin.ads.overview.campaignOverview', $withFilters(['acos' => '31'])) }}"
                            class="text-decoration-none">
                            <div class="card hover-card clickable-card mb-2">
                                <div class="card-body">
                                    <!-- Header -->
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="avatar-xs me-2">
                                            <span class="avatar-title rounded-circle glance glance-warning font-size-18">
                                                <i class="bx bx-trending-down"></i>
                                            </span>
                                        </div>
                                        <h5 class="font-size-14 mb-0 text-dark">&gt; 30% ACoS</h5>
                                    </div>
                                    <!-- Campaigns + Units + Spend + Sales -->
                                    <div class="row text-center text-sm-start">
                                        <!-- campaigns -->
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Campaigns</span>
                                            <h5 class="mb-0 text-dark">
                                                {{ $total ? $formatInt($total['buckets']['gte_30']['count'] ?? null) : 'N/A' }}
                                            </h5>
                                        </div>
                                        <!-- Units -->
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Units</span>
                                            <h5 class="mb-0 text-dark">
                                                {{ $total ? $formatInt($total['buckets']['gte_30']['units'] ?? null) : 'N/A' }}
                                                </h6>
                                        </div>
                                        <!-- Spend -->
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Spend</span>
                                            <h5 class="mb-0 text-dark">
                                                {{ $total ? $formatMoney($total['buckets']['gte_30']['spend'] ?? null) : 'N/A' }}
                                            </h5>
                                        </div>
                                        <!-- Sales -->
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Sales</span>
                                            <h5 class="mb-0 text-dark">
                                                {{ $total ? $formatMoney($total['buckets']['gte_30']['sales'] ?? null) : 'N/A' }}
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6 pe-1">
                        <a href="{{ route('admin.ads.overview.campaignOverview', $withFilters(['acos' => '0'])) }}"
                            class="text-decoration-none">
                            <div class="card hover-card clickable-card mb-2">
                                <div class="card-body">
                                    <!-- Header -->
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="avatar-xs me-2">
                                            <span class="avatar-title rounded-circle glance glance-danger font-size-18">
                                                <i class="bx bx-error-circle"></i>
                                            </span>
                                        </div>
                                        <h5 class="font-size-14 mb-0 text-dark me-2">0% ACoS</h5>
                                        <span class="text-muted small d-block">(Spend > 0 && Sale = 0)</span>
                                    </div>
                                    <!-- Campaigns + Units + Spend + Sales -->
                                    <div class="row text-center text-sm-start">
                                        <!-- campaigns -->
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Campaigns</span>
                                            <h5 class="mb-0 text-dark">
                                                {{ $total ? $formatInt($total['buckets']['spend_gt_zero_sales']['count'] ?? null) : 'N/A' }}
                                            </h5>
                                        </div>
                                        <!-- Units -->
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Units</span>
                                            <h5 class="mb-0 text-muted">
                                                {{ $total ? $formatInt($total['buckets']['spend_gt_zero_sales']['unit'] ?? null) : 'N/A' }}
                                                </h6>
                                        </div>
                                        <!-- Spend -->
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Spend</span>
                                            <h5 class="mb-0 text-dark">
                                                {{ $total ? $formatMoney($total['buckets']['spend_gt_zero_sales']['spend'] ?? null) : 'N/A' }}
                                            </h5>
                                        </div>
                                        <!-- Sales -->
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Sales</span>
                                            <h5 class="mb-0 text-muted">
                                                {{ $total ? $formatMoney($total['buckets']['spend_gt_zero_sales']['sales'] ?? null) : 'N/A' }}
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 ps-1">
                        <a href="{{ route('admin.ads.overview.campaignOverview', $withFilters(['acos' => 'none', 'spend' => '0'])) }}"
                            class="text-decoration-none">
                            <div class="card  hover-card clickable-card mb-2">
                                <div class="card-body">
                                    <!-- Header -->
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="avatar-xs me-2">
                                            <span class="avatar-title rounded-circle glance glance-danger font-size-18">
                                                <i class="bx bx-error"></i>
                                            </span>
                                        </div>
                                        <h5 class="font-size-14 mb-0 text-dark me-2">0% ACoS</h5>
                                        <span class="text-muted small d-block">(Spend = 0 && Sale = 0)</span>
                                    </div>
                                    <!-- Campaigns + Units + Spend + Sales -->
                                    <div class="row text-center text-sm-start">
                                        <!-- campaigns -->
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Campaigns</span>
                                            <h5 class="mb-0 text-dark">
                                                {{ $total ? $formatInt($total['buckets']['spend_zero_sales_zero_cnt']['count'] ?? null) : 'N/A' }}
                                            </h5>
                                        </div>
                                        <!-- Units -->
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Units</span>
                                            <h5 class="mb-0 text-muted">
                                                {{ $total ? $formatInt($total['buckets']['spend_zero_sales_zero_cnt']['unit'] ?? null) : 'N/A' }}
                                                </h6>
                                        </div>
                                        <!-- Spend -->
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Spend</span>
                                            <h5 class="mb-0 text-muted">
                                                {{ $total ? $formatMoney($total['buckets']['spend_zero_sales_zero_cnt']['spend'] ?? null) : 'N/A' }}
                                            </h5>
                                        </div>
                                        <!-- Sales -->
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Sales</span>
                                            <h5 class="mb-0 text-muted">
                                                {{ $total ? $formatMoney($total['buckets']['spend_zero_sales_zero_cnt']['sales'] ?? null) : 'N/A' }}
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Row 2:4 SP AUTO --}}

        <div class="row align-items-stretch">
            <!-- LEFT SIDE (SP SUMMARY + AUTO/MANUAL SPLIT) -->
            <div class="col-xl-6 d-flex">
                <div class="card w-100">
                    <div class="card-body">
                        <h4 class="card-title mb-0">Campaigns SP</h4>
                        <div class="row mb-2 pb-2">
                            <div class="col-lg-6">
                                <div class="mt-2">
                                    <p>Total Campaigns</p>
                                    <h4>{{ $totalsp ?? 'N/A' }}</h4>
                                    <div class="mt-2">
                                        <a class="btn btn-light btn-sm"
                                            href="{{ route('admin.ads.overview.campaignOverview', $withFilters(['campaign' => 'SP'])) }}">
                                            View more <i class="mdi mdi-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="align-self-center col-lg-6 ps-3">
                                <div class="row d-flex flex-lg-column align-items-lg-center text-lg-center">
                                    <!-- Total Units -->
                                    <div class="col-6 col-lg-12">
                                        <p class="mb-2">
                                            <i class="mdi mdi-circle align-middle font-size-14 me-2 text-success"></i>
                                            AUTO ACoS
                                        </p>
                                        <h5>{{ isset($spAuto) ? $formatPercent($spAuto['acos']) : 'N/A' }}</h5>
                                    </div>
                                    <!-- ACoS -->
                                    <div class="col-6 col-lg-12">
                                        <p class="mb-2">
                                            <i class="mdi mdi-circle align-middle font-size-14 me-2 text-info"></i>
                                            MANUAL ACoS
                                        </p>
                                        <h5>{{ isset($spManual) ? $formatPercent($spManual['acos']) : 'N/A' }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- OPTIONAL: AUTO / MANUAL SMALL SUMMARY BELOW (IF YOU WANT) -->

                        <div class="row pt-2 border-top border-4 mt-2">
                            <div class="col-md-6 border-end border-4 mb-3 mb-md-0">
                                <p class="fw-semibold mb-2">
                                    <i class="mdi mdi-checkbox-blank-circle-outline text-info me-1"></i> AUTO
                                </p>
                                <div class="row text-sm-start">
                                    <div class="col-6">
                                        <span class="text-muted small d-block">Campaigns</span>
                                        <h6 class="mb-0 text-dark">{{ $overview['counts']['SP']['AUTO'] ?? 'N/A' }}</h6>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted small d-block">Units</span>
                                        <h6 class="mb-0 text-dark">{{ isset($spAuto) ? $spAuto['units'] : 'N/A' }}</h6>
                                    </div>
                                    <div class="col-6 mt-2">
                                        <span class="text-muted small d-block">Spend</span>
                                        <h6 class="mb-0 text-dark">
                                            {{ isset($spAuto) ? $formatMoney($spAuto['spend']) : 'N/A' }}</h6>
                                    </div>
                                    <div class="col-6 mt-2">
                                        <span class="text-muted small d-block">Sales</span>
                                        <h6 class="mb-0 text-dark">
                                            {{ isset($spAuto) ? $formatMoney($spAuto['sales']) : 'N/A' }}</h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 ps-md-3">
                                <p class="fw-semibold mb-2">
                                    <i class="mdi mdi-checkbox-blank-circle-outline text-success me-1"></i> MANUAL
                                </p>
                                <div class="row text-sm-start">
                                    <div class="col-6">
                                        <span class="text-muted small d-block">Campaigns</span>
                                        <h6 class="mb-0 text-dark">{{ $overview['counts']['SP']['MANUAL'] ?? 'N/A' }}</h6>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted small d-block">Units</span>
                                        <h6 class="mb-0 text-dark">{{ isset($spManual) ? $spManual['units'] : 'N/A' }}
                                        </h6>
                                    </div>
                                    <div class="col-6 mt-2">
                                        <span class="text-muted small d-block">Spend</span>
                                        <h6 class="mb-0 text-dark">
                                            {{ isset($spManual) ? $formatMoney($spManual['spend']) : 'N/A' }}</h6>
                                    </div>
                                    <div class="col-6 mt-2">
                                        <span class="text-muted small d-block">Sales</span>
                                        <h6 class="mb-0 text-dark">
                                            {{ isset($spManual) ? $formatMoney($spManual['sales']) : 'N/A' }}</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- RIGHT SIDE (AUTO / MANUAL TOGGLE WITH NAV-TABS) -->
            <div class="col-xl-6 d-flex">
                <div class="card w-100">
                    <div class="card-body" x-data="{ view: 'auto' }">

                        <!-- NAV TABS -->
                        <ul class="nav nav-tabs nav-tabs-custom card-header-tabs ms-auto mb-3" role="tablist">
                            <li class="nav-item">
                                <a href="javascript:void(0)" class="nav-link"
                                    :class="view === 'auto' ? 'active' : ''" @click="view = 'auto'">
                                    AUTO
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="javascript:void(0)" class="nav-link"
                                    :class="view === 'manual' ? 'active' : ''" @click="view = 'manual'">
                                    MANUAL
                                </a>
                            </li>
                        </ul>

                        <!-- AUTO VIEW -->
                        <div x-show="view === 'auto'" x-cloak>
                            <!-- FIRST ROW -->
                            <div class="row">
                                <!-- LEFT – < 30% ACoS -->
                                <div class="col-sm-6 pe-sm-1 border-end border-4">
                                    <a href="{{ route('admin.ads.overview.campaignOverview', $withFilters(['acos' => '30', 'campaign' => 'SP'])) }}"
                                        class="text-decoration-none d-block py-2">
                                        <div>
                                            <!-- Header -->
                                            <h5 class="font-size-14 mb-0 text-success">&lt; 30% ACoS</h5>
                                            <!-- Metrics -->
                                            <div class="row mt-2 text-center text-sm-start">
                                                <div class="col-6">
                                                    <span class="text-muted small d-block">Campaigns</span>
                                                    <h6 class="mb-0 text-dark">
                                                        {{ isset($spAuto) ? $formatInt($spAuto['buckets']['lt_30']['count'] ?? null) : 'N/A' }}
                                                    </h6>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted small d-block">Units</span>
                                                    <h6 class="mb-0 text-dark">
                                                        {{ isset($spAuto) ? $formatInt($spAuto['buckets']['lt_30']['units'] ?? null) : 'N/A' }}
                                                    </h6>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted small d-block">Spend</span>
                                                    <h6 class="mb-0 text-dark">
                                                        {{ isset($spAuto) ? $formatMoney($spAuto['buckets']['lt_30']['spend'] ?? null) : 'N/A' }}
                                                    </h6>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted small d-block">Sales</span>
                                                    <h6 class="mb-0 text-dark">
                                                        {{ isset($spAuto) ? $formatMoney($spAuto['buckets']['lt_30']['sales'] ?? null) : 'N/A' }}
                                                    </h6>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>

                                <!-- RIGHT – > 30% ACoS -->
                                <div class="col-sm-6 ps-sm-1">
                                    <a href="{{ route('admin.ads.overview.campaignOverview', $withFilters(['acos' => '31', 'campaign' => 'SP'])) }}"
                                        class="text-decoration-none d-block py-2">
                                        <div class="ps-2">
                                            <!-- Header -->
                                            <h5 class="font-size-14 mb-0 text-warning">&gt; 30% ACoS</h5>
                                            <!-- Metrics -->
                                            <div class="row mt-2 text-center text-sm-start">
                                                <div class="col-6">
                                                    <span class="text-muted small d-block">Campaigns</span>
                                                    <h6 class="mb-0 text-dark">
                                                        {{ isset($spAuto) ? $formatInt($spAuto['buckets']['gte_30']['count'] ?? null) : 'N/A' }}
                                                    </h6>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted small d-block">Units</span>
                                                    <h6 class="mb-0 text-dark">
                                                        {{ isset($spAuto) ? $formatInt($spAuto['buckets']['gte_30']['units'] ?? null) : 'N/A' }}
                                                    </h6>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted small d-block">Spend</span>
                                                    <h6 class="mb-0 text-dark">
                                                        {{ isset($spAuto) ? $formatMoney($spAuto['buckets']['gte_30']['spend'] ?? null) : 'N/A' }}
                                                    </h6>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted small d-block">Sales</span>
                                                    <h6 class="mb-0 text-dark">
                                                        {{ isset($spAuto) ? $formatMoney($spAuto['buckets']['gte_30']['sales'] ?? null) : 'N/A' }}
                                                    </h6>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <div class="row">
                                <!-- LEFT – SECOND ROW < 30% ACoS -->
                                <div class="col-sm-6 pe-sm-1 border-top border-end border-4">
                                    <a href="{{ route('admin.ads.overview.campaignOverview', $withFilters(['acos' => '30', 'campaign' => 'SP'])) }}"
                                        class="text-decoration-none d-block py-2">
                                        <div>
                                            <!-- Header -->
                                            <div class="d-flex align-items-center gap-2">
                                                <h5 class="font-size-14 mb-0 text-danger">0% ACoS</h5>
                                                <span class="text-muted small d-block">(Spend > 0 && Sale = 0)</span>
                                            </div>
                                            <!-- Metrics -->
                                            <div class="row mt-2 text-center text-sm-start">
                                                <div class="col-6">
                                                    <span class="text-muted small d-block">Campaigns</span>
                                                    <h6 class="mb-0 text-dark">
                                                        {{ isset($spAuto) ? $formatInt($spAuto['buckets']['spend_gt_zero_sales']['count'] ?? null) : 'N/A' }}
                                                    </h6>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted small d-block">Units</span>
                                                    <h6 class="mb-0 text-muted">
                                                        {{ isset($spAuto) ? $formatInt($spAuto['buckets']['spend_gt_zero_sales']['units'] ?? null) : 'N/A' }}
                                                    </h6>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted small d-block">Spend</span>
                                                    <h6 class="mb-0 text-dark">
                                                        {{ isset($spAuto) ? $formatMoney($spAuto['buckets']['spend_gt_zero_sales']['spend'] ?? null) : 'N/A' }}
                                                    </h6>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted small d-block">Sales</span>
                                                    <h6 class="mb-0 text-muted">
                                                        {{ isset($spAuto) ? $formatMoney($spAuto['buckets']['spend_gt_zero_sales']['sales'] ?? null) : 'N/A' }}
                                                    </h6>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>

                                <!-- LEFT – >% ACoS -->
                                <div class="col-sm-6 ps-sm-1 border-top border-4">
                                    <div class="ps-2 py-2">
                                        <!-- Header -->
                                        <div class="d-flex align-items-center gap-2">
                                            <h5 class="font-size-14 mb-0 text-danger">0% ACoS</h5>
                                            <span class="text-muted small d-block">(Spend = 0 && Sale = 0)</span>
                                        </div>
                                        <!-- Metrics -->
                                        <div class="row mt-2 text-center text-sm-start">
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Campaigns</span>
                                                <h6 class="mb-0 text-dark">
                                                    {{ isset($spAuto) ? $formatInt($spAuto['buckets']['spend_zero_sales_zero_cnt']['count'] ?? null) : 'N/A' }}
                                                </h6>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Units</span>
                                                <h6 class="mb-0 text-muted">
                                                    {{ isset($spAuto) ? $formatInt($spAuto['buckets']['spend_zero_sales_zero_cnt']['units'] ?? null) : 'N/A' }}
                                                </h6>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Spend</span>
                                                <h6 class="mb-0 text-muted">
                                                    {{ isset($spAuto) ? $formatMoney($spAuto['buckets']['spend_zero_sales_zero_cnt']['spend'] ?? null) : 'N/A' }}
                                                </h6>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Sales</span>
                                                <h6 class="mb-0 text-muted">
                                                    {{ isset($spAuto) ? $formatMoney($spAuto['buckets']['spend_zero_sales_zero_cnt']['sales'] ?? null) : 'N/A' }}
                                                </h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- MANUAL VIEW -->
                        <div x-show="view === 'manual'" x-cloak>

                            <!-- FIRST ROW -->
                            <div class="row">
                                <!-- LEFT – < 30% ACoS -->
                                <div class="col-sm-6 pe-sm-1 border-end border-4">
                                    <a href="{{ route('admin.ads.overview.campaignOverview', $withFilters(['acos' => '30', 'campaign' => 'SP'])) }}"
                                        class="text-decoration-none d-block py-2">
                                        <div>
                                            <!-- Header -->
                                            <h5 class="font-size-14 mb-0 text-success">&lt; 30% ACoS</h5>
                                            <!-- Metrics -->
                                            <div class="row mt-2 text-center text-sm-start">
                                                <div class="col-6">
                                                    <span class="text-muted small d-block">Campaigns</span>
                                                    <h6 class="mb-0 text-dark">
                                                        {{ isset($spManual) ? $formatInt($spManual['buckets']['lt_30']['count'] ?? null) : 'N/A' }}
                                                    </h6>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted small d-block">Units</span>
                                                    <h6 class="mb-0 text-dark">
                                                        {{ isset($spManual) ? $formatInt($spManual['buckets']['lt_30']['units'] ?? null) : 'N/A' }}
                                                    </h6>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted small d-block">Spend</span>
                                                    <h6 class="mb-0 text-dark">
                                                        {{ isset($spManual) ? $formatMoney($spManual['buckets']['lt_30']['spend'] ?? null) : 'N/A' }}
                                                    </h6>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted small d-block">Sales</span>
                                                    <h6 class="mb-0 text-dark">
                                                        {{ isset($spManual) ? $formatMoney($spManual['buckets']['lt_30']['sales'] ?? null) : 'N/A' }}
                                                    </h6>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>

                                <!-- RIGHT – > 30% ACoS -->
                                <div class="col-sm-6 ps-sm-1">
                                    <a href="{{ route('admin.ads.overview.campaignOverview', $withFilters(['acos' => '31', 'campaign' => 'SP'])) }}"
                                        class="text-decoration-none d-block py-2">
                                        <div class="ps-2">
                                            <!-- Header -->
                                            <h5 class="font-size-14 mb-0 text-warning">&gt; 30% ACoS</h5>
                                            <!-- Metrics -->
                                            <div class="row mt-2 text-center text-sm-start">
                                                <div class="col-6">
                                                    <span class="text-muted small d-block">Campaigns</span>
                                                    <h6 class="mb-0 text-dark">
                                                        {{ isset($spManual) ? $formatInt($spManual['buckets']['gte_30']['count'] ?? null) : 'N/A' }}
                                                    </h6>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted small d-block">Units</span>
                                                    <h6 class="mb-0 text-dark">
                                                        {{ isset($spManual) ? $formatInt($spManual['buckets']['gte_30']['units'] ?? null) : 'N/A' }}
                                                    </h6>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted small d-block">Spend</span>
                                                    <h6 class="mb-0 text-dark">
                                                        {{ isset($spManual) ? $formatMoney($spManual['buckets']['gte_30']['spend'] ?? null) : 'N/A' }}
                                                    </h6>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted small d-block">Sales</span>
                                                    <h6 class="mb-0 text-dark">
                                                        {{ isset($spManual) ? $formatMoney($spManual['buckets']['gte_30']['sales'] ?? null) : 'N/A' }}
                                                    </h6>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <!-- SECOND ROW -->
                            <div class="row">
                                <!-- LEFT – < 30% ACoS -->
                                <div class="col-sm-6 pe-sm-1 border-top border-end border-4">
                                    <a href="{{ route('admin.ads.overview.campaignOverview', $withFilters(['acos' => '0', 'campaign' => 'SP'])) }}"
                                        class="text-decoration-none d-block py-2">
                                        <div>
                                            <!-- Header -->
                                            <div class="d-flex align-items-center gap-2">
                                                <h5 class="font-size-14 mb-0 text-danger">0% ACoS</h5>
                                                <span class="text-muted small d-block">(Spend > 0 && Sale = 0)</span>
                                            </div> <!-- Metrics -->
                                            <div class="row mt-2 text-center text-sm-start">
                                                <div class="col-6">
                                                    <span class="text-muted small d-block">Campaigns</span>
                                                    <h6 class="mb-0 text-dark">
                                                        {{ isset($spManual) ? $formatInt($spManual['buckets']['spend_gt_zero_sales']['count'] ?? null) : 'N/A' }}
                                                    </h6>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted small d-block">Units</span>
                                                    <h6 class="mb-0 text-muted">
                                                        {{ isset($spManual) ? $formatInt($spManual['buckets']['spend_gt_zero_sales']['units'] ?? null) : 'N/A' }}
                                                    </h6>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted small d-block">Spend</span>
                                                    <h6 class="mb-0 text-dark">
                                                        {{ isset($spManual) ? $formatMoney($spManual['buckets']['spend_gt_zero_sales']['spend'] ?? null) : 'N/A' }}
                                                    </h6>
                                                </div>
                                                <div class="col-6">
                                                    <span class="text-muted small d-block">Sales</span>
                                                    <h6 class="mb-0 text-muted">
                                                        {{ isset($spManual) ? $formatMoney($spManual['buckets']['spend_gt_zero_sales']['sales'] ?? null) : 'N/A' }}
                                                    </h6>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>

                                <!-- RIGHT – > 30% ACoS -->
                                <div class="col-sm-6 ps-sm-1 border-top border-4">
                                    <div class="ps-2 py-2">
                                        <!-- Header -->
                                        <div class="d-flex align-items-center gap-2">
                                            <h5 class="font-size-14 mb-0 text-danger">0% ACoS</h5>
                                            <span class="text-muted small d-block">(Spend = 0 && Sale = 0)</span>
                                        </div> <!-- Metrics -->
                                        <div class="row mt-2 text-center text-sm-start">
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Campaigns</span>
                                                <h6 class="mb-0 text-dark">
                                                    {{ isset($spManual) ? $formatInt($spManual['buckets']['spend_zero_sales_zero_cnt']['count'] ?? null) : 'N/A' }}
                                                </h6>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Units</span>
                                                <h6 class="mb-0 text-muted">
                                                    {{ isset($spManual) ? $formatInt($spManual['buckets']['spend_zero_sales_zero_cnt']['units'] ?? null) : 'N/A' }}
                                                </h6>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Spend</span>
                                                <h6 class="mb-0 text-muted">
                                                    {{ isset($spManual) ? $formatMoney($spManual['buckets']['spend_zero_sales_zero_cnt']['spend'] ?? null) : 'N/A' }}
                                                </h6>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Sales</span>
                                                <h6 class="mb-0 text-muted">
                                                    {{ isset($spManual) ? $formatMoney($spManual['buckets']['spend_zero_sales_zero_cnt']['sales'] ?? null) : 'N/A' }}
                                                </h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SB Section --}}
        @php
            $sbBuckets = $sb['buckets'] ?? [
                'lt_30' => ['count' => null, 'spend' => null],
                'gte_30' => ['count' => null, 'spend' => null],
                'zero' => ['count' => null, 'spend' => null],
                'spend_gt_zero_sales' => ['count' => null, 'spend' => null],
                'spend_zero_sales_zero_cnt' => ['count' => null, 'spend' => null],
            ];
        @endphp


        <div class="row align-items-stretch">
            <!-- LEFT SIDE -->
            <div class="col-xl-6 d-flex">
                <div class="card w-100">
                    <div class="card-body">
                        <h4 class="card-title mb-0">Campaigns SB</h4>
                        <div class="row mb-2 pb-2">
                            <div class="col-lg-6">
                                <div class="mt-2">
                                    <p>Total Campaigns</p>
                                    <h4>{{ $overview['counts']['SB'] ?? 'N/A' }}</h4>

                                    <div class="row mt-4">
                                        <div class="col-6">
                                            <div>
                                                <p class="mb-2">Total Spend</p>
                                                <h5>{{ isset($sb) ? $formatMoney($sb['spend']) : 'N/A' }}</h5>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div>
                                                <p class="mb-2">Total Sales</p>
                                                <h5>{{ isset($sb) ? $formatMoney($sb['sales']) : 'N/A' }}</h5>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-2">
                                        <a class="btn btn-light btn-sm"
                                            href="{{ route('admin.ads.overview.campaignOverview', $withFilters(['campaign' => 'SB'])) }}">
                                            View more <i class="mdi mdi-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="align-self-center col-lg-6 ps-3">
                                <div class="row d-flex flex-lg-column align-items-lg-center text-lg-center">
                                    <!-- Total Units -->
                                    <div class="col-6 col-lg-12">
                                        <p class="mb-2">
                                            <i class="mdi mdi-circle align-middle font-size-14 me-2 text-primary"></i>
                                            Total Units
                                        </p>
                                        <h5>{{ isset($sb) ? $sb['units'] : 'N/A' }}</h5>
                                    </div>
                                    <!-- ACoS -->
                                    <div class="col-6 col-lg-12">
                                        <p class="mb-2">
                                            <i class="mdi mdi-circle align-middle font-size-14 me-2 text-warning"></i>
                                            Total ACoS
                                        </p>
                                        <h5> {{ isset($sb) ? $formatPercent($sb['acos']) : 'N/A' }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT SIDE -->
            <div class="col-xl-6 d-flex">
                <div class="card w-100">
                    <div class="card-body">

                        <div class="row">
                            <!-- LEFT – < 30% ACoS -->
                            <div class="col-sm-6 pe-sm-1 border-end border-4">
                                <a href="{{ route('admin.ads.overview.campaignOverview', $withFilters(['acos' => '30', 'campaign' => 'SB'])) }}"
                                    class="text-decoration-none d-block py-2">
                                    <div>
                                        <!-- Header -->
                                        <h5 class="font-size-14 mb-0 text-success">&lt; 30% ACoS</h5>
                                        <!-- Metrics -->
                                        <div class="row mt-2 text-center text-sm-start">
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Campaigns</span>
                                                <h6 class="mb-0 text-dark">
                                                    {{ $formatInt($sbBuckets['lt_30']['count'] ?? null) }}</h6>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Units</span>
                                                <h6 class="mb-0 text-dark">
                                                    {{ $formatInt($sbBuckets['lt_30']['units'] ?? null) }}</h6>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Spend</span>
                                                <h6 class="mb-0 text-dark">
                                                    {{ $formatMoney($sbBuckets['lt_30']['spend'] ?? null) }}</h6>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Sales</span>
                                                <h6 class="mb-0 text-dark">
                                                    {{ isset($sbBuckets) ? $formatMoney($sbBuckets['lt_30']['sales'] ?? null) : 'N/A' }}
                                                </h6>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <!-- RIGHT – > 30% ACoS -->
                            <div class="col-sm-6 ps-sm-1">
                                <a href="{{ route('admin.ads.overview.campaignOverview', $withFilters(['acos' => '31', 'campaign' => 'SB'])) }}"
                                    class="text-decoration-none d-block py-2">
                                    <div class="ps-2">
                                        <!-- Header -->
                                        <h5 class="font-size-14 mb-0 text-warning">&gt; 30% ACoS</h5>
                                        <!-- Metrics -->
                                        <div class="row mt-2 text-center text-sm-start">
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Campaigns</span>
                                                <h6 class="mb-0 text-dark">
                                                    {{ $formatInt($sbBuckets['gte_30']['count'] ?? null) }}</h6>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Units</span>
                                                <h6 class="mb-0 text-dark">
                                                    {{ isset($sbBuckets) ? $sbBuckets['gte_30']['units'] : 'N/A' }}</h6>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Spend</span>
                                                <h6 class="mb-0 text-dark">
                                                    {{ $formatMoney($sbBuckets['gte_30']['spend'] ?? null) }}</h6>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Sales</span>
                                                <h6 class="mb-0 text-dark">
                                                    {{ isset($sbBuckets) ? $formatMoney($sbBuckets['gte_30']['sales'] ?? null) : 'N/A' }}
                                                </h6>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <!-- SECOND ROW (0% ACoS) -->
                        <div class="row">
                            <!-- LEFT 0% -->
                            <div class="col-sm-6 pe-sm-1 border-top border-end border-4">
                                <a href="{{ route('admin.ads.overview.campaignOverview', $withFilters(['acos' => '0', 'campaign' => 'SB'])) }}"
                                    class="text-decoration-none d-block py-2">
                                    <div>
                                        <div class="d-flex align-items-center mb-1">
                                            <h5 class="font-size-14 mb-0 text-danger me-2">0% ACoS</h5>
                                            <span class="text-muted small">(Spend > 0 && Sale = 0)</span>
                                        </div>

                                        <div class="row mt-2 text-center text-sm-start">
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Campaigns</span>
                                                <h6 class="mb-0 text-dark">
                                                    {{ $formatInt($sbBuckets['spend_gt_zero_sales']['count'] ?? null) }}
                                                </h6>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Units</span>
                                                <h6 class="mb-0 text-muted">
                                                    {{ $formatInt($sbBuckets['spend_gt_zero_sales']['unit'] ?? null) }}
                                                </h6>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Spend</span>
                                                <h6 class="mb-0 text-dark">
                                                    {{ $formatMoney($sbBuckets['spend_gt_zero_sales']['spend'] ?? null) }}
                                                </h6>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Sales</span>
                                                <h6 class="mb-0 text-muted">
                                                    {{ $formatMoney($sbBuckets['spend_gt_zero_sales']['sales'] ?? null) }}
                                                </h6>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <!-- RIGHT 0% -->
                            <div class="col-sm-6 ps-sm-1 border-top border-4">
                                <div class="ps-2 py-2">
                                    <div class="d-flex align-items-center mb-1">
                                        <h5 class="font-size-14 mb-0 text-danger me-2">0% ACoS</h5>
                                        <span class="text-muted small">(Spend = 0 && Sale = 0)</span>
                                    </div>

                                    <div class="row mt-2 text-center text-sm-start">
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Campaigns</span>
                                            <h6 class="mb-0 text-dark">
                                                {{ $formatInt($sbBuckets['spend_zero_sales_zero_cnt']['count'] ?? null) }}
                                            </h6>
                                        </div>
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Units</span>
                                            <h6 class="mb-0 text-muted">
                                                {{ $formatInt($sbBuckets['spend_zero_sales_zero_cnt']['unit'] ?? null) }}
                                            </h6>
                                        </div>
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Spend</span>
                                            <h6 class="mb-0 text-muted">
                                                {{ $formatMoney($sbBuckets['spend_zero_sales_zero_cnt']['spend'] ?? null) }}
                                            </h6>
                                        </div>
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Sales</span>
                                            <h6 class="mb-0 text-muted">
                                                {{ $formatMoney($sbBuckets['spend_zero_sales_zero_cnt']['sales'] ?? null) }}
                                            </h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $sdBuckets = $sd['buckets'] ?? [
                'lt_30' => ['count' => null, 'spend' => null],
                'gte_30' => ['count' => null, 'spend' => null],
                'zero' => ['count' => null, 'spend' => null],
                'spend_gt_zero_sales' => ['count' => null, 'spend' => null],
                'spend_zero_sales_zero_cnt' => ['count' => null, 'spend' => null],
            ];
        @endphp

        <div class="row align-items-stretch">
            <!-- LEFT SIDE -->
            <div class="col-xl-6 d-flex">
                <div class="card w-100">
                    <div class="card-body">
                        <h4 class="card-title mb-0">Campaigns SD</h4>
                        <div class="row mb-2 pb-2">
                            <div class="col-lg-6">
                                <div class="mt-2">
                                    <p>Total Campaigns</p>
                                    <h4>{{ $overview['counts']['SD'] ?? 'N/A' }}</h4>

                                    <div class="row mt-4">
                                        <div class="col-6">
                                            <div>
                                                <p class="mb-2">Total Spend</p>
                                                <h5> {{ isset($sd) ? $formatMoney($sd['spend']) : 'N/A' }}</h5>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div>
                                                <p class="mb-2">Total Sales</p>
                                                <h5>{{ isset($sd) ? $formatMoney($sd['sales']) : 'N/A' }}</h5>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-2">
                                        <a class="btn btn-light btn-sm"
                                            href="{{ route('admin.ads.overview.campaignOverview', $withFilters(['campaign' => 'SD'])) }}">
                                            View more <i class="mdi mdi-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="align-self-center col-lg-6 ps-3">
                                <div class="row d-flex flex-lg-column align-items-lg-center text-lg-center">
                                    <!-- Total Units -->
                                    <div class="col-6 col-lg-12">
                                        <p class="mb-2">
                                            <i class="mdi mdi-circle align-middle font-size-14 me-2 text-info"></i>
                                            Total Units
                                        </p>
                                        <h5> {{ isset($sd) ? $sd['units'] : 'N/A' }}</h5>
                                    </div>
                                    <!-- ACoS -->
                                    <div class="col-6 col-lg-12">
                                        <p class="mb-2">
                                            <i class="mdi mdi-circle align-middle font-size-14 me-2 text-danger"></i>
                                            Total ACoS
                                        </p>
                                        <h5>{{ isset($sd) ? $formatPercent($sd['acos']) : 'N/A' }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT SIDE -->
            <div class="col-xl-6 d-flex">
                <div class="card w-100">
                    <div class="card-body">
                        <div class="row">
                            <!-- LEFT – < 30% ACoS -->
                            <div class="col-sm-6 pe-sm-1 border-end border-4">
                                <a href="{{ route('admin.ads.overview.campaignOverview', $withFilters(['acos' => '30', 'campaign' => 'SD'])) }}"
                                    class="text-decoration-none d-block py-2">
                                    <div>
                                        <!-- Header -->
                                        <h5 class="font-size-14 mb-0 text-success">&lt; 30% ACoS</h5>
                                        <!-- Metrics -->
                                        <div class="row mt-2 text-center text-sm-start">
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Campaigns</span>
                                                <h6 class="mb-0 text-dark">
                                                    {{ $formatInt($sdBuckets['lt_30']['count'] ?? null) }}</h6>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Units</span>
                                                <h6 class="mb-0 text-dark">
                                                    {{ $formatInt($sdBuckets['lt_30']['units'] ?? null) }}</h6>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Spend</span>
                                                <h6 class="mb-0 text-dark">
                                                    {{ $formatMoney($sdBuckets['lt_30']['spend'] ?? null) }}</h6>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Sales</span>
                                                <h6 class="mb-0 text-dark">
                                                    {{ $formatMoney($sdBuckets['lt_30']['sales'] ?? null) }}</h6>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <!-- RIGHT – > 30% ACoS -->
                            <div class="col-sm-6 ps-sm-1">
                                <a href="{{ route('admin.ads.overview.campaignOverview', $withFilters(['acos' => '31', 'campaign' => 'SD'])) }}"
                                    class="text-decoration-none d-block py-2">
                                    <div class="ps-2">
                                        <!-- Header -->
                                        <h5 class="font-size-14 mb-0 text-warning">&gt; 30% ACoS</h5>
                                        <!-- Metrics -->
                                        <div class="row mt-2 text-center text-sm-start">
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Campaigns</span>
                                                <h6 class="mb-0 text-dark">
                                                    {{ $formatInt($sdBuckets['gte_30']['count'] ?? null) }}</h6>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Units</span>
                                                <h6 class="mb-0 text-dark">
                                                    {{ $formatInt($sdBuckets['gte_30']['units'] ?? null) }}</h6>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Spend</span>
                                                <h6 class="mb-0 text-dark">
                                                    {{ $formatMoney($sdBuckets['gte_30']['spend'] ?? null) }}</h6>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Sales</span>
                                                <h6 class="mb-0 text-dark">
                                                    {{ $formatMoney($sdBuckets['gte_30']['sales'] ?? null) }}</h6>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <!-- SECOND ROW (0% ACoS) -->
                        <div class="row">
                            <!-- LEFT 0% -->
                            <div class="col-sm-6 pe-sm-1 border-top border-end border-4">
                                <a href="{{ route('admin.ads.overview.campaignOverview', $withFilters(['acos' => '0', 'campaign' => 'SD'])) }}"
                                    class="text-decoration-none d-block py-2">
                                    <div>
                                        <div class="d-flex align-items-center mb-1">
                                            <h5 class="font-size-14 mb-0 text-danger me-2">0% ACoS</h5>
                                            <span class="text-muted small">(Spend > 0 && Sale = 0)</span>
                                        </div>

                                        <div class="row mt-2 text-center text-sm-start">
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Campaigns</span>
                                                <h6 class="mb-0 text-dark">
                                                    {{ $formatInt($sdBuckets['spend_gt_zero_sales']['count'] ?? null) }}
                                                </h6>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Units</span>
                                                <h6 class="mb-0 text-dark">
                                                    {{ $formatInt($sdBuckets['spend_gt_zero_sales']['units'] ?? null) }}
                                                </h6>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Spend</span>
                                                <h6 class="mb-0 text-dark">
                                                    {{ $formatMoney($sdBuckets['spend_gt_zero_sales']['spend'] ?? null) }}
                                                </h6>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted small d-block">Sales</span>
                                                <h6 class="mb-0 text-dark">
                                                    {{ $formatMoney($sdBuckets['spend_gt_zero_sales']['sales'] ?? null) }}
                                                </h6>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <!-- RIGHT 0% -->
                            <div class="col-sm-6 ps-sm-1 border-top border-4">
                                <div class="ps-2 py-2">
                                    <div class="d-flex align-items-center mb-1">
                                        <h5 class="font-size-14 mb-0 text-danger me-2">0% ACoS</h5>
                                        <span class="text-muted small">(Spend = 0 && Sale = 0)</span>
                                    </div>

                                    <div class="row mt-2 text-center text-sm-start">
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Campaigns</span>
                                            <h6 class="mb-0 text-dark">
                                                {{ $formatInt($sdBuckets['spend_zero_sales_zero_cnt']['count'] ?? null) }}
                                            </h6>
                                        </div>
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Units</span>
                                            <h6 class="mb-0 text-muted">
                                                {{ $formatInt($sdBuckets['spend_zero_sales_zero_cnt']['units'] ?? null) }}
                                            </h6>
                                        </div>
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Spend</span>
                                            <h6 class="mb-0 text-muted">
                                                {{ $formatMoney($sdBuckets['spend_zero_sales_zero_cnt']['spend'] ?? null) }}
                                            </h6>
                                        </div>
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Sales</span>
                                            <h6 class="mb-0 text-muted">
                                                {{ $formatMoney($sdBuckets['spend_zero_sales_zero_cnt']['sales'] ?? null) }}
                                            </h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            @if (session('no_data'))
                showToast('warning', @json(session('no_data')), 7000);
            @endif
        </script>
    @endpush
@endsection
