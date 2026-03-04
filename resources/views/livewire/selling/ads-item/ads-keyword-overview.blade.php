<div>
    @php
        $sp = $overview['by_type']['SP'] ?? null;
        $sb = $overview['by_type']['SB'] ?? null;

        $counts = $overview['counts'] ?? [];
        $total = $overview['total'] ?? null;

        $formatMoney = function ($val) {
            return $val !== null ? '$' . number_format((float) $val, 2) : 'N/A';
        };

        $formatPercent = function ($val) {
            return $val !== null ? number_format((float) $val, 2) . '%' : 'N/A';
        };

        $formatInt = function ($val) {
            return $val !== null ? number_format((int) $val) : 'N/A';
        };

        // Ensure $selectedDate is always defined from the component
        $selectedDate = $selectedDate ?? (request()->query('date', now(config('timezone.market'))->subDay()->toDateString()));

        $withFilters = function (array $extra = []) use ($asin, $selectedDate) {
            $base = [
                'period' => request('period', '1d'),
                'source' => request('source', 'ads-item'),
                'date'   => $selectedDate,
            ];
            $asin = request()->input('asin', $asin);
            if (!empty($asin)) {
                $base['asins[]'] = $asin;
            }
            return array_merge($base, $extra);
        };
    @endphp

    {{-- TOTAL KEYWORDS (SP + SB) --}}
    <div class="accordion mb-2" id="accordionKeywordMetrics">
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingKeywordTotal">
                <button class="accordion-button collapsed fs-5 fw-bold acc-btn-theme" type="button"
                    data-bs-toggle="collapse" data-bs-target="#collapseKeywordTotal" aria-expanded="false"
                    aria-controls="collapseKeywordTotal">
                    Total Keywords (SP + SB)
                </button>
            </h2>

            <div id="collapseKeywordTotal" class="accordion-collapse collapse" aria-labelledby="headingKeywordTotal"
                data-bs-parent="#accordionKeywordMetrics">
                <div class="accordion-body acc-btn-theme">
                    <div class="row">
                        <div class="col-xl-6 mb-0">
                            <div class="card mb-0">
                                <div class="card-body">
                                    <h4 class="card-title mb-3">Total Keywords</h4>

                                    <div class="row mb-4">
                                        <div class="col-lg-8">
                                            <div class="mt-4">
                                                <p>Total Keywords</p>
                                                <h2 class="text-primary">
                                                    {{ $counts['total'] ?? 'N/A' }}
                                                </h2>

                                                <div class="row">
                                                    <div class="col-6">
                                                        <p class="mb-2">Total Spend</p>
                                                        <h3>
                                                            {{ $total ? $formatMoney($total['spend']) : 'N/A' }}
                                                        </h3>
                                                    </div>
                                                    <div class="col-6">
                                                        <p class="mb-2">Total Sales</p>
                                                        <h3 class="text-success">
                                                            {{ $total ? $formatMoney($total['sales']) : 'N/A' }}
                                                        </h3>
                                                    </div>
                                                </div>

                                                <div class="mt-4">
                                                    <a class="btn btn-light btn-sm"
                                                        href="{{ route('admin.ads.overview.keywordOverview', $withFilters([])) }}">
                                                        View more <i class="mdi mdi-arrow-right ms-1"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="align-self-center col-lg-4 ps-3">
                                            <div class="row d-flex flex-lg-column align-items-lg-center text-lg-center">
                                                <div class="col-6 col-lg-12 mt-4 pt-2">
                                                    <p class="mb-2">
                                                        <i
                                                            class="mdi mdi-circle align-middle font-size-14 me-2 text-primary"></i>
                                                        Total Units
                                                    </p>
                                                    <h3>{{ $total ? $formatInt($total['units']) : 'N/A' }}</h3>
                                                </div>

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

                        {{-- RIGHT SIDE: SP vs SB quick split --}}
                        <div class="col-xl-6">
                            {{-- ACoS < 30% --}}
                            <div class="row">
                                <div class="col-sm-6 pe-1">
                                    <a href="{{ route('admin.ads.overview.keywordOverview', $withFilters(['acos' => '30'])) }}" class="text-decoration-none">
                                        <div class="card hover-card clickable-card mb-2">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="avatar-xs me-3">
                                                        <span class="avatar-title rounded-circle glance glance-success font-size-18">
                                                            <i class="bx bx-trending-up"></i>
                                                        </span>
                                                    </div>
                                                    <h5 class="font-size-14 mb-0 text-dark">&lt; 30% ACoS</h5>
                                                </div>
                                                <div class="row text-center text-sm-start">
                                                    <div class="col-6">
                                                        <span class="text-muted small d-block">Keywords</span>
                                                        <h5 class="mb-0 text-dark">{{ $total ? $formatInt($total['buckets']['lt_30']['count'] ?? null) : 'N/A' }}</h5>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted small d-block">Units</span>
                                                        <h5 class="mb-0 text-dark">{{ $total ? $formatInt($total['buckets']['lt_30']['units'] ?? null) : 'N/A' }}</h5>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted small d-block">Spend</span>
                                                        <h5 class="mb-0 text-dark">{{ $total ? $formatMoney($total['buckets']['lt_30']['spend'] ?? null) : 'N/A' }}</h5>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted small d-block">Sales</span>
                                                        <h5 class="mb-0 text-dark">{{ $total ? $formatMoney($total['buckets']['lt_30']['sales'] ?? null) : 'N/A' }}</h5>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                {{-- ACoS > 30% --}}
                                <div class="col-sm-6 ps-1">
                                    <a href="{{ route('admin.ads.overview.keywordOverview', $withFilters(['acos' => '31'])) }}" class="text-decoration-none">
                                        <div class="card hover-card clickable-card mb-2">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="avatar-xs me-3">
                                                        <span class="avatar-title rounded-circle glance glance-warning font-size-18">
                                                            <i class="bx bx-trending-down"></i>
                                                        </span>
                                                    </div>
                                                    <h5 class="font-size-14 mb-0 text-dark">&gt; 30% ACoS</h5>
                                                </div>
                                                <div class="row text-center text-sm-start">
                                                    <div class="col-6">
                                                        <span class="text-muted small d-block">Keywords</span>
                                                        <h5 class="mb-0 text-dark">{{ $total ? $formatInt($total['buckets']['gte_30']['count'] ?? null) : 'N/A' }}</h5>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted small d-block">Units</span>
                                                        <h5 class="mb-0 text-dark">{{ $total ? $formatInt($total['buckets']['gte_30']['units'] ?? null) : 'N/A' }}</h5>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted small d-block">Spend</span>
                                                        <h5 class="mb-0 text-dark">{{ $total ? $formatMoney($total['buckets']['gte_30']['spend'] ?? null) : 'N/A' }}</h5>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted small d-block">Sales</span>
                                                        <h5 class="mb-0 text-dark">{{ $total ? $formatMoney($total['buckets']['gte_30']['sales'] ?? null) : 'N/A' }}</h5>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            {{-- 0% ACoS (Spend > 0 && Sale = 0) and (Spend = 0 && Sale = 0) --}}
                            <div class="row">
                                <div class="col-sm-6 pe-1">
                                    <a href="{{ route('admin.ads.overview.keywordOverview', $withFilters(['acos' => '0'])) }}" class="text-decoration-none">
                                        <div class="card hover-card clickable-card mb-2">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="avatar-xs me-3">
                                                        <span class="avatar-title rounded-circle glance glance-danger font-size-18">
                                                            <i class="bx bx-error-circle"></i>
                                                        </span>
                                                    </div>
                                                    <h5 class="font-size-14 mb-0 text-dark me-2">0% ACoS</h5>
                                                    <span class="text-muted small d-block">(Spend &gt; 0 &amp;&amp; Sale = 0)</span>
                                                </div>
                                                <div class="row text-center text-sm-start">
                                                    <div class="col-6">
                                                        <span class="text-muted small d-block">Keywords</span>
                                                        <h5 class="mb-0 text-dark">{{ $total ? $formatInt($total['buckets']['spend_gt_zero_sales']['count'] ?? null) : 'N/A' }}</h5>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted small d-block">Units</span>
                                                        <h5 class="mb-0 text-muted">{{ $total ? $formatInt($total['buckets']['spend_gt_zero_sales']['unit'] ?? null) : 'N/A' }}</h5>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted small d-block">Spend</span>
                                                        <h5 class="mb-0 text-dark">{{ $total ? $formatMoney($total['buckets']['spend_gt_zero_sales']['spend'] ?? null) : 'N/A' }}</h5>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted small d-block">Sales</span>
                                                        <h5 class="mb-0 text-muted">{{ $total ? $formatMoney($total['buckets']['spend_gt_zero_sales']['sales'] ?? null) : 'N/A' }}</h5>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-sm-6 ps-1">
                                    <a href="{{ route('admin.ads.overview.keywordOverview', $withFilters(['acos' => 'none', 'spend' => '0'])) }}" class="text-decoration-none">
                                        <div class="card hover-card clickable-card mb-2">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="avatar-xs me-3">
                                                        <span class="avatar-title rounded-circle glance glance-danger font-size-18">
                                                            <i class="bx bx-error"></i>
                                                        </span>
                                                    </div>
                                                    <h5 class="font-size-14 mb-0 text-dark me-2">0% ACoS</h5>
                                                    <span class="text-muted small d-block">(Spend = 0 &amp;&amp; Sale = 0)</span>
                                                </div>
                                                <div class="row text-center text-sm-start">
                                                    <div class="col-6">
                                                        <span class="text-muted small d-block">Keywords</span>
                                                        <h5 class="mb-0 text-dark">{{ $total ? $formatInt($total['buckets']['spend_zero_sales_zero_cnt']['count'] ?? null) : 'N/A' }}</h5>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted small d-block">Units</span>
                                                        <h5 class="mb-0 text-muted">{{ $total ? $formatInt($total['buckets']['spend_zero_sales_zero_cnt']['unit'] ?? null) : 'N/A' }}</h5>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted small d-block">Spend</span>
                                                        <h5 class="mb-0 text-muted">{{ $total ? $formatMoney($total['buckets']['spend_zero_sales_zero_cnt']['spend'] ?? null) : 'N/A' }}</h5>
                                                    </div>
                                                    <div class="col-6">
                                                        <span class="text-muted small d-block">Sales</span>
                                                        <h5 class="mb-0 text-muted">{{ $total ? $formatMoney($total['buckets']['spend_zero_sales_zero_cnt']['sales'] ?? null) : 'N/A' }}</h5>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div> {{-- row --}}
                </div>
            </div>
        </div>
    </div>
</div>
