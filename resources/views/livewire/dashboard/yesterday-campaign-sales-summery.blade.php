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
                    <h5 class="font-size-14 mb-0">
                        Yesterday's Sales Summary
                        {{ !empty($campaign['c_date']) ? \Carbon\Carbon::parse($campaign['c_date'])->format('M j, Y') : 'N/A' }}
                    </h5>
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
                                <p class="text-muted font-size-12 mb-0">SPONSORED_PRODUCTS</p>
                            </td>
                            <td>
                                <p class="text-muted mb-1">Sales</p>
                                <h5 class="mb-0" style="font-size: 12px; font-weight: 500;">
                                    ${{ number_format($campaign['totals']['sp']['sales'] ?? 0, 2) ?? 'N/A' }}
                                </h5>
                            </td>
                            <td>
                                <p class="text-muted mb-1">Spend</p>
                                <h5 class="mb-0" style="font-size: 12px; font-weight: 500;">
                                    ${{ number_format($campaign['totals']['sp']['cost'] ?? 0, 2) ?? 'N/A' }}
                                </h5>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <h5 class="font-size-12 mb-1">Campaign Sales</h5>
                                <p class="text-muted font-size-12 mb-0">SPONSORED_BRANDS</p>
                            </td>
                            <td>
                                <p class="text-muted mb-1">Sales</p>
                                <h5 class="mb-0" style="font-size: 12px; font-weight: 500;">
                                    ${{ number_format($campaign['totals']['sb']['sales'] ?? 0, 2) ?? 'N/A' }}
                                </h5>
                            </td>
                            <td>
                                <p class="text-muted mb-1">Spend</p>
                                <h5 class="mb-0" style="font-size: 12px; font-weight: 500;">
                                    ${{ number_format($campaign['totals']['sb']['cost'] ?? 0, 2) ?? 'N/A' }}
                                </h5>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <h5 class="font-size-12 mb-1">Campaign Sales</h5>
                                <p class="text-muted font-size-12 mb-0">SPONSORED_DISPLAY</p>
                            </td>
                            <td>
                                <p class="text-muted mb-1">Sales</p>
                                <h5 class="mb-0" style="font-size: 12px; font-weight: 500;">
                                    ${{ number_format($campaign['totals']['sd']['sales'] ?? 0, 2) ?? 'N/A' }}
                                </h5>
                            </td>
                            <td>
                                <p class="text-muted mb-1">Spend</p>
                                <h5 class="mb-0" style="font-size: 12px; font-weight: 500;">
                                    ${{ number_format($campaign['totals']['sd']['cost'] ?? 0, 2) ?? 'N/A' }}
                                </h5>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <h5 class="font-size-12 mb-1">Cumulative</h5>
                                <p class="text-muted font-size-12 mb-0">SP + SB + SD</p>
                            </td>
                            <td>
                                <p class="text-muted mb-1">Sales</p>
                                <h5 class="mb-0" style="font-size: 12px; font-weight: 500;">
                                    ${{ number_format(($campaign['totals']['sp']['sales'] ?? 0) + ($campaign['totals']['sb']['sales'] ?? 0) + ($campaign['totals']['sd']['sales'] ?? 0), 2) ?? 'N/A' }}
                                </h5>
                            </td>
                            <td>
                                <p class="text-muted mb-1">Spend</p>
                                <h5 class="mb-0" style="font-size: 12px; font-weight: 500;">
                                    ${{ number_format(($campaign['totals']['sp']['cost'] ?? 0) + ($campaign['totals']['sb']['cost'] ?? 0) + ($campaign['totals']['sd']['cost'] ?? 0), 2) ?? 'N/A' }}
                                </h5>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
