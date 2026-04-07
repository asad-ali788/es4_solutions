@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Dashboard</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                        <li class="breadcrumb-item active">ITrend</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================== -->
    <!-- Block: Welcome Back -->
    <!-- ===================================== -->
    <div class="row g-3 align-items-stretch">
        <!-- Left Card -->
        <div class="col-12 col-xl-4 d-flex">
            <div class="card w-100 flip-card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="flip-face-content h-100 d-flex flex-column">
                        <!-- Top right dropdown -->
                        <div class="d-flex justify-content-end position-relative mb-n3 mt-n1">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light backdrop-blur border-white-50 shadow-sm" type="button" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <i class="mdi mdi-dots-horizontal fs-5"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.dashboard.clearCache') }}">Reset
                                            Cache</a>
                                    </li>
                                     <li>
                                        <a class="dropdown-item" href="{{ route('admin.runDemoData') }}">
                                            Run demo data</a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="d-flex align-items-center flex-grow-1">
                            <div class="me-3">
                                <div class="position-relative">
                                    @if ($user->profile)
                                        <img src="{{ asset('storage/' . $user->profile) }}" alt="Profile Image"
                                            class="rounded-circle border border-2 border-white shadow-sm" style="width:72px;height:72px;object-fit:cover;">
                                    @else
                                        <div class="avatar-md rounded-circle border border-2 border-white shadow-sm">
                                            <span class="avatar-title rounded-circle bg-primary-subtle text-primary font-size-24 fw-bold">
                                                {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                                            </span>
                                        </div>
                                    @endif
                                    <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-white rounded-circle shadow-sm" title="Online"></span>
                                </div>
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <h4 class="mb-1 fw-bold text-truncate">{{ $user->name ?? 'User' }}</h4>
                                <p class="text-muted mb-2 fs-13 fw-medium">{{ $user->getRoleNames()->first() ?? 'role' }}</p>
                                
                                <div class="d-flex flex-column gap-1">
                                    <!-- First line: PST label + clock -->
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs me-2">
                                            <span class="avatar-title rounded-circle bg-info-subtle text-info font-size-14">
                                                <i class="mdi mdi-clock-outline"></i>
                                            </span>
                                        </div>
                                        <span class="pst-clock fw-bold text-primary fs-15"></span>
                                        <span class="text-muted ms-2 small fw-semibold">PST</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs me-2">
                                            <span class="avatar-title rounded-circle bg-warning-subtle text-warning font-size-14">
                                                <i class="mdi mdi-calendar"></i>
                                            </span>
                                        </div>
                                        <span class="pst-date text-muted fs-12 fw-medium"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <livewire:dashboard.welcome-weather-flip-card />

    </div>
    @if (!$error)
        <!-- ===================================== -->
        <!-- Block: Hourly Sales Snapshot  -->
        <!-- ===================================== -->
        @can('dashboard.sales-summery')
            <div class="row g-3 align-items-stretch">
                <!-- LEFT: Hourly Sales Snapshot (full height) -->
                <div class="col-12 col-xl-4">
                    <div class="card w-100 shadow-sm mb-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar-xs me-3">
                                    <span class="avatar-title rounded-circle glance glance-primary font-size-18">
                                        <i class="bx bx-timer"></i>
                                    </span>
                                </div>
                                <h5 class="font-size-14 mb-0">
                                    Hourly Sales Snapshot </h5>
                                <div class="ms-auto">
                                    @php
                                        $range = request('range', 'today'); // today | yesterday
                                    @endphp
                                    <div class="btn-group" role="group" aria-label="Date range">
                                        <input type="radio" class="btn-check" name="range" id="rangeToday"
                                            autocomplete="off" {{ $range === 'today' ? 'checked' : '' }}
                                            onclick="window.location.href='{{ request()->fullUrlWithQuery(['range' => 'today']) }}'">

                                        <label class="btn btn-outline-secondary btn-sm" for="rangeToday">
                                            Today
                                        </label>

                                        <input type="radio" class="btn-check" name="range" id="rangeYesterday"
                                            autocomplete="off" {{ $range === 'yesterday' ? 'checked' : '' }}
                                            onclick="window.location.href='{{ request()->fullUrlWithQuery(['range' => 'yesterday']) }}'">

                                        <label class="btn btn-outline-secondary btn-sm" for="rangeYesterday">
                                            Yesterday
                                        </label>
                                    </div>
                                </div>
                            </div>
                            @php
                                $rows = count($yesterdaySaleSummary['by_country'] ?? []);
                                $height = $rows > 2 ? 413 : 309;
                            @endphp
                            <div class="mt-3"
                                style="
                                    min-height: {{ $height }}px;
                                    max-height: {{ $height }}px;
                                    overflow-y: auto;
                                ">
                                @if ($dailySnapshot && $dailySnapshot->count())
                                    <ul class="verti-timeline list-unstyled mb-0">
                                        @foreach ($dailySnapshot as $snapshot)
                                            @php
                                                $isLatest = $loop->first; // mark first item as "active"
                                                // Parse snapshot time (hour start)
                                                $startTime = \Carbon\Carbon::parse($snapshot->snapshot_time, 'UTC');
                                                // Add 1 hour for range end
                                                $endTime = $startTime->copy()->addHour();
                                            @endphp

                                            <li class="event-list pb-4 {{ $isLatest ? 'active' : '' }}">
                                                <div class="event-timeline-dot">
                                                    <i
                                                        class="font-size-18 bx {{ $isLatest ? 'bxs-right-arrow-circle text-primary' : 'bx-right-arrow-circle' }}"></i>
                                                </div>
                                                <div class="flex-shrink-0 d-flex">
                                                    <!-- Time Range -->
                                                    <div class="me-3">
                                                        <h5 class="font-size-12 mb-1">
                                                            {{ $startTime->format('h:i A') }}
                                                            –
                                                            {{ $endTime->format('h:i A') }}
                                                            <i
                                                                class="bx bx-right-arrow-alt font-size-14 text-primary align-middle ms-1"></i>
                                                        </h5>
                                                        <small class="text-muted">
                                                            {{ $startTime->format('M d, Y') }}
                                                        </small>
                                                    </div>
                                                    <!-- Units Section -->
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex flex-column align-items-end">
                                                            <h5 class="mb-0 fw-semibold" style="font-size: 14px;">
                                                                {{ $snapshot->total_units ?? 0 }}
                                                            </h5>
                                                            <small class="text-muted">Units Sold</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div
                                        class="d-flex flex-column align-items-center justify-content-center text-center text-muted small p-3">
                                        <div class="w-50 opacity-50" style="filter: grayscale(50%) blur(.5px);">
                                            <img src="{{ asset('assets/images/empty-folder.png') }}" alt="No data"
                                                class="img-fluid" style="max-width: 120px;">
                                        </div>
                                        <div class="mb-2">No data available now</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <livewire:dashboard.hourly-snapshot-chart marketTz="America/Los_Angeles" />
                </div>

                <!-- RIGHT: Hourly Revenue + Hourly Units (top) & Yesterday's Summary (bottom) -->
                <div class="col-12 col-xl-8 d-flex flex-column gap-3 order-xl-2">
                    <div class="row g-3">
                        <!-- Hourly Revenue -->
                        <div class="col-12 col-md-6 d-flex">
                            <div class="card w-100 shadow-sm mb-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="avatar-xs me-3">
                                            <span class="avatar-title rounded-circle glance glance-success font-size-18">
                                                <i class="bx bx-dollar-circle"></i>
                                            </span>
                                        </div>
                                        <h5 class="font-size-14 mb-0">
                                            Hourly Revenue —
                                            {{ $hourly['last_updated'] !== 'N/A' ? \Carbon\Carbon::parse($hourly['last_updated'])->timezone(config('timezone.market'))->format('F j, Y T') : 'N/A' }}
                                        </h5>
                                    </div>
                                    <div class="text-muted mt-4">
                                        <h4 class="mb-2">
                                            ${{ $hourly['revenue'] ?? 0 }}
                                            <i
                                                class="{{ $hourly['revenue_meta']['icon'] ?? '' }} ms-1 {{ $hourly['revenue_meta']['is_up'] ?? false ? 'text-success' : 'text-danger' }}"></i>
                                        </h4>
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <div class="d-flex align-items-center">
                                                <span
                                                    class="badge {{ $hourly['revenue_meta']['badge_class'] ?? 'badge-soft-secondary' }} font-size-12">
                                                    {{ $hourly['revenue_meta']['symbol'] ?? '' }}{{ abs($hourly['revenue_change'] ?? 0) }}%
                                                </span>
                                                <span class="ms-2 text-truncate">From Yesterday</span>
                                            </div>
                                            @if (!empty($hourly['last_updated']))
                                                <div class="text-muted font-size-12 text-end ms-auto" title="Last Updated At">
                                                    <span class="pulse pulse-success me-1"
                                                        style="width: 8px; height: 8px; display: inline-block;"></span>
                                                    <span>LU:</span>
                                                    {{ \Carbon\Carbon::parse($hourly['last_updated'])->timezone(config('timezone.market'))->format('h:i A T') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Hourly Units -->
                        <div class="col-12 col-md-6 d-flex">
                            <div class="card w-100 shadow-sm mb-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="avatar-xs me-3">
                                            <span class="avatar-title rounded-circle glance glance-primary font-size-18">
                                                <i class="bx bx-timer"></i>
                                            </span>
                                        </div>
                                        <h5 class="font-size-14 mb-0">
                                            Hourly Units —
                                            {{ $hourly['last_updated'] !== 'N/A' ? \Carbon\Carbon::parse($hourly['last_updated'])->timezone(config('timezone.market'))->format('F j, Y T') : 'N/A' }}
                                        </h5>
                                    </div>
                                    <div class="text-muted mt-4">
                                        <h4 class="mb-2">
                                            Units: {{ $hourly['units'] ?? 0 }}
                                            <i
                                                class="{{ $hourly['units_meta']['icon'] ?? '' }} ms-1 {{ $hourly['units_meta']['is_up'] ?? false ? 'text-success' : 'text-danger' }}"></i>
                                        </h4>
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <div class="d-flex align-items-center">
                                                <span
                                                    class="badge {{ $hourly['units_meta']['badge_class'] ?? 'badge-soft-secondary' }} font-size-12">
                                                    {{ $hourly['units_meta']['symbol'] ?? '' }}{{ abs($hourly['units_change'] ?? 0) }}%
                                                </span>
                                                <span class="ms-2 text-truncate">From Yesterday</span>
                                            </div>
                                            @if (!empty($hourly['last_updated']))
                                                <div class="text-muted font-size-12 text-end ms-auto" title="Last Updated At">
                                                    <span class="pulse pulse-success me-1"
                                                        style="width: 8px; height: 8px; display: inline-block;"></span>
                                                    <span>LU:</span>
                                                    {{ \Carbon\Carbon::parse($hourly['last_updated'])->timezone(config('timezone.market'))->format('h:i A T') }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Forecast Cards -->
                    <div class="row g-3">
                        <!-- Forecast ASIN -->
                        @php
                            $monthParam = now()->format('Y-m');
                        @endphp

                        <div class="col-12 col-md-6 d-flex">
                            @can('order_forecast.asin-monthly')
                                <a href="{{ route(
                                    'admin.orderforecastasin.asinDetailByMonth',
                                    array_merge(request()->query(), [
                                        'type' => 'asin',
                                        'month' => $monthParam,
                                    ]),
                                ) }}"
                                    class="w-100 text-decoration-none">
                                @else
                                    <div class="w-100 cursor-not-allowed"
                                        title="ASIN monthly forecast access restricted. Contact admin.">
                                    @endcan
                                    <div class="card w-100 mb-0 shadow-sm">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="avatar-xs me-3">
                                                    <span class="avatar-title rounded-circle glance glance-info font-size-18">
                                                        <i class="bx bx-bar-chart"></i>
                                                    </span>
                                                </div>
                                                <h5 class="font-size-14 mb-0">
                                                    Forecast Units (ASIN) — {{ now()->format('F Y') }}
                                                </h5>
                                            </div>

                                            <div class="text-muted mt-4">
                                                <h4 class="mb-2">
                                                    {{ number_format($forecast['asin_units'] ?? 0) }}
                                                    <span class="text-muted font-size-14">Units</span>
                                                </h4>
                                                <div class="d-flex align-items-center w-100">
                                                    <p class="mb-0 text-muted font-size-12 flex-grow-1">
                                                        Total forecasted units across all ASINs
                                                    </p>
                                                    @can('order_forecast.asin-monthly')
                                                        <span
                                                            class="rounded-circle glance glance-light font-size-18 flex-shrink-0 ms-2"
                                                            data-bs-toggle="tooltip" title="Detailed ASIN-wise forecast">
                                                            <i class="bx bx-right-arrow-alt p-1"></i>
                                                        </span>
                                                    @endcan
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @can('order_forecast.asin-monthly')
                                </a>
                            @else
                            </div>
                        @endcan
                    </div>

                    <!-- Forecast SKU -->
                    <div class="col-12 col-md-6 d-flex">
                        <div class="card w-100 shadow-sm mb-0">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="avatar-xs me-3">
                                        <span class="avatar-title rounded-circle glance glance-success font-size-18">
                                            <i class="bx bx-line-chart"></i>
                                        </span>
                                    </div>
                                    <h5 class="font-size-14 mb-0">
                                        FCF % (ASIN) — {{ now()->format('F Y') }}
                                    </h5>
                                </div>

                                <div class="text-muted mt-4">
                                    <h4 class="mb-2">
                                        {{ $forecast['fcf_asin'] ?? 0 }}%
                                    </h4>
                                    <p class="text-muted font-size-12 mb-0">
                                        MTD sales vs forecasted proportion of the month
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Yesterday's Sales Summary (bottom full width) -->
                <div class="col-12">
                    <div class="card w-100 shadow-sm mb-0">
                        <div class="card-body">
                            <div class="d-flex mb-2 justify-content-between gap-2 flex-wrap">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-xs me-3">
                                        <span
                                            class="avatar-title rounded-circle bg-warning-subtle text-warning glance glance-warning font-size-18">
                                            <i class="bx bx-wallet"></i>
                                        </span>
                                    </div>
                                    <h4 class="font-size-14 mb-0">
                                        Yesterday's Sales Summary -
                                        {{ ($yesterdaySaleSummary['summary']['sale_date'] ?? 'N/A') !== 'N/A'
                                            ? \Carbon\Carbon::parse($yesterdaySaleSummary['summary']['sale_date'])->format('F j, Y')
                                            : 'N/A' }}
                                        PST
                                    </h4>
                                </div>

                                <div class="hstack gap-2 justify-content-center">
                                    <span class="badge badge-soft-info">Amazon.com</span>
                                    <span class="badge badge-soft-info">Amazon.ca</span>
                                    <span class="badge badge-soft-info">Amazon.com.mx</span>
                                </div>
                            </div>

                            <div class="row g-3">
                                <!-- Total Revenue Summary -->
                                <div class="col-12 col-lg-4">
                                    <div class="mt-2">
                                        <p>
                                            <span class="pulse-dot pulse-success me-1"
                                                style="width: 8px; height: 8px; display: inline-block;"></span>
                                            Total Units:
                                        </p>
                                        <h4 class="mt-0">
                                            {{ number_format((int) data_get($yesterdaySaleSummary, 'summary.total_units', 0)) ?: 'N/A' }}
                                        </h4>
                                        <p class="text-muted mb-2">
                                            Total Revenue:
                                            ${{ number_format((float) data_get($yesterdaySaleSummary, 'summary.total_revenue_usd', 0), 2) ?? 'N/A' }}
                                            USD
                                        </p>
                                    </div>
                                </div>

                                @php $flagMap = config('flagmap'); @endphp
                                @foreach (data_get($yesterdaySaleSummary, 'by_country', []) as $row)
                                    @php
                                        $countryCode = strtoupper(substr($row['country'], 0, 2));
                                        $flag = $flagMap[$countryCode]['file'] ?? 'default.jpg';
                                        $countryName = $flagMap[$countryCode]['name'] ?? $row['country'];
                                    @endphp
                                    @if ($row['total_units'] > 0)
                                        <div class="col-12 col-sm-6 col-lg-4 align-self-center">
                                            <div class="pt-2">
                                                <p class="mb-2 d-flex align-items-center">
                                                    <img src="{{ asset('assets/images/flags/' . $flag) }}"
                                                        alt="{{ $countryName }}" width="20" class="me-2" />
                                                    <span>{{ $countryName }}</span>
                                                </p>
                                                <h5>
                                                    {{ number_format($row['total_units']) }} Units =
                                                    <span class="text-muted font-size-14">
                                                        ${{ number_format($row['revenue_usd'], 2) ?? 'N/A' }} USD
                                                    </span>
                                                </h5>
                                                <p class="text-muted font-size-12 mb-2">
                                                    Revenue: ${{ number_format($row['total_revenue'], 2) ?? 'N/A' }}
                                                    {{ $row['currency'] ?? 'N/A' }}
                                                </p>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach

                                <!-- View More Button always on the right -->
                                <div class="col-12 d-flex justify-content-end align-items-center mt-0">
                                    <a href="{{ route('admin.dashboard.monthToDateDailyView') }}"
                                        class="btn btn-light btn-sm">
                                        MTD Daily Sales Summary
                                        <i class="bx bx-right-arrow-alt ms-1"></i>
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <livewire:dashboard.month-to-date-year-comparisons :marketplaceMap="$marketplaceMap" marketTz="America/Los_Angeles" lazy />
            </div>
            </div>
        @endcan

        <!-- ===================================== -->
        <!-- Block: Top 10 Selling Product  Todays's Sales Summary -->
        <!-- ===================================== -->
        @can('dashboard.top-sales-and-campaigns')
            <div class="row g-3 mt-0 align-items-stretch">
                <!-- LEFT (4): Top 10 Selling Product -->
                <livewire:dashboard.top-selling-products marketTz="America/Los_Angeles" lazy />
                <!-- RIGHT (8): Two cards side-by-side -->
                <div class="col-12 col-xl-8">
                    <div class="row g-3 align-items-stretch">
                        <!-- Today's Sales Summary -->
                        <livewire:dashboard.todays-campaign-sales-summery marketTz="America/Los_Angeles" lazy />
                        <!-- Yesterday's Sales Summary -->
                        <livewire:dashboard.yesterday-campaign-sales-summery marketTz="America/Los_Angeles" lazy />
                    </div>
                </div>
            </div>
        @endcan
        <!-- ===================================== -->
        <!-- Block: Performance Charts -->
        <!-- ===================================== -->
        @can('dashboard.performance-graphs')
            <livewire:dashboard.performance-charts class="w-100" />
        @endcan
        <!-- ===================================== -->
        <!-- Row: Top 10 Campaigns (6 / 6) -->
        <!-- ===================================== -->
        @can('dashboard.top-campaigns')
            <livewire:dashboard.top-campaigns-table marketTz="America/Los_Angeles" lazy />
        @endcan
        <!-- end row -->
    @else
        @include('errors.exception')
    @endif
@endsection
