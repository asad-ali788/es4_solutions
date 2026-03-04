@extends('layouts.app')

@section('content')
    @php
        $flagMap = config('flagmap');

        $currentCountryCode = strtoupper($listing->country ?? 'US');
        $currentFlag = $flagMap[$currentCountryCode]['file'] ?? 'us.jpg';
        $currentCountryName = $flagMap[$currentCountryCode]['name'] ?? 'United States';
        $dayNames = $dailyReport['dayNames'] ?? [];
        $days = $dailyReport['days'] ?? [];
    @endphp
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <h4 class="mb-0">Selling Dashboard ASIN - {{ $asin ?? 'N/A' }}</h4>
                </div>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            <a href="{{ route('admin.asin-selling.index') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Selling Items
                            </a>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="left-panel">
                @can('selling.daily-sales')
                    <div class="card border-2">
                        <div class="card-body">
                            <div class="h4 card-title">Current Week Daily Sales & Campaign Report by Market</div>
                            <div class="card-title-desc card-subtitle">
                                Sales by region with ad campaign performance for the last 7 days.
                            </div>

                            @if (!empty($dailyReport['summary']) && !empty($dailyReport['days']))
                                <div class="table-responsive">
                                    <table class="table table-bordered mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Region</th>
                                                @foreach ($dailyReport['days'] as $day)
                                                    <th>{{ $dailyReport['dayNames'][$day] ?? $day }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $codeMap = [
                                                    'USA' => 'US',
                                                    'CA' => 'CA',
                                                    'MX' => 'MX',
                                                ];
                                            @endphp

                                            @foreach ($dailyReport['summary'] as $region => $data)
                                                @php
                                                    $code = $codeMap[$region] ?? strtoupper($region ?? 'US');
                                                    $flag = $flagMap[$code]['file'] ?? 'us.jpg';
                                                    $hasCampaign = in_array($code, ['US', 'CA']);

                                                    $currencySymbol = $currencies[$code] ?? '$';

                                                @endphp

                                                {{-- Row 1: Sales --}}
                                                <tr>
                                                    <td class="align-middle" rowspan="2">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <img src="{{ asset('assets/images/flags/' . $flag) }}"
                                                                alt="flag" height="18" class="rounded">
                                                            <span class="fw-semibold">{{ $region ?? '-' }}</span>
                                                        </div>
                                                        <div class="small text-muted mt-1">Ad Spend</div>
                                                    </td>

                                                    @foreach ($dailyReport['days'] as $day)
                                                        @php
                                                            $sales = $data[$day]['units'] ?? 0;
                                                            $revenue = $data[$day]['revenue'] ?? 0;
                                                        @endphp
                                                        <td class="align-middle text-center fw-bold">
                                                            {{ $sales }}
                                                            @if ($revenue > 0)
                                                                <div class="small text-primary">
                                                                    {{ $currencySymbol }}{{ $revenue }}
                                                                </div>
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                </tr>


                                                {{-- Row 2: SP + ACOS + TACoS --}}
                                                <tr>
                                                    @foreach ($dailyReport['days'] as $day)
                                                        @php
                                                            $sp = $campaignReport['sp'][$code][$day] ?? [
                                                                'spTotal' => 0,
                                                            ];
                                                            $sb = $campaignReport['sb'][$code][$day] ?? [
                                                                'sbTotal' => 0,
                                                            ];
                                                            $sd = $campaignReport['sd'][$code][$day] ?? [
                                                                'sdTotal' => 0,
                                                            ];
                                                            $metrics =
                                                                $campaignReport['campaignMetrics'][$code][$day] ?? [];

                                                            // Calculate cumulative ad spend
                                                            $totalAdSpend =
                                                                ($sp['spTotal'] ?? 0) +
                                                                ($sb['sbTotal'] ?? 0) +
                                                                ($sd['sdTotal'] ?? 0);
                                                        @endphp
                                                        <td class="text-center small"
                                                            style="min-width: 100px; white-space: nowrap;">
                                                            <div class="fw-bold {{ $totalAdSpend > 0 ? 'text-success' : '' }}">
                                                                {{ $currencySymbol }}{{ number_format($totalAdSpend, 2) }}
                                                            </div>
                                                            <div>
                                                                ACOS:
                                                                @if (!empty($metrics['total_acos']) && $metrics['total_acos'] != 0)
                                                                    {{ number_format($metrics['total_acos'], 2) }}%
                                                                @else
                                                                    -
                                                                @endif
                                                            </div>
                                                            <div>
                                                                TACoS:
                                                                @if (!empty($metrics['total_tacos']) && $metrics['total_tacos'] != 0)
                                                                    {{ number_format($metrics['total_tacos'], 2) }}%
                                                                @else
                                                                    -
                                                                @endif
                                                            </div>
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>

                                    </table>
                                </div>
                            @else
                                <p class="text-danger fw-bold mt-3 mb-0">
                                    ⚠️ Unable to load daily sales data.
                                </p>
                            @endif
                        </div>
                    </div>
                @endcan
                @can('selling.advertising-cost')
                    <div class="card border-2">
                        <div class="card-body">
                            <div class="h4 card-title">Advertising Cost</div>
                            <div class="card-title-desc card-subtitle">
                                Combined Sponsored Product, Brand & Display data for the past 7 days.
                            </div>

                            <div class="table-responsive mt-3">
                                <table class="table table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Region</th>
                                            <th>Type</th>
                                            @foreach ($campaignReport['days'] as $day)
                                                <th>{{ $campaignReport['dayNames'][$day] ?? $day }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach (['US', 'CA'] as $region)
                                            @php
                                                $spData = $campaignReport['sp'][$region] ?? [];
                                                $sbData = $campaignReport['sb'][$region] ?? [];
                                                $sdData = $campaignReport['sd'][$region] ?? [];
                                                $flag = $flagMap[$region]['file'] ?? 'us.jpg';
                                            @endphp
                                            {{-- Row 1: SP --}}
                                            <tr>
                                                <td rowspan="3">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <img src="{{ asset('assets/images/flags/' . $flag) }}"
                                                            alt="{{ $region }} flag" height="16">
                                                        <span>{{ $region }}</span>
                                                    </div>
                                                </td>
                                                <td><strong>SP</strong></td>
                                                @foreach ($campaignReport['days'] as $day)
                                                    @php
                                                        $spSpendRaw = $spData[$day]['cost'] ?? 0;
                                                        $spSalesRaw = $spData[$day]['sales7d'] ?? 0;
                                                    @endphp
                                                    <td>
                                                        @if ($spSpendRaw == 0 && $spSalesRaw == 0)
                                                            -
                                                        @else
                                                            <div><strong>Spend:</strong> ${{ number_format($spSpendRaw, 2) }}
                                                            </div>
                                                            <div><strong>Sales:</strong> ${{ number_format($spSalesRaw, 2) }}
                                                            </div>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>

                                            {{-- Row 2: SB --}}
                                            <tr>
                                                <td><strong>SB</strong></td>
                                                @foreach ($campaignReport['days'] as $day)
                                                    @php
                                                        $sbSpendRaw = $sbData[$day]['cost'] ?? 0;
                                                        $sbSalesRaw = $sbData[$day]['sales7d'] ?? 0;
                                                    @endphp
                                                    <td>
                                                        @if ($sbSpendRaw == 0 && $sbSalesRaw == 0)
                                                            -
                                                        @else
                                                            <div><strong>Spend:</strong> ${{ number_format($sbSpendRaw, 2) }}
                                                            </div>
                                                            <div><strong>Sales:</strong> ${{ number_format($sbSalesRaw, 2) }}
                                                            </div>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>

                                            {{-- Row 3: SD --}}
                                            <tr>
                                                <td><strong>SD</strong></td>
                                                @foreach ($campaignReport['days'] as $day)
                                                    @php
                                                        $sdSpendRaw = $sdData[$day]['cost'] ?? 0;
                                                        $sdSalesRaw = $sdData[$day]['sales7d'] ?? 0;
                                                    @endphp
                                                    <td>
                                                        @if ($sdSpendRaw == 0 && $sdSalesRaw == 0)
                                                            -
                                                        @else
                                                            <div><strong>Spend:</strong> ${{ number_format($sdSpendRaw, 2) }}
                                                            </div>
                                                            <div><strong>Sales:</strong> ${{ number_format($sdSalesRaw, 2) }}
                                                            </div>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endcan

                <div class="card border-2">
                    <div class="card-body">
                        <div class="h4 card-title">ASIN Campaign Summary</div>
                        <div class="card-title-desc card-subtitle">
                            Campaign data for ASIN: <strong>{{ $asin }}</strong>
                        </div>

                        @if ($recommendations->count())
                            <div class="table-responsive mt-3">
                                <table class="table table-bordered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Region</th>
                                            <th>Campaign Type</th>
                                            <th>Active Campaigns</th>
                                            <th style="width: 120px;">Daily Budget</th>
                                            <th style="width: 120px;">Spend</th>
                                            <th style="width: 120px;">Sales</th>
                                            <th>ACOS (%)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recommendations as $rec)
                                            @php
                                                $currencySymbol = $currencies[$rec->country] ?? '$';
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        {{ $rec->country }}
                                                        @if ($rec->country == 'US')
                                                            <img src="{{ asset('assets/images/flags/us.jpg') }}"
                                                                alt="US Flag" class="ms-1" height="16">
                                                        @elseif($rec->country == 'CA')
                                                            <img src="{{ asset('assets/images/flags/canada.jpg') }}"
                                                                alt="CA Flag" class="ms-1" height="16">
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>{{ $rec->campaign_types }}</td>
                                                <td>{{ $rec->total_active_campaigns }}</td>
                                                <td style="width: 120px;">{{ $currencySymbol }}
                                                    {{ number_format($rec->total_daily_budget, 2) }}</td>
                                                <td style="width: 120px;">{{ $currencySymbol }}
                                                    {{ number_format($rec->total_spend, 2) }}</td>
                                                <td style="width: 120px;">{{ $currencySymbol }}
                                                    {{ number_format($rec->total_sales, 2) }}</td>
                                                <td>{{ number_format($rec->avg_acos, 2) }}%</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                            </div>
                        @else
                            <p class="text-danger fw-bold mt-3 mb-0">
                                No campaign recommendations available for this ASIN.
                            </p>
                        @endif
                    </div>
                </div>
                @can('selling.product-ranking')
                    <livewire:selling.asin-ranking-table :asin="$asin" />
                @endcan

                @can('selling.product-price-range')
                    <livewire:selling.asin-price-range-table :asin="$asin" />
                @endcan

            </div>
        </div>
        <div class="col-lg-6">
            <div class="right-panel">

                @can('selling.weekly-sales')
                    <div class="card border-2">
                        <div class="card-body">
                            <div class="h4 card-title">Last 6 Weeks Sales by Market</div>
                            <div class="card-title-desc card-subtitle">
                                Total units sold per region for each of the last 6 weeks
                            </div>
                            @if (!empty($weeklyReport['summary']) && !empty($weeklyReport['weeks']))
                                <div class="table-responsive">
                                    <table class="table table-bordered mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Region</th>
                                                @foreach ($weeklyReport['weeks'] as $week)
                                                    <th>{{ $week }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $codeMap = [
                                                    'USA' => 'USA',
                                                    'CA' => 'CA',
                                                    'MX' => 'MX',
                                                ];
                                            @endphp

                                            @foreach ($weeklyReport['summary'] as $region => $data)
                                                @php
                                                    $code = $codeMap[$region] ?? strtoupper($region ?? 'US');
                                                    $flag = $flagMap[$code]['file'] ?? 'us.jpg';
                                                    $hasCampaign = in_array($code, ['USA', 'CA']);
                                                    $currencySymbol = $currencies[$code] ?? '$';
                                                @endphp

                                                {{-- Row 1: Sales --}}
                                                <tr>
                                                    <td class="align-middle" rowspan="2">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <img src="{{ asset('assets/images/flags/' . $flag) }}"
                                                                alt="flag" height="18" class="rounded">
                                                            <span class="fw-semibold">{{ $region ?? '-' }}</span>
                                                        </div>
                                                        <div class="small text-muted mt-1">Ad Spend</div>
                                                    </td>
                                                    @foreach ($weeklyReport['weeks'] as $week)
                                                        @php
                                                            $units = $data[$week]['units'] ?? 0;
                                                            $revenue = $data[$week]['revenue'] ?? 0;
                                                        @endphp
                                                        <td class="align-middle text-center fw-bold">
                                                            {{ $units }}
                                                            @if ($revenue > 0)
                                                                <div class="small text-primary">
                                                                    {{ $currencySymbol }}{{ $revenue }}
                                                                </div>
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                </tr>

                                                {{-- Row 2: SP + SB + ACOS + TACoS --}}
                                                <tr>
                                                    @foreach ($weeklyReport['weeks'] as $week)
                                                        @php
                                                            $sp = $weeklyReport['sp'][$code][$week] ?? ['spTotal' => 0];
                                                            $sb = $weeklyReport['sb'][$code][$week] ?? ['sbTotal' => 0];
                                                            $metrics =
                                                                $weeklyReport['campaignMetrics'][$code][$week] ?? [];

                                                            // Cumulative ad spend
                                                            $totalAdSpend =
                                                                ($sp['spTotal'] ?? 0) + ($sb['sbTotal'] ?? 0);
                                                        @endphp
                                                        <td class="text-center small"
                                                            style="min-width: 100px; white-space: nowrap;">
                                                            <div class="fw-bold {{ $totalAdSpend > 0 ? 'text-success' : '' }}">
                                                                {{ $currencySymbol }}{{ number_format($totalAdSpend, 2) }}
                                                            </div>
                                                            ACOS:
                                                            @if (!empty($metrics['total_acos']) && $metrics['total_acos'] != 0)
                                                                {{ number_format($metrics['total_acos'], 2) }}%
                                                            @else
                                                                -
                                                            @endif
                                                            <div>
                                                                TACoS:
                                                                @if (!empty($metrics['total_tacos']) && $metrics['total_tacos'] != 0)
                                                                    {{ number_format($metrics['total_tacos'], 2) }}%
                                                                @else
                                                                    -
                                                                @endif
                                                            </div>
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>

                                    </table>
                                </div>
                            @else
                                <div class="alert alert-warning mt-3 mb-0">
                                    ⚠️ Unable to load weekly sales data.
                                </div>
                            @endif

                        </div>
                    </div>
                @endcan

                @can('selling.forecast-by-month')
                    <div class="card border-2">
                        <div class="card-body">
                            <div class="h4 card-title">Product Forecast by Month</div>
                            <div class="card-title-desc card-subtitle">
                                Forecasted units from {{ $forecastRangeLabel }}.
                            </div>

                            @if (!empty($forecastRows) && $forecastRows->isNotEmpty())
                                <div class="table-responsive mt-3">
                                    <table class="table table-bordered mb-0 text-center align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th rowspan="4">Region</th>
                                                {{-- <th colspan="2">Next 6 Months</th> --}}
                                                {{-- <th colspan="2">Following 6 Months</th> --}}
                                            </tr>
                                            <tr>
                                                <th>Month</th>
                                                <th>Units</th>
                                                <th>Month</th>
                                                <th>Units</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach ($forecastRows as $index => $row)
                                                <tr>
                                                    @if ($index === 0)
                                                        <td rowspan="{{ $forecastRows->count() }}">
                                                            <div
                                                                class="d-flex align-items-center justify-content-center gap-2">
                                                                <img src="{{ asset('assets/images/flags/us.jpg') }}"
                                                                    height="16">
                                                                <span>USA</span>
                                                            </div>
                                                        </td>
                                                    @endif

                                                    <td>{{ $row['left']['month'] ?? '-' }}</td>
                                                    <td class="fw-bold">
                                                        {{ $row['left']['units'] !== null ? number_format($row['left']['units']) : '-' }}
                                                    </td>

                                                    <td>{{ $row['right']['month'] ?? '-' }}</td>
                                                    <td class="fw-bold">
                                                        {{ $row['right']['units'] !== null ? number_format($row['right']['units']) : '-' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-danger fw-bold mt-3 mb-0">
                                    No forecast data available.
                                </p>
                            @endif
                        </div>
                    </div>
                @endcan

                @can('selling.stock-info')
                    <div class="card border-2">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Stock Information</h4>

                            <div class="table-responsive">
                                <table class="table table-bordered text-center align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th colspan="1">AFN</th>
                                            <th colspan="3">FBA</th>
                                            <th colspan="2">Inbound</th>
                                            <th colspan="3">W/H</th>
                                        </tr>
                                        <tr>
                                            <th>Qty Available</th>
                                            <th>Market</th>
                                            <th>Total Stock</th>
                                            <th>Reserve Stock</th>
                                            <th>Qty Shipped</th>
                                            <th>Qty Received</th>
                                            <th>Shipout Qty</th>
                                            <th>Tactical Qty</th>
                                            <th>AWD Available</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>{{ $stockSummary['afn_quantity'] ?? 0 }}</td>
                                            <td>{{ isset($stockSummary['countries']) ? implode(', ', $stockSummary['countries']) : '-' }}
                                            </td>

                                            <td>{{ $stockSummary['fba_total_stock'] ?? 0 }}</td>
                                            <td>{{ $stockSummary['fba_reserved_stock'] ?? 0 }}</td>
                                            <td>{{ $stockSummary['total_qty_shipped'] ?? 0 }}</td>
                                            <td>{{ $stockSummary['total_qty_received'] ?? 0 }}</td>
                                            <td>{{ $stockSummary['wh_available'] ?? 0 }}</td>
                                            <td>{{ $stockSummary['tactical_wh_available'] ?? 0 }}</td>
                                            <td>{{ $stockSummary['afd_wh_available'] ?? 0 }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endcan
                @can('selling.campaign-info')
                    <livewire:selling.asin-campaigns-tabs :asin="$asin" />
                @endcan
            </div>
        </div>
    </div>
@endsection
