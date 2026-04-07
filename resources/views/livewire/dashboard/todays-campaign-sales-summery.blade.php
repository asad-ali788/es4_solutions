<div class="col-12 col-lg-6 d-flex">
    <div class="card w-100 mb-0 shadow-sm">
        <div class="card-body">
            <div class="clearfix">
                <div class="d-flex align-items-center mb-2">
                    <div class="avatar-xs me-2">
                        <span class="avatar-title rounded-circle glance glance-warning font-size-18">
                            <i class="bx bx-cart-alt"></i>
                        </span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between w-100">
                        <h5 class="font-size-14 mb-0">
                            Todays's Sales Summary
                            {{ !empty($adSales['today']) ? \Carbon\Carbon::parse($adSales['today'])->format('M j, Y') : 'N/A' }}
                        </h5>
                        <a href="{{ route('admin.dashboard.detailed-todays-sales-summery') }}" class="text-muted"
                             data-bs-toggle="tooltip" title="Expand view">
                            <span class="avatar-title rounded-circle glance glance-light font-size-18">
                                <i class="bx bx-right-arrow-alt p-1"></i>
                            </span>
                        </a>
                    </div>
                </div>
                <p class="text-muted font-size-12 mb-0 mt-0">
                    Campaign Performance Report — <strong>USA</strong> & <strong>CA</strong>
                </p>
            </div>
            <div class="table-responsive mt-2">
                <table class="table align-middle mb-0">
                    <tbody>
                        <tr>
                            <td>
                                <h5 class="font-size-12 mb-1">Campaign Sales</h5>
                                <p class="text-muted mb-0">SPONSORED_PRODUCTS</p>
                            </td>
                            <td>
                                <p class="text-muted mb-1">Sales</p>
                                <h5 class="mb-0" style="font-size: 12px; font-weight: 500;">
                                    ${{ number_format($adSales['campaign']['sales'] ?? 0, 2) ?? 'N/A' }}
                                </h5>
                            </td>
                            <td>
                                <p class="text-muted mb-1">Spend</p>
                                <h5 class="mb-0" style="font-size: 12px; font-weight: 500;">
                                    ${{ number_format($adSales['campaign']['spend'] ?? 0, 2) ?? 'N/A' }}
                                </h5>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <h5 class="font-size-12 mb-1">Campaign Sales</h5>
                                <p class="text-muted mb-0">SPONSORED_BRANDS</p>
                            </td>
                            <td>
                                <p class="text-muted mb-1">Sales</p>
                                <h5 class="mb-0" style="font-size: 12px; font-weight: 500;">
                                    ${{ number_format($adSales['campaign_sb']['sales'] ?? 0, 2) ?? 'N/A' }}
                                </h5>
                            </td>
                            <td>
                                <p class="text-muted mb-1">Spend</p>
                                <h5 class="mb-0" style="font-size: 12px; font-weight: 500;">
                                    ${{ number_format($adSales['campaign_sb']['spend'] ?? 0, 2) ?? 'N/A' }}
                                </h5>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <h5 class="font-size-12 mb-1">Campaign Sales</h5>
                                <p class="text-muted mb-0">SPONSORED_DISPLAY</p>
                            </td>
                            <td>
                                <p class="text-muted mb-1">Sales</p>
                                <h5 class="mb-0" style="font-size: 12px; font-weight: 500;">
                                    ${{ number_format($adSales['campaign_sd']['sales'] ?? 0, 2) ?? 'N/A' }}
                                </h5>
                            </td>
                            <td>
                                <p class="text-muted mb-1">Spend</p>
                                <h5 class="mb-0" style="font-size: 12px; font-weight: 500;">
                                    ${{ number_format($adSales['campaign_sd']['spend'] ?? 0, 2) ?? 'N/A' }}
                                </h5>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <h5 class="font-size-12 mb-1">Cumulative</h5>
                                <p class="text-muted mb-0">SP + SB + SD</p>
                            </td>
                            <td>
                                <p class="text-muted mb-1">Sales</p>
                                <h5 class="mb-0" style="font-size: 12px; font-weight: 500;">
                                    ${{ is_numeric(data_get($adSales, 'cumulative.sales'))
                                        ? number_format(data_get($adSales, 'cumulative.sales'), 2)
                                        : 'N/A' }}
                                </h5>
                            </td>
                            <td>
                                <p class="text-muted mb-1">Spend</p>
                                <h5 class="mb-0" style="font-size: 12px; font-weight: 500;">
                                    ${{ is_numeric(data_get($adSales, 'cumulative.spend'))
                                        ? number_format(data_get($adSales, 'cumulative.spend'), 2)
                                        : 'N/A' }}
                                </h5>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
