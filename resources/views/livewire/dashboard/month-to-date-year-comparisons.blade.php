<div class="col-12">
    @php
        $mtdStart = data_get($monthToDateSummary, 'summary.mtd_start', 'N/A');

        $totalUnitsTy = data_get($monthToDateSummary, 'summary.total_units_ty', 0);
        $totalRevenueTy = data_get($monthToDateSummary, 'summary.total_revenue_usd_ty', 0);

        $totalUnitsLy = data_get($monthToDateSummary, 'summary.total_units_ly', 0);
        $totalRevenueLy = data_get($monthToDateSummary, 'summary.total_revenue_usd_ly', 0);
        $pct = data_get($monthToDateSummary, 'summary.total_units_percentage', null);

        $lastMonthStart = data_get($monthToDateSummary, 'summary.last_month_start', null);
        $lastMonthUnits = data_get($monthToDateSummary, 'summary.total_units_last_month', null);
        $lastMonthRev = data_get($monthToDateSummary, 'summary.total_revenue_usd_last_month', null);

        $lastMonthLyStart = data_get($monthToDateSummary, 'summary.last_month_ly_start', null);
        $lastMonthLyUnits = data_get($monthToDateSummary, 'summary.total_units_last_month_ly', null);
        $lastMonthLyRev = data_get($monthToDateSummary, 'summary.total_revenue_usd_last_month_ly', null);
    @endphp

    <div class="card w-100 mb-0">
        <div class="card-body">
            <div class="d-flex mb-2 justify-content-between gap-2 flex-wrap">
                <div class="d-flex align-items-center">
                    <div class="avatar-xs me-3">
                        <span
                            class="avatar-title rounded-circle bg-info-subtle text-info glance glance-info font-size-18">
                            <i class="bx bx-pie-chart-alt-2"></i>
                        </span>
                    </div>
                    <h4 class="font-size-14 mb-0">Month-to-Date Sales vs Last Year</h4>
                </div>
            </div>

            <div class="row g-3 gap-3">
                <div class="col-12 col-sm-6 col-lg-4 align-self-center">
                    <p>
                        <span class="pulse-dot pulse-success me-1"
                            style="width: 8px; height: 8px; display: inline-block;"></span>
                        Total Units -
                        {{ $mtdStart !== 'N/A' ? \Carbon\Carbon::parse($mtdStart)->format('F Y') : 'N/A' }}:
                    </p>

                    <h4 class="text-muted mt-0">
                        {{ number_format((int) $totalUnitsTy) }}
                    </h4>

                    <p class="text-muted mb-2">
                        Total Revenue:
                        ${{ number_format((float) $totalRevenueTy, 2) }} USD
                    </p>

                    <x-elements.progress-bar :value="$pct" />
                </div>

                <div class="col-12 col-sm-6 col-lg-4 align-self-center">
                    <p>
                        <span class="pulse-dot pulse-warning me-1"
                            style="width: 8px; height: 8px; display: inline-block;"></span>
                        Total Units -
                        {{ $mtdStart !== 'N/A' ? \Carbon\Carbon::parse($mtdStart)->subYear()->format('F Y') : 'N/A' }}:
                    </p>

                    <h4 class="text-muted mt-0">
                        {{ number_format((int) $totalUnitsLy) }}
                    </h4>

                    <p class="text-muted mb-4">
                        Total Revenue:
                        ${{ number_format((float) $totalRevenueLy, 2) }} USD
                    </p>
                </div>

                {{-- 3rd column --}}
                <div class="col">
                    <div class="table-responsive-sm">
                        <table class="table align-middle table-nowrap mb-0">
                            <tbody>
                                <tr>
                                    <td style="width: 50%;">
                                        <p class="mb-0 small">
                                            {{ !empty($lastMonthStart) ? \Carbon\Carbon::parse($lastMonthStart)->format('M Y') : '—' }}
                                        </p>
                                    </td>
                                    <td class="text-end">
                                        <h5 class="mb-0">
                                            {{ isset($lastMonthUnits) ? number_format((int) $lastMonthUnits) : 'N/A' }}
                                        </h5>
                                        <small class="text-muted d-block">
                                            ${{ isset($lastMonthRev) ? number_format((float) $lastMonthRev, 2) : 'N/A' }}
                                            USD
                                        </small>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="width: 50%;">
                                        <p class="mb-0 small">
                                            {{ !empty($lastMonthLyStart) ? \Carbon\Carbon::parse($lastMonthLyStart)->format('M Y') : '—' }}
                                        </p>
                                    </td>
                                    <td class="text-end">
                                        <h5 class="mb-0">
                                            {{ isset($lastMonthLyUnits) ? number_format((int) $lastMonthLyUnits) : 'N/A' }}
                                        </h5>
                                        <small class="text-muted d-block">
                                            ${{ isset($lastMonthLyRev) ? number_format((float) $lastMonthLyRev, 2) : 'N/A' }}
                                            USD
                                        </small>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>
