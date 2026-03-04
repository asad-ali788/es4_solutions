@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <h4 class="mb-0">Monthly Forecast Details ASINs — {{ $selectedMonth->format('F Y') }}</h4>
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
                {{-- <ul role="tablist" class="nav nav-tabs nav-tabs-custom pt-2 flex-column flex-sm-row">
                    @can('selling.sku')
                        <li class="nav-item">
                            <a href="{{ route('admin.selling.index') }}"
                                class="nav-link w-100 w-sm-auto {{ Request::is('admin/selling') ? 'active' : '' }}">
                                Selling Item (SKU)
                            </a>
                        </li>
                    @endcan
                    <hr class="d-sm-none my-0">
                    @can('selling.asin')
                        <li class="nav-item">
                            <a href="{{ route('admin.asin-selling.index') }}"
                                class="nav-link w-100 w-sm-auto {{ Request::is('admin/asin-selling*') ? 'active' : '' }}">
                                Selling Item (ASIN)
                            </a>
                        </li>
                    @endcan
                    <hr class="d-sm-none my-0">
                    @can('selling.ads-item')
                        <li class="nav-item">
                            <a href="{{ route('admin.selling.adsItems.index') }}"
                                class="nav-link w-100 w-sm-auto {{ Request::is('admin/sellingAdsItem*') ? 'active' : '' }}">
                                Ads Item (ASIN)
                            </a>
                        </li>
                    @endcan
                </ul> --}}
                <div class="card-body pt-2">
                    <div class="row g-3 align-items-center justify-content-between mb-3">
                        <div class="col-lg-9">
                            <form method="GET" action="{{ route('admin.orderforecastasin.asinDetailByMonth') }}"
                                class="row g-2">
                                <!-- Hidden type filter -->
                                <input type="hidden" name="type" value="asin">

                                <x-elements.search-box />
                                <!-- Month picker -->
                                <div class="col-auto">
                                    <div class="form-floating">
                                        <input type="month" name="month" id="month"
                                            class="form-control custom-dropdown-small"
                                            value="{{ request('month', now()->format('Y-m')) }}"
                                            max="{{ now()->format('Y-m') }}" onchange="this.form.submit()">
                                        <label for="month">Forecast Month</label>
                                    </div>
                                </div>

                                <div class="col-auto">
                                    <div class="form-floating">
                                        <select name="forecast_filter" class="form-select custom-dropdown-small"
                                            onchange="this.form.submit()">
                                            <option value="">All FC Fulfillment</option>
                                            <option value="gt100"
                                                {{ request('forecast_filter') == 'gt100' ? 'selected' : '' }}>
                                                Fulfillment > 100
                                            </option>
                                            <option value="50to100"
                                                {{ request('forecast_filter') == '50to100' ? 'selected' : '' }}>
                                                Fulfillment 50 - 100
                                            </option>
                                            <option value="lt50"
                                                {{ request('forecast_filter') == 'lt50' ? 'selected' : '' }}>
                                                Fulfillment < 50 </option>
                                        </select>

                                        <label for="forecast_filter">FCF %</label>
                                    </div>
                                </div>

                            </form>
                        </div>
                        <div class="col-lg-3 text-end">
                            @can('order_forecast.asin-monthly-export')
                                <a href="{{ route('admin.orderforecastasin.downloadOrderForecastAsinMonthlyExport', request()->query()) }}"
                                    class="btn btn-success btn-rounded waves-effect waves-light w-50 w-lg-auto"
                                    onclick="return confirm('Do you want to download the ASIN monthly report?');">
                                    <i class="mdi mdi-file-excel label-icon"></i> Excel Export
                                </a>
                            @endcan
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover"
                                id="asinDetailByMonth-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>ASIN</th>
                                        <th>Last Year Sold ({{ $selectedMonth->copy()->subYear()->format('M Y') }})</th>
                                        <th>Current Month Sold ({{ $selectedMonth->format('M Y') }})</th>
                                        <th>Forecast {{ $selectedMonth->format('M Y') }}</th>
                                        <th>FC Fulfillment %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (isset($asins) && $asins->count() > 0)
                                        @foreach ($asins as $asin)
                                            <tr>
                                                <td>{{ ($asins->currentPage() - 1) * $asins->perPage() + $loop->iteration }}
                                                </td>

                                                <td>{{ $asin->asin }}</td>

                                                <td>{{ number_format($asin->last_year_sold) }}</td>
                                                <td>{{ number_format($asin->current_month_sold) }}</td>
                                                <td>{{ number_format($asin->forecast_units) }}</td>
                                                <td>
                                                    @php
                                                        $fcf = $asin->fcf_percent;
                                                    @endphp

                                                    @if ($fcf !== null)
                                                        {{ number_format($fcf, 2) }}%

                                                        @if ($fcf < 50)
                                                            <i class="bx bx-chevrons-down text-danger fw-bold ms-1"
                                                                title="Very Low (<50%)"></i>
                                                        @elseif ($fcf >= 50 && $fcf < 60)
                                                            <i class="bx bx-down-arrow-alt text-danger ms-1"
                                                                title="Low (50–60%)"></i>
                                                        @elseif ($fcf >= 60 && $fcf < 70)
                                                            <i class="bx bx-right-arrow-alt text-warning ms-1"
                                                                title="Moderate (60–70%)"></i>
                                                        @elseif ($fcf >= 70 && $fcf < 80)
                                                            <i class="bx bx-up-arrow-alt text-success ms-1"
                                                                title="Good (70–80%)"></i>
                                                        @elseif ($fcf >= 80 && $fcf <= 90)
                                                            <i class="bx bx-chevrons-up text-success fw-bold ms-1"
                                                                title="Very Good (80–90%)"></i>
                                                        @endif
                                                    @else
                                                        --
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="100%" class="text-center">No data item available for this</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>

                            <div class="mt-2">
                                {{ $asins->appends(request()->query())->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection
