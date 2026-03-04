@extends('layouts.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <h4 class="mb-0">
                        Month-to-Date Daily Sales
                        <small class="text-muted">
                            ({{ \Carbon\Carbon::parse($summary['start_date'])->format('d M Y') }} →
                            {{ \Carbon\Carbon::parse($summary['end_date'])->format('d M Y') }})
                        </small>
                    </h4>
                </div>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            <a href="{{ route('admin.dashboard') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Dashboard
                            </a>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body pt-2">
                    <div class="row g-3 align-items-center justify-content-between">
                        <!-- Left: Month selector & search -->
                        <div class="col-lg-9 d-flex flex-wrap align-items-center gap-2">
                            <form method="GET"
                                action="{{ route('admin.dashboard.monthToDateDailyView', request()->query()) }}"
                                class="d-flex flex-wrap align-items-center gap-2 w-100">

                                <x-elements.search-box />

                                <div class="form-floating">
                                    <input type="month" name="month" id="month"
                                        class="form-control custom-dropdown-small"
                                        value="{{ request('month', now()->format('Y-m')) }}"
                                        max="{{ now()->format('Y-m') }}" onchange="this.form.submit()">
                                    <label for="month">Forecast Month</label>
                                </div>
                            </form>
                        </div>

                        <!-- Right: Refresh button -->
                        <div class="col-lg-3 d-flex justify-content-lg-end mt-2 mt-lg-0">
                            <form action="{{ route('admin.dashboard.flushMtdDailyCache') }}" method="GET">
                                <button type="submit" class="btn btn-light btn-sm d-flex align-items-center gap-1">
                                    <i class="bx bx-loader text-primary"></i>
                                    Refresh
                                </button>
                            </form>
                        </div>
                    </div>

                </div>

                <div class="table-responsive">
                    <table class="table align-middle table-nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Date</th>

                                <th>Total Units Sold (US+CA+MX)</th>
                                <th>Total Units (Ads)</th>
                                <th>Total Sales (Ads)</th>
                                <th>Total Spend (Ads)</th>
                                @foreach (array_keys($byCountry) as $country)
                                    <th>{{ $country }} Units</th>
                                    <th>{{ $country }} Revenue</th>
                                    <th>{{ $country }} Revenue (USD)</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $row = 1;

                                // All unique dates
                                $allDates = collect($byCountry)->flatten(1)->pluck('sale_date')->unique()->sortDesc();
                                $dailyTotalsMap = collect($dailyTotals)->keyBy('report_week');
                            @endphp

                            @foreach ($allDates as $date)
                                @php
                                    $daily = $dailyTotalsMap->get($date);
                                    $totalSoldUnits = $totalUnitsMtd[$date] ?? 0;
                                @endphp

                                <tr>
                                    {{-- # --}}
                                    <td>{{ $row++ }}</td>

                                    {{-- Date --}}
                                    <td>{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</td>

                                    {{-- Total Units Sold (US+CA+MX) --}}
                                    <td class="table-warning fw-semibold text-end">
                                        {{ number_format($totalSoldUnits) }}
                                    </td>

                                    {{-- Total Units (Ads) --}}
                                    <td class="table-info fw-semibold text-end">
                                        {{ number_format($daily->total_units ?? 0) }}
                                    </td>

                                    {{-- Total Sales (Ads) --}}
                                    <td class="table-info fw-semibold text-end">
                                        {{ number_format($daily->total_sales ?? 0, 2) }}
                                    </td>

                                    {{-- Total Spend (Ads) --}}
                                    <td class="table-info fw-semibold text-end">
                                        {{ number_format($daily->total_spend ?? 0, 2) }}
                                    </td>

                                    {{-- Country wise columns --}}
                                    @foreach (array_keys($byCountry) as $country)
                                        @php
                                            $item = collect($byCountry[$country])->firstWhere('sale_date', $date);
                                        @endphp

                                        <td class="table-light text-end">
                                            {{ number_format($item['total_units'] ?? 0) }}
                                        </td>

                                        <td class="table-success text-end">
                                            {{ number_format($item['total_revenue'] ?? 0, 2) }}
                                        </td>

                                        <td class="table-primary text-end">
                                            {{ number_format($item['revenue_usd'] ?? 0, 2) }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>

                    </table>

                </div>

                {{-- <div class="mt-2">
                        {{ $records->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div> --}}
            </div>
        </div>
    </div>
    </div>
@endsection
