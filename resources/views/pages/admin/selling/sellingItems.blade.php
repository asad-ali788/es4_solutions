@extends('layouts.app')
@section('content')
    @php
        $statusLabels = [
            1 => ['label' => 'Not Started', 'class' => 'bg-danger'],
            2 => ['label' => 'In Progress', 'class' => 'bg-warning'],
            3 => ['label' => 'Completed', 'class' => 'bg-success'],
        ];

        $status = $listing->progress_status;
        $label = $statusLabels[$status]['label'] ?? 'N/A';
        $badgeClass = $statusLabels[$status]['class'] ?? 'bg-secondary';
        $flagMap = config('flagmap');

        $currentCountryCode = strtoupper($listing->country ?? 'US');
        $currentFlag = $flagMap[$currentCountryCode]['file'] ?? 'us.jpg';
        $currentCountryName = $flagMap[$currentCountryCode]['name'] ?? 'United States';

        $dayNames = $dailyReport['dayNames'] ?? [];
        $days = $dailyReport['days'] ?? [];

    @endphp


    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <h4 class="mb-0">Selling Dashboard - {{ $sku ?? 'N/A' }}</h4>
                </div>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            @if ($from === 'sellingItems')
                                <a href="{{ route('admin.selling.index') }}">
                                    <i class="bx bx-left-arrow-alt me-1"></i> Back to Selling Items
                                </a>
                            @else
                                <a href="{{ route('admin.products.index') }}">
                                    <i class="bx bx-left-arrow-alt me-1"></i> Back to Products
                                </a>
                            @endif
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>


    <input type="hidden" name="id" value="{{ old('id', $sourcing->uuid ?? '') }}">
    <div class="row">
        <div class="col-lg-6">
            <div class="left-panel">
                <div class="card border-2 border-info">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Product Image & Details</h4>
                        <div class="row align-items-start">
                            <!-- Image on the left -->
                            <div class="col-md-6 text-center">
                                @php
                                    $imagePath = asset('assets/images/broken_image.png'); // default broken image

                                    if (
                                        !empty($listing->additionalDetail) &&
                                        !empty($listing->additionalDetail->image1)
                                    ) {
                                        $image1 = $listing->additionalDetail->image1;
                                        if (str_starts_with($image1, 'http')) {
                                            $imagePath = $image1;
                                        } else {
                                            $imagePath = asset('storage/' . $image1);
                                        }
                                    }
                                @endphp

                                <img class="rounded" alt="Product Image" width="220" src="{{ $imagePath }}"
                                    onerror="this.onerror=null; this.src='{{ asset('assets/images/broken_image.png') }}';"
                                    data-holder-rendered="true">

                            </div>
                            <!-- Inputs on the right -->
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="product_status" class="form-label fw-bold">Product Status</label><br>
                                    <span class="badge {{ $badgeClass }}">
                                        {{ old('product_status', $label) }}
                                    </span>
                                </div>

                                <div class="form-group mb-3">
                                    <div class="text-muted fw-bold">Landed Cost</div>
                                    <div class="form-control-plaintext text-dark fs-5">
                                        {{ number_format($landedCost, 2) }}
                                    </div>
                                </div>
                                @can('selling.discontinue')
                                    @if ($listing->disc_status == 1)
                                        <span class="badge bg-danger">
                                            Discontinued in ({{ $listing->country ?? 'USA' }})
                                        </span>
                                    @else
                                        <button type="button" class="btn btn-light" data-bs-toggle="modal"
                                            data-bs-target="#discontinueModal">
                                            Discontinue - {{ $listing->country ?? 'USA' }}
                                        </button>
                                    @endif
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-2 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4 class="card-title mb-0">Amazon URL</h4>

                            @if (isset($countries) && $countries->count() > 1)
                                <div class="dropdown">
                                    <button type="button" class="btn header-item waves-effect d-flex align-items-center"
                                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <img id="header-lang-img" src="{{ asset('assets/images/flags/' . $currentFlag) }}"
                                            alt="Selected Country" height="16" class="me-1">
                                        <span class="d-none d-sm-inline">{{ $currentCountryName }}</span>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        @foreach ($countries as $country)
                                            @php
                                                $code = strtoupper($country->country ?? 'US');
                                                $flag = $flagMap[$code]['file'] ?? 'us.jpg';
                                                $name = $flagMap[$code]['name'] ?? $code;
                                                $route = route('admin.selling.createSelling', $country->uuid);
                                            @endphp
                                            <a href="{{ $route }}" class="dropdown-item notify-item language">
                                                <img src="{{ asset('assets/images/flags/' . $flag) }}"
                                                    alt="{{ $name }}" class="me-1" height="12">
                                                <span class="align-middle">{{ $name }}</span>
                                                @if ($listing->uuid === $country->uuid)
                                                    <i class="mdi mdi-check text-success ms-2"></i>
                                                @endif
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @elseif (isset($countries) && $countries->count() === 1)
                                @php
                                    $country = $countries->first();
                                    $code = strtoupper($country->country ?? 'US');
                                    $flag = $flagMap[$code]['file'] ?? 'us.jpg';
                                    $name = $flagMap[$code]['name'] ?? $code;
                                @endphp
                                <div class="d-flex align-items-center">
                                    <img src="{{ asset('assets/images/flags/' . $flag) }}" alt="{{ $name }}"
                                        height="16" class="me-1">
                                    <span class="d-none d-sm-inline">{{ $name }}</span>
                                </div>
                            @endif
                        </div>


                        <div class="row">

                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label for="amz_price" class="form-label mb-2">Selling Price</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="amz_price" name="amz_price"
                                            value="{{ old('amz_price', $amazonSoldPrice[0]->listing_price ?? ($listing->pricing?->base_price ?? '')) }}">
                                        @if (!empty($sourcing->amazon_url))
                                            <a href="{{ old('amazon_url', $sourcing->amazon_url) }}" target="_blank"
                                                class="btn btn-outline-primary ms-2" title="Open in new tab">
                                                <i class="bx bx-link-external"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label for="profit" class="form-label mb-2">Profit</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="profit" name="profit"
                                            value="{{ old('profit', $profit ?? '') }}" readonly>
                                        @if (!empty($sourcing->amazon_url))
                                            <a href="{{ old('amazon_url', $sourcing->amazon_url) }}" target="_blank"
                                                class="btn btn-outline-primary ms-2" title="Open in new tab">
                                                <i class="bx bx-link-external"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @can('selling.set-price')
                                <div class="col-md-12">
                                    <div class="form-group mb-3">
                                        <label for="new_price" class="form-label mb-2">Set Amazon Price</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="new_price"
                                                placeholder="Enter Amazon Price" maxlength="4"
                                                oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1').replace(/^(\d+)(\.\d{0,2}).*$/, '$1$2')">
                                            <div class="ms-2">
                                                <button type="button" id="openSetPriceModal" class="btn btn-success"
                                                    disabled>
                                                    Set Price
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endcan
                        </div>
                    </div>
                </div>


                @can('selling.stock-info')
                    <div class="card border-2 border-warning">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Stock Information</h4>

                            <div class="table-responsive">
                                <table class="table table-bordered text-center align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th colspan="1">AFN</th>
                                            <th colspan="3">FBA</th>
                                            <th colspan="3">Inbound</th>
                                            <th colspan="3">W/H</th>
                                        </tr>
                                        <tr>
                                            <th>Quantity Available</th>
                                            <th>Market</th>
                                            <th>Reserve Stock</th>
                                            <th>Total Stock</th>
                                            <th>Status</th>
                                            <th>Qty Shipped</th>
                                            <th>Qty Received</th>
                                            <th>Shipout Qty</th>
                                            <th>Tactical Qty</th>
                                            <th>AWD Available</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $afnCount = $afnInventoryData->count();
                                            $fbaCount = $fbaInventoryData->count();
                                            $inboundCount = $inboundDetailsData->count();
                                            $whCount = $whInventoryData->count();
                                            $maxRows = max($afnCount, $fbaCount, $inboundCount, $whCount);
                                        @endphp

                                        @for ($i = 0; $i < $maxRows; $i++)
                                            <tr>
                                                @php
                                                    $afn = $afnInventoryData[$i] ?? null;
                                                    $fba = $fbaInventoryData[$i] ?? null;
                                                    $inbound = $inboundDetailsData->get($i);
                                                    $wh = $whInventoryData[$i] ?? null;
                                                @endphp

                                                <td>{{ $afn?->quantity_available ?? '-' }}</td>
                                                <td>{{ $fba?->country ?? '-' }}</td>
                                                <td>{{ $fba?->reserve_stock ?? '-' }}</td>
                                                <td>{{ $fba?->totalstock ?? '-' }}</td>
                                                <td>{{ $inbound?->shipment?->ship_status ?? 'N/A' }}</td>
                                                <td>{{ $inbound?->qty_ship ?? 'N/A' }}</td>
                                                <td>{{ $inbound?->qty_received ?? 'N/A' }}</td>
                                                <td>{{ $wh?->available_quantity ?? '-' }}</td>

                                                {{-- Show totals only on first row --}}
                                                <td>{{ $i === 0 ? $tacticalWhAvailable : '-' }}</td>
                                                <td>{{ $i === 0 ? $afdWhAvailable : '-' }}</td>
                                            </tr>
                                        @endfor
                                    </tbody>
                                </table>

                            </div>

                            <div class="mt-3 d-flex flex-wrap gap-3" id="afn">
                                @if ($afnInventoryData->total() > 0)
                                    <div>{!! $afnInventoryData->withQueryString()->links('pagination::bootstrap-5') !!}</div>
                                @endif

                                @if ($fbaInventoryData->total() > 0)
                                    <div>{!! $fbaInventoryData->withQueryString()->links('pagination::bootstrap-5') !!}</div>
                                @endif

                                @if ($inboundDetailsData->total() > 0)
                                    <div>{!! $inboundDetailsData->withQueryString()->links('pagination::bootstrap-5') !!}</div>
                                @endif
                                @if ($whInventoryData->total() > 0)
                                    <div>{!! $whInventoryData->withQueryString()->links('pagination::bootstrap-5') !!}</div>
                                @endif

                            </div>
                        </div>
                    </div>
                @endcan
                @can('selling.product-ranking')
                    <livewire:selling.sku-ranking-table :product_id="$productId" />
                @endcan
                @can('selling.product-price-range')
                    <livewire:selling.sku-price-range-table :product_id="$productId" />
                @endcan
                @can('selling.product-log')
                    <livewire:selling.sku-product-logs-table :listing="$listing" />
                @endcan
            </div>
        </div>
        <div class="col-lg-6">
            <div class="right-panel">
                @can('selling.forecast-by-month')
                    <div class="card border-2 border-warning">
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
                                                <th>Fc</th>
                                                <th>Month</th>
                                                <th>Fc</th>
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

                @can('selling.daily-sales')
                    <div class="card border-2 border-warning">
                        <div class="card-body">
                            <div class="h4 card-title">Current Week Daily Sales by Market</div>
                            <div class="card-title-desc card-subtitle">
                                Sales by region, displayed day by day for the last 7 days.
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
                                                @endphp

                                                {{-- Row 1: Sales --}}
                                                <tr>
                                                    <td class="align-middle" rowspan="2">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <img src="{{ asset('assets/images/flags/' . $flag) }}"
                                                                alt="flag" height="18" class="rounded">
                                                            <span class="fw-semibold">{{ $region ?? '-' }}</span>
                                                        </div>
                                                        <div class="small text-muted mt-1">Ad Spend</div> {{-- label under region --}}
                                                    </td>
                                                    @foreach ($dailyReport['days'] as $day)
                                                        @php
                                                            $sales = $data[$day] ?? 0;
                                                        @endphp
                                                        <td class="align-middle text-center fw-bold">{{ $sales }}</td>
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
                                                            $metrics =
                                                                $campaignReport['campaignMetrics'][$code][$day] ?? [];

                                                            // Calculate cumulative ad spend
                                                            $totalAdSpend =
                                                                ($sp['spTotal'] ?? 0) + ($sb['sbTotal'] ?? 0);
                                                        @endphp
                                                        <td class="text-center small"
                                                            style="min-width: 140px; white-space: nowrap;">
                                                            <div
                                                                class="fw-bold {{ $totalAdSpend > 0 ? 'text-success' : '' }}">
                                                                ${{ number_format($totalAdSpend, 2) }}
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
                @can('selling.weekly-sales')
                    <div class="card border-2 border-warning">
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
                                                @endphp

                                                {{-- Row 1: Sales --}}
                                                <tr>
                                                    <td class="align-middle" rowspan="2">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <img src="{{ asset('assets/images/flags/' . $flag) }}"
                                                                alt="flag" height="18" class="rounded">
                                                            <span class="fw-semibold">{{ $region ?? '-' }}</span>
                                                        </div>
                                                        <div class="small text-muted mt-1">Ad Spend</div> {{-- Label below region --}}
                                                    </td>
                                                    @foreach ($weeklyReport['weeks'] as $week)
                                                        @php
                                                            $summaryValue = $data[$week] ?? 0;
                                                        @endphp
                                                        <td class="align-middle text-center fw-bold">{{ $summaryValue }}</td>
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
                                                            style="min-width: 140px; white-space: nowrap;">
                                                            <div
                                                                class="fw-bold {{ $totalAdSpend > 0 ? 'text-success' : '' }}">
                                                                ${{ number_format($totalAdSpend, 2) }}
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
                @can('selling.advertising-cost')
                    <div class="card border-2 border-dark">
                        <div class="card-body">
                            <div class="h4 card-title">Advertising Cost</div>
                            <div class="card-title-desc card-subtitle">
                                Combined Sponsored Product & Brand data for the past 7 days.
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
                                                $flag = $flagMap[$region]['file'] ?? 'us.jpg';
                                            @endphp

                                            {{-- Row 1: SP --}}
                                            <tr>
                                                <td rowspan="2">
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
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endcan

                <livewire:selling.product-notes :uuid="$listing->uuid" />

            </div>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="discontinueModal" tabindex="-1" aria-labelledby="discontinueModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.selling.discontinueProduct', $listing->uuid) }}" method="POST"
                    id="discontinueForm">
                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title" id="discontinueModalLabel">Discontinue Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Hidden input (example: product ID) -->
                        <input type="hidden" name="country" value="{{ $rankingData[0]['country'] ?? '' }}">

                        <input type="hidden" name="from" value="{{ request('from') }}">
                        <!-- Textarea for reason -->
                        <div class="mb-3">
                            <label for="reason_of_dis" class="form-label">Reason for Discontinuation</label>
                            <textarea name="reason_of_dis" id="reason_of_dis" class="form-control" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-light"
                            onclick="return confirm('Are you sure you want to discontinue this product?');">Confirm
                            Discontinue
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="setPriceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.selling.setAmazonPrice') }}">
                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title">Confirm New Amazon Price</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">

                        <!-- Hidden fields -->
                        <input type="hidden" name="old_price"
                            value="{{ $amazonSoldPrice->first()?->regular_price ?? ($listing->pricing?->base_price ?? '') }}">

                        <input type="hidden" name="from" value="{{ request('from') }}">
                        <input type="hidden" name="base_price" value="{{ $listing->pricing?->base_price ?? '' }}">

                        <input type="hidden" name="sku" value="{{ $listing->product?->sku ?? '' }}">

                        <input type="hidden" name="asin" value="{{ $listing->product?->asin ?? '' }}">

                        <input type="hidden" name="country" value="{{ $listing->country ?? '' }}">

                        <input type="hidden" name="uuid" value="{{ $listing->uuid ?? '' }}">

                        <input type="hidden" name="product_id" value="{{ $listing->products_id ?? '' }}">

                        <input type="hidden" name="new_price" id="modal_new_price">

                        <!-- Reason Dropdown -->
                        <div class="mb-3">
                            <label for="price_update_reason_id" class="form-label">Reason <span
                                    class="fw-bold text-danger">*</span></label>
                            <select class="form-select" name="price_update_reason_id" id="price_update_reason_id"
                                required>
                                <option value="" selected disabled>-- Select Reason --</option>
                                @foreach ($reasons as $reason)
                                    <option value="{{ $reason->id }}">
                                        {{ $reason->reason_detail }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="reference" class="form-label">Reference (Optional)</label>
                            <input type="text" name="reference" class="form-control">
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Confirm & Submit</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            let debounceTimer;

            $('#amz_price').on('input', function() {
                clearTimeout(debounceTimer);
                const sellingPrice = $(this).val();
                const uuid = "{{ $listing->uuid }}";

                debounceTimer = setTimeout(function() {
                    $.ajax({
                        url: "{{ route('admin.selling.updateProfit') }}",
                        type: "POST",
                        data: {
                            _token: '{{ csrf_token() }}',
                            selling_price: sellingPrice,
                            uuid: uuid
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#profit').val(response.profit);
                            } else {
                                $('#profit').text('N/A');
                            }
                        },
                        error: function() {
                            $('#profit').text('Error');
                        }
                    });
                }, 500);
            });

            // Handle 'input' event for the #new_price input field
            $('#new_price').on('input', function() {
                let val = $(this).val().trim();
                if (val !== "") {
                    $('#openSetPriceModal').prop('disabled', false);
                } else {
                    $('#openSetPriceModal').prop('disabled', true);
                }
            });

            // Handle modal trigger and fill modal fields
            $('#openSetPriceModal').on('click', function() {
                let newPrice = $('#new_price').val().trim();
                $('#modal_new_price').val(newPrice);

                $('#setPriceModal').modal('show');
            });
        });
    </script>
@endpush
