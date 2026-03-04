<div>
    {{-- Sales Accordions (your existing part stays same) --}}
    @can('selling.ads-item.dashboard.salesdata')
        <div class="accordion mb-2" id="accordionExample">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button collapsed fs-5 fw-bold acc-btn-theme" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true"
                        aria-controls="collapseOne">
                        Sales Data
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne"
                    data-bs-parent="#accordionExample">
                    <div class="accordion-body acc-btn-theme">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="left-panel mb-0">
                                    @can('selling.daily-sales')
                                        <div class="card border-2 mb-0">
                                            <div class="card-body">
                                                <div class="h4 card-title">Current Week Daily Sales & Campaign Report by Market
                                                </div>
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
                                                                        $code =
                                                                            $codeMap[$region] ??
                                                                            strtoupper($region ?? 'US');
                                                                        $flag = $flagMap[$code]['file'] ?? 'us.jpg';
                                                                        $currencySymbol = $currencies[$code] ?? '$';
                                                                    @endphp

                                                                    {{-- Row 1: Sales --}}
                                                                    <tr>
                                                                        <td class="align-middle" rowspan="2">
                                                                            <div class="d-flex align-items-center gap-2">
                                                                                <img src="{{ asset('assets/images/flags/' . $flag) }}"
                                                                                    alt="flag" height="18"
                                                                                    class="rounded">
                                                                                <span
                                                                                    class="fw-semibold">{{ $region ?? '-' }}</span>
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

                                                                    {{-- Row 2: Spend + ACOS + TACoS --}}
                                                                    <tr>
                                                                        @foreach ($dailyReport['days'] as $day)
                                                                            @php
                                                                                $sp = $campaignReport['sp'][$code][
                                                                                    $day
                                                                                ] ?? ['spTotal' => 0];
                                                                                $sb = $campaignReport['sb'][$code][
                                                                                    $day
                                                                                ] ?? ['sbTotal' => 0];
                                                                                $sd = $campaignReport['sd'][$code][
                                                                                    $day
                                                                                ] ?? ['sdTotal' => 0];

                                                                                $metrics =
                                                                                    $campaignReport['campaignMetrics'][
                                                                                        $code
                                                                                    ][$day] ?? [];

                                                                                $totalAdSpend =
                                                                                    ($sp['spTotal'] ?? 0) +
                                                                                    ($sb['sbTotal'] ?? 0) +
                                                                                    ($sd['sdTotal'] ?? 0);
                                                                            @endphp
                                                                            <td class="text-center small"
                                                                                style="min-width: 100px; white-space: nowrap;">
                                                                                <div
                                                                                    class="fw-bold {{ $totalAdSpend > 0 ? 'text-success' : '' }}">
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
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="right-panel mb-0">
                                    @can('selling.weekly-sales')
                                        <div class="card border-2 mb-0">
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
                                                                        $code =
                                                                            $codeMap[$region] ??
                                                                            strtoupper($region ?? 'US');
                                                                        $flag = $flagMap[$code]['file'] ?? 'us.jpg';
                                                                        $currencySymbol = $currencies[$code] ?? '$';
                                                                    @endphp

                                                                    {{-- Row 1: Sales --}}
                                                                    <tr>
                                                                        <td class="align-middle" rowspan="2">
                                                                            <div class="d-flex align-items-center gap-2">
                                                                                <img src="{{ asset('assets/images/flags/' . $flag) }}"
                                                                                    alt="flag" height="18"
                                                                                    class="rounded">
                                                                                <span
                                                                                    class="fw-semibold">{{ $region ?? '-' }}</span>
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

                                                                    {{-- Row 2: Spend + ACOS + TACoS --}}
                                                                    <tr>
                                                                        @foreach ($weeklyReport['weeks'] as $week)
                                                                            @php
                                                                                $sp = $weeklyReport['sp'][$code][
                                                                                    $week
                                                                                ] ?? [
                                                                                    'spTotal' => 0,
                                                                                ];
                                                                                $sb = $weeklyReport['sb'][$code][
                                                                                    $week
                                                                                ] ?? [
                                                                                    'sbTotal' => 0,
                                                                                ];
                                                                                $metrics =
                                                                                    $weeklyReport['campaignMetrics'][
                                                                                        $code
                                                                                    ][$week] ?? [];

                                                                                $totalAdSpend =
                                                                                    ($sp['spTotal'] ?? 0) +
                                                                                    ($sb['sbTotal'] ?? 0);
                                                                            @endphp
                                                                            <td class="text-center small"
                                                                                style="min-width: 100px; white-space: nowrap;">
                                                                                <div
                                                                                    class="fw-bold {{ $totalAdSpend > 0 ? 'text-success' : '' }}">
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan
</div>
