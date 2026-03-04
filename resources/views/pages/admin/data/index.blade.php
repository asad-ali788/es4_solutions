@extends('layouts.app')

@section('content')
    {{-- Local styles just for this page --}}
    <style>
        .download-circle {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            /* default: no background */
            transition: background 0.2s ease;
        }

        .download-circle:hover {
            background: var(--bs-success-bg-subtle, #eaf7ea);
            /* background only on hover */
        }

        .download-circle i {
            font-size: 22px;
        }

        .download-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            color: inherit;
            text-decoration: none;
        }

        .download-link.disabled {
            pointer-events: none;
            opacity: 0.6;
        }

        .download-icon,
        .download-spinner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    </style>

    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Data Reports & Exchange</h4>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- ===================== TOP SECTION: DATA DOWNLOADS ===================== --}}
        <div class="col-12">
            <div class="row g-3">
                {{-- Downloads Data - US --}}
                @can('data.download.us-master')
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="card-title mb-3">Downloads Data - US</div>
                                <div class="table-responsive">
                                    <table class="table table-nowrap align-middle table-hover mb-0">
                                        <tbody>
                                            @php
                                                $usDownloads = [
                                                    [
                                                        'title' => 'Master Data File US',
                                                        'route' => 'masterDataDownload',
                                                        'desc' => 'Excel format (.xlsx)',
                                                        'confirm' => 'Master Data File US',
                                                    ],
                                                    [
                                                        'title' => 'Product Library Images',
                                                        'route' => 'libraryImagesDownload',
                                                        'desc' => 'Excel format (.xlsx)',
                                                        'confirm' => 'Product Library Images',
                                                    ],
                                                    [
                                                        'title' => "All SKU's Carton Size",
                                                        'route' => 'cartonSizeDownload',
                                                        'desc' => 'Excel format (.xlsx)',
                                                        'confirm' => "All SKU's Carton Size",
                                                    ],
                                                    [
                                                        'title' => 'Item Prices Only',
                                                        'route' => 'itemPriceDownload',
                                                        'desc' => 'Excel format (.xlsx)',
                                                        'confirm' => 'Item Prices Only',
                                                    ],
                                                    [
                                                        'title' => 'Stock Run Down Report',
                                                        'route' => 'StockRunDownReport',
                                                        'desc' => 'Excel format (.xlsx)',
                                                        'confirm' => 'Stock Run Down Report',
                                                    ],
                                                ];
                                            @endphp
                                            @foreach ($usDownloads as $item)
                                                <tr>
                                                    {{-- Left icon --}}
                                                    <td style="width:45px;">
                                                        <div class="avatar-sm">
                                                            <span
                                                                class="avatar-title rounded-circle glance glance-success font-size-24">
                                                                <i class="bx bxs-file-doc"></i>
                                                            </span>
                                                        </div>
                                                    </td>

                                                    {{-- Title / desc --}}
                                                    <td>
                                                        <h5 class="font-size-14 mb-1">{{ $item['title'] }}</h5>
                                                        <small>{{ $item['desc'] }}</small>
                                                    </td>

                                                    {{-- Download circle --}}
                                                    <td style="width:60px;">
                                                        @if ($item['route'])
                                                            <div class="download-circle">
                                                                <a href="{{ route('admin.data.' . $item['route']) }}"
                                                                    class="download-link"
                                                                    data-confirm="Are you sure you want to download {{ $item['confirm'] }}?">
                                                                    <span class="download-icon">
                                                                        <i class="bx bx-cloud-download"></i>
                                                                    </span>
                                                                    <span class="download-spinner d-none">
                                                                        <i class="bx bx-loader-alt bx-spin"></i>
                                                                    </span>
                                                                </a>
                                                            </div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endcan

                {{-- Ads Campaign Performance --}}
                @can('data.download.ads-campaign-performance')
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="card-title mb-3">
                                    Download Ads Campaign Performance overview <strong>(US)</strong>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-nowrap align-middle table-hover mb-0">
                                        <tbody>
                                            @php
                                                $downloads = [
                                                    [
                                                        'label' => 'Last 7 days',
                                                        'route' => 'adsCampaignLast7Days',
                                                        'confirm' => 'the SP Ads Performance report for last 7 days',
                                                    ],
                                                    [
                                                        'label' => 'Last 4 Weeks',
                                                        'route' => 'adsCampaignLast4Weeks',
                                                        'confirm' => 'the SP Ads Performance report for last 4 weeks',
                                                    ],
                                                    [
                                                        'label' => 'Last 3 Months',
                                                        'route' => 'adsCampaignLast3Months',
                                                        'confirm' => 'the SP Ads Performance report for last 3 months',
                                                    ],
                                                ];
                                            @endphp
                                            @foreach ($downloads as $download)
                                                <tr>
                                                    <td style="width:45px;">
                                                        <div class="avatar-sm">
                                                            <span
                                                                class="avatar-title rounded-circle glance glance-success font-size-24">
                                                                <i class="bx bxs-file-doc"></i>
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <h5 class="font-size-14 mb-1">
                                                            Campaign - {{ $download['label'] }}
                                                        </h5>
                                                        <small>Excel format (.xlsx)</small>
                                                    </td>
                                                    <td style="width:60px;">
                                                        <div class="download-circle">
                                                            <a href="{{ route('admin.data.' . $download['route']) }}"
                                                                class="download-link"
                                                                data-confirm="Are you sure you want to download {{ $download['confirm'] }}?">
                                                                <span class="download-icon">
                                                                    <i class="bx bx-cloud-download"></i>
                                                                </span>
                                                                <span class="download-spinner d-none">
                                                                    <i class="bx bx-loader-alt bx-spin"></i>
                                                                </span>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endcan

                {{-- Download Forecast --}}
                @can('data.download.forecast')
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="card-title mb-3">Download Forecast</div>
                                <div class="table-responsive">

                                    <table class="table table-nowrap align-middle table-hover mb-0">
                                        <tbody>
                                            @php
                                                $forecastDownloads = [
                                                    [
                                                        'label' => 'Forecast Finalised',
                                                        'route' => 'orderForecastFinaliseExport',
                                                        'confirm' => 'forecast finalised export',
                                                    ],
                                                    [
                                                        'label' => 'ASIN Performance Report',
                                                        'route' => 'AsinPerformanceReportExport',
                                                        'confirm' => 'Asin Performance Report Export',
                                                    ],
                                                ];
                                            @endphp
                                            @foreach ($forecastDownloads as $download)
                                                <tr>
                                                    <td style="width:45px;">
                                                        <div class="avatar-sm">
                                                            <span
                                                                class="avatar-title rounded-circle glance glance-success font-size-24">
                                                                <i class="bx bxs-file-doc"></i>
                                                            </span>
                                                        </div>
                                                    </td>

                                                    <td>
                                                        <h5 class="font-size-14 mb-1">{{ $download['label'] }}</h5>
                                                        <small>Excel format (.xlsx)</small>
                                                    </td>

                                                    <td style="width:60px;">
                                                        <div class="download-circle">
                                                            <a href="{{ route('admin.data.' . $download['route']) }}"
                                                                class="download-link"
                                                                data-confirm="Are you sure you want to download the {{ $download['confirm'] }}?">
                                                                <span class="download-icon">
                                                                    <i class="bx bx-cloud-download"></i>
                                                                </span>
                                                                <span class="download-spinner d-none">
                                                                    <i class="bx bx-loader-alt bx-spin"></i>
                                                                </span>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endcan
            </div>
        </div>

        {{-- ===================== MIDDLE SECTION: PERFORMANCE / WAREHOUSE / SALES ===================== --}}
        <div class="col-12">
            <div class="row g-3">
                {{-- Download Performance Report --}}
                @can('data.download.performance-report')
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="card-title mb-3">Download Performance Report</div>
                                <div class="table-responsive">
                                    <table class="table table-nowrap align-middle table-hover mb-0">
                                        <tbody>
                                            <tr>
                                                <td style="width:45px;">
                                                    <div class="avatar-sm">
                                                        <span
                                                            class="avatar-title rounded-circle glance glance-success font-size-24">
                                                            <i class="bx bxs-file-doc"></i>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <h5 class="font-size-14 mb-1">Performance Keyword</h5>
                                                    <p class="font-size-10 mb-1">Last 7 Days from Today</p>
                                                    <small>Excel format (.xlsx)</small>
                                                </td>
                                                <td style="width:60px;">
                                                    <div class="download-circle">
                                                        <a href="{{ route('admin.data.adsKeywordPerfomanceDownload7days') }}"
                                                            class="download-link"
                                                            data-confirm="Are you sure you want to download the Ads Keyword Performance report for the last 7 days?">
                                                            <span class="download-icon">
                                                                <i class="bx bx-cloud-download"></i>
                                                            </span>
                                                            <span class="download-spinner d-none">
                                                                <i class="bx bx-loader-alt bx-spin"></i>
                                                            </span>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width:45px;">
                                                    <div class="avatar-sm">
                                                        <span
                                                            class="avatar-title rounded-circle glance glance-success font-size-24">
                                                            <i class="bx bxs-file-doc"></i>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <h5 class="font-size-14 mb-1">Performance Campaigns</h5>
                                                    <p class="font-size-10 mb-1">Last 7 Days from Today</p>
                                                    <small>Excel format (.xlsx)</small>
                                                </td>
                                                <td style="width:60px;">
                                                    <div class="download-circle">
                                                        <a href="{{ route('admin.data.adsPerformanceByProductCampaignDownload') }}"
                                                            class="download-link"
                                                            data-confirm="Are you sure you want to download the Ads Performance Campaign report for the last 7 days?">
                                                            <span class="download-icon">
                                                                <i class="bx bx-cloud-download"></i>
                                                            </span>
                                                            <span class="download-spinner d-none">
                                                                <i class="bx bx-loader-alt bx-spin"></i>
                                                            </span>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width:45px;">
                                                    <div class="avatar-sm">
                                                        <span
                                                            class="avatar-title rounded-circle glance glance-success font-size-24">
                                                            <i class="bx bxs-file-doc"></i>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <h5 class="font-size-14 mb-1">ASIN level - Last 30 Days</h5>
                                                    <small>Excel format (.xlsx)</small>
                                                </td>
                                                <td style="width:60px;">
                                                    <div class="download-circle">
                                                        <a href="{{ route('admin.data.adsPerformanceByAsinDownload') }}"
                                                            class="download-link"
                                                            data-confirm="Are you sure you want to download the Ads Performance report by ASIN?">
                                                            <span class="download-icon">
                                                                <i class="bx bx-cloud-download"></i>
                                                            </span>
                                                            <span class="download-spinner d-none">
                                                                <i class="bx bx-loader-alt bx-spin"></i>
                                                            </span>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endcan

                {{-- Warehouse Export --}}
                @can('data.download.warehouse-report')
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="card-title mb-3">Warehouse Export</div>
                                <div class="table-responsive">
                                    <table class="table table-nowrap align-middle table-hover mb-0">
                                        <tbody>
                                            <tr>
                                                <td style="width:45px;">
                                                    <div class="avatar-sm">
                                                        <span
                                                            class="avatar-title rounded-circle glance glance-success font-size-24">
                                                            <i class="bx bxs-file-doc"></i>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <h5 class="font-size-14 mb-1">All Warehouses</h5>
                                                    <small>Excel format (.xlsx)</small>
                                                </td>
                                                <td style="width:60px;">
                                                    <div class="download-circle">
                                                        <a href="{{ route('admin.warehouse.exportExcel') }}"
                                                            class="download-link"
                                                            data-confirm="Are you sure you want to download All Warehouse Export?">
                                                            <span class="download-icon">
                                                                <i class="bx bx-cloud-download"></i>
                                                            </span>
                                                            <span class="download-spinner d-none">
                                                                <i class="bx bx-loader-alt bx-spin"></i>
                                                            </span>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width:45px;">
                                                    <div class="avatar-sm">
                                                        <span
                                                            class="avatar-title rounded-circle glance glance-success font-size-24">
                                                            <i class="bx bxs-file-doc"></i>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <h5 class="font-size-14 mb-1">Warehouses by ASIN</h5>
                                                    <small>Excel format (.xlsx)</small>
                                                </td>
                                                <td style="width:60px;">
                                                    <div class="download-circle">
                                                        <a href="{{ route('admin.data.asinExportExcel') }}"
                                                            class="download-link"
                                                            data-confirm="Are you sure you want to download ASIN Warehouse Export?">
                                                            <span class="download-icon">
                                                                <i class="bx bx-cloud-download"></i>
                                                            </span>
                                                            <span class="download-spinner d-none">
                                                                <i class="bx bx-loader-alt bx-spin"></i>
                                                            </span>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endcan

                {{-- Sales Data --}}
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="card-title mb-3">Sales Data</div>
                            <div class="table-responsive">
                                <table class="table table-nowrap align-middle table-hover mb-0 table-hover">
                                    <tbody>
                                        @php
                                            $salesDownloads = [
                                                [
                                                    'label' => 'Daily Sales Report',
                                                    'route' => 'salesDailyReport',
                                                    'confirm' => 'daily sales report',
                                                ],
                                                [
                                                    'label' => 'Monthly Sales Report',
                                                    'route' => 'salesMonthlyReport',
                                                    'confirm' => 'Monthly sales report',
                                                ],
                                                [
                                                    'label' => 'Ranking Report',
                                                    'route' => 'rankingReport',
                                                    'confirm' => 'Ranking report',
                                                ],
                                                [
                                                    'label' => 'Weekly Sales Report',
                                                    'route' => 'weeklySalesPerformanceReport',
                                                    'confirm' => 'Weekly sales report',
                                                ],
                                            ];
                                        @endphp
                                        @foreach ($salesDownloads as $download)
                                            <tr>
                                                <td style="width:45px;">
                                                    <div class="avatar-sm">
                                                        <span
                                                            class="avatar-title rounded-circle glance glance-success font-size-24">
                                                            <i class="bx bxs-file-doc"></i>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <h5 class="font-size-14 mb-1">{{ $download['label'] }}</h5>
                                                    <small>Excel format (.xlsx)</small>
                                                </td>
                                                <td style="width:60px;">
                                                    <div class="download-circle">
                                                        <a href="{{ route('admin.data.' . $download['route']) }}"
                                                            class="download-link"
                                                            data-confirm="Are you sure you want to download the {{ $download['confirm'] }}?">
                                                            <span class="download-icon">
                                                                <i class="bx bx-cloud-download"></i>
                                                            </span>
                                                            <span class="download-spinner d-none">
                                                                <i class="bx bx-loader-alt bx-spin"></i>
                                                            </span>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===================== BOTTOM SECTION: EXCHANGE RATE TABLE ===================== --}}
        @can('data.exchange-rate')
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title mb-3">Exchange Rate</div>
                        <div class="table-responsive">
                            <table class="table table-nowrap align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Country Code</th>
                                        <th>Currency Code</th>
                                        <th>Currency Name</th>
                                        <th>Symbol</th>
                                        <th>Rate to USD</th>
                                        @can('data.exchange-rate-update')
                                            <th>Action</th>
                                        @endcan
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $flagMap = config('flagmap'); @endphp
                                    @forelse ($currencies as $currency)
                                        @php
                                            $code = strtoupper($currency->country_code ?? 'US');
                                            $flag = $flagMap[$code]['file'] ?? 'us.jpg';
                                            $name = $flagMap[$code]['name'] ?? $code;
                                        @endphp
                                        <tr>
                                            <td>
                                                <img src="{{ asset('assets/images/flags/' . $flag) }}"
                                                    alt="{{ $name }}" title="{{ $name }}"
                                                    style="width:24px;height:24px;border-radius:50%;">
                                            </td>
                                            <td>{{ $currency->country_code }}</td>
                                            <td>{{ $currency->currency_code }}</td>
                                            <td>{{ $currency->currency_name }}</td>
                                            <td>{{ $currency->currency_symbol }}</td>
                                            <td>{{ number_format($currency->conversion_rate_to_usd, 2) }}</td>
                                            @can('data.exchange-rate-update')
                                                <td class="p-0">
                                                    <a href="{{ route('admin.currencies.edit', $currency->id) }}"
                                                        class="d-flex align-items-center justify-content-center w-100 h-100 py-2"
                                                        data-bs-toggle="tooltip" title="Edit">
                                                        <i class="mdi mdi-pencil text-primary fs-5"></i>
                                                    </a>
                                                </td>
                                            @endcan
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">No currencies found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">
                            {{ $currencies->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
        @endcan
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const downloadLinks = document.querySelectorAll('.download-link');

                downloadLinks.forEach(function(link) {
                    link.addEventListener('click', function(e) {
                        const confirmMessage = this.getAttribute('data-confirm');

                        if (confirmMessage && !confirm(confirmMessage)) {
                            e.preventDefault();
                            return;
                        }

                        const icon = this.querySelector('.download-icon');
                        const spinner = this.querySelector('.download-spinner');

                        if (icon && spinner) {
                            icon.classList.add('d-none');
                            spinner.classList.remove('d-none');
                        }

                        this.classList.add('disabled');

                        // Reset back after a few seconds (cannot detect actual download completion)
                        setTimeout(() => {
                            if (icon && spinner) {
                                icon.classList.remove('d-none');
                                spinner.classList.add('d-none');
                            }
                            this.classList.remove('disabled');
                        }, 4000);
                    });
                });
            });
        </script>
    @endpush
@endsection
