<div>
    @php
        // Pick which segment you are showing (now: SP MANUAL)
        $type = request('campaign', 'SP');
        $mode = request('sp_targeting_type', 'MANUAL');

        $spAuto = $overview['by_type']['SP']['AUTO'] ?? null;
        $spManual = $overview['by_type']['SP']['MANUAL'] ?? null;
        $sb = $overview['by_type']['SB'] ?? null;
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
    @can('selling.ads-item.dashboard.totalcampaign')
        {{-- Row 1: 4 metric cards --}}
        <div class="accordion mb-2" id="accordionmetrics">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed fs-5 fw-bold acc-btn-theme" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="true"
                        aria-controls="collapseTwo">
                        Total Campaigns (SP + SB + SD)
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo"
                    data-bs-parent="#accordionmetrics">
                    <div class="accordion-body acc-btn-theme">
                        <div class="row">
                            <div class="col-xl-6 mb-0">
                                <div class="card mb-0">
                                    <div class="card-body">
                                        <h4 class="card-title mb-3">Total Campaigns</h4>
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
                                                            href="{{ route('admin.ads.overview.campaignOverview', array_merge($filters, ['campaign' => 'SP'])) }}">View
                                                            more <i class="mdi mdi-arrow-right ms-1"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="align-self-center col-lg-4 ps-3">
                                                <div class="row d-flex flex-lg-column align-items-lg-center text-lg-center">
                                                    <!-- Total Units -->
                                                    <div class="col-6 col-lg-12 mt-4 pt-2">
                                                        <p class="mb-2">
                                                            <i
                                                                class="mdi mdi-circle align-middle font-size-14 me-2 text-primary"></i>
                                                            Total Units
                                                        </p>
                                                        <h3> {{ $total ? $total['units'] : 'N/A' }}</h3>
                                                    </div>
                                                    <!-- ACoS -->
                                                    <div class="col-6 col-lg-12 mt-4 pt-2">
                                                        <p class="mb-2">
                                                            <i
                                                                class="mdi mdi-circle align-middle font-size-14 me-2 text-success"></i>
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
                                        <a href="{{ route('admin.ads.overview.campaignOverview', array_merge($filters, ['acos' => '30'])) }}"
                                            class="text-decoration-none">
                                            <div class="card hover-card clickable-card mb-2">
                                                <div class="card-body">
                                                    <!-- Header -->
                                                    <div class="d-flex align-items-center mb-2">
                                                        <div class="avatar-xs me-3">
                                                            <span
                                                                class="avatar-title rounded-circle glance glance-success font-size-18">
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
                                        <a href="{{ route('admin.ads.overview.campaignOverview', array_merge($filters, ['acos' => '31'])) }}"
                                            class="text-decoration-none">
                                            <div class="card hover-card clickable-card mb-2">
                                                <div class="card-body">
                                                    <!-- Header -->
                                                    <div class="d-flex align-items-center mb-2">
                                                        <div class="avatar-xs me-3">
                                                            <span
                                                                class="avatar-title rounded-circle glance glance-warning font-size-18">
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
                                        <a href="{{ route('admin.ads.overview.campaignOverview', array_merge($filters, ['acos' => '0'])) }}"
                                            class="text-decoration-none">
                                            <div class="card hover-card clickable-card mb-2">
                                                <div class="card-body">
                                                    <!-- Header -->
                                                    <div class="d-flex align-items-center mb-2">
                                                        <div class="avatar-xs me-3">
                                                            <span
                                                                class="avatar-title rounded-circle glance glance-danger font-size-18">
                                                                <i class="bx bx-error-circle"></i>
                                                            </span>
                                                        </div>
                                                        <h5 class="font-size-14 mb-0 text-dark me-2">0% ACoS</h5>
                                                        <span class="text-muted small d-block">(Spend > 0 && Sale =
                                                            0)</span>
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
                                        <a href="{{ route('admin.ads.overview.campaignOverview', array_merge($filters, ['acos' => 'none', 'spend' => '0'])) }}"
                                            class="text-decoration-none">
                                            <div class="card  hover-card clickable-card mb-2">
                                                <div class="card-body">
                                                    <!-- Header -->
                                                    <div class="d-flex align-items-center mb-2">
                                                        <div class="avatar-xs me-3">
                                                            <span
                                                                class="avatar-title rounded-circle glance glance-danger font-size-18">
                                                                <i class="bx bx-error"></i>
                                                            </span>
                                                        </div>
                                                        <h5 class="font-size-14 mb-0 text-dark me-2">0% ACoS</h5>
                                                        <span class="text-muted small d-block">(Spend = 0 && Sale =
                                                            0)</span>
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
                    </div>
                </div>
            </div>
        </div>
    @endcan
    {{-- Row 2:4 SP AUTO --}}

    <div class="row align-items-stretch">
        <!-- LEFT SIDE (SP SUMMARY + AUTO/MANUAL SPLIT) -->
        <div class="col-xl-6 d-flex">
            <div class="card w-100 mb-2">
                <div class="card-body">
                    <h4 class="card-title mb-0">Campaigns SP</h4>
                    <div class="row mb-2 pb-2">
                        <div class="col-lg-6">
                            <div class="mt-2">
                                <p>Total Campaigns</p>
                                <h4>{{ $totalsp ?? 'N/A' }}</h4>
                                <div class="mt-2">
                                    <a class="btn btn-light btn-sm"
                                        href="{{ route('admin.ads.overview.campaignOverview', array_merge($filters, ['campaign' => 'SP'])) }}">
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
                                    <h6 class="mb-0 text-dark">{{ $overview['counts']['SP']['AUTO'] ?? 'N/A' }}
                                    </h6>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted small d-block">Units</span>
                                    <h6 class="mb-0 text-dark">{{ isset($spAuto) ? $spAuto['units'] : 'N/A' }}
                                    </h6>
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
                                    <h6 class="mb-0 text-dark">{{ $overview['counts']['SP']['MANUAL'] ?? 'N/A' }}
                                    </h6>
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
            <div class="card w-100 mb-2">
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
                                <a href="{{ route('admin.ads.overview.campaignOverview', array_merge($filters, ['acos' => '30', 'campaign' => 'SP'])) }}"
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
                                <a href="{{ route('admin.ads.overview.campaignOverview', array_merge($filters, ['acos' => '31', 'campaign' => 'SP'])) }}"
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
                                <a href="{{ route('admin.ads.overview.campaignOverview', array_merge($filters, ['acos' => '30', 'campaign' => 'SP'])) }}"
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
                                <a href="{{ route('admin.ads.overview.campaignOverview', array_merge($filters, ['acos' => '30', 'campaign' => 'SP'])) }}"
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
                                <a href="{{ route('admin.ads.overview.campaignOverview', array_merge($filters, ['acos' => '31', 'campaign' => 'SP'])) }}"
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
                                <a href="{{ route('admin.ads.overview.campaignOverview', array_merge($filters, ['acos' => '0', 'campaign' => 'SP'])) }}"
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
</div>
