<div>
    {{-- ✅ Real content --}}
    <div wire:loading.remove>
        <div class="row g-3 mt-2 align-items-stretch">

            {{-- SP card --}}
            <div class="col-12 col-md-6 d-flex">
                <div class="card h-100 w-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar-xs me-2">
                                <span class="avatar-title rounded-circle glance glance-primary font-size-18">
                                    <i class="bx bx-purchase-tag-alt"></i>
                                </span>
                            </div>
                            <h5 class="font-size-14 mb-0">
                                Top 10 Campaigns SP -
                                {{ !empty($campaign['c_date']) ? \Carbon\Carbon::parse($campaign['c_date'])->format('M j, Y') : 'N/A' }}
                                PST
                            </h5>
                        </div>

                        <p class="text-muted font-size-12 mb-0 mt-0">Campaign Performance Report</p>

                        <div class="table-responsive" data-simplebar="init" style="max-height: 300px;">
                            <table class="table table-sm align-middle mb-0">
                                <tbody>
                                    @php $sp = $campaign['top_10']['sp'] ?? []; @endphp

                                    @if (!empty($sp) && count($sp) > 0)
                                        @foreach ($sp as $item)
                                            <tr style="line-height: 1.2;">
                                                <td>#{{ $loop->iteration }}</td>
                                                <td style="width: 70%;">
                                                    <h6 class="text-truncate"
                                                        style="font-size: 12px; word-break: break-word; white-space: normal;">
                                                        {{ $item->campaign->campaign_name ?? ($item->campaign_id ?? 'N/A') }}
                                                    </h6>
                                                </td>
                                                <td class="text-end p-1 pe-2" style="width: 30%;">
                                                    <small class="text-muted">Sales</small>
                                                    <h5 style="font-size: 12px; margin-bottom: 1px; font-weight: 500;">
                                                        ${{ number_format($item->sales7d_usd ?? 0, 2) }}
                                                    </h5>
                                                    <small class="text-muted">Spend</small>
                                                    <h5 style="font-size: 12px; font-weight: 500;">
                                                        ${{ number_format($item->cost_usd ?? 0, 2) }}
                                                    </h5>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="3" class="text-center text-muted small p-3">
                                                <div
                                                    class="d-flex flex-column align-items-center justify-content-center">
                                                    <div class="w-50 opacity-50"
                                                        style="filter: grayscale(50%) blur(.5px);">
                                                        <img src="{{ asset('assets/images/empty-folder.png') }}"
                                                            alt="No data" class="img-fluid" style="max-width: 120px;">
                                                    </div>
                                                    <div class="mb-2">No data available now</div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>

            {{-- SB card --}}
            <div class="col-12 col-md-6 d-flex">
                <div class="card h-100 w-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar-xs me-2">
                                <span class="avatar-title rounded-circle glance glance-primary font-size-18">
                                    <i class="bx bx-purchase-tag-alt"></i>
                                </span>
                            </div>
                            <h5 class="font-size-14 mb-0">
                                Top 10 Campaigns SB -
                                {{ !empty($campaign['c_date']) ? \Carbon\Carbon::parse($campaign['c_date'])->format('M j, Y') : 'N/A' }}
                                PST
                            </h5>
                        </div>

                        <p class="text-muted font-size-12 mb-0 mt-0">Campaign Performance Report</p>

                        <div class="table-responsive" data-simplebar="init" style="max-height: 300px;">
                            <table class="table table-sm align-middle mb-0">
                                <tbody>
                                    @php $sb = $campaign['top_10']['sb'] ?? []; @endphp

                                    @if (!empty($sb) && count($sb) > 0)
                                        @foreach ($sb as $item)
                                            <tr style="line-height: 1.2;">
                                                <td>#{{ $loop->iteration }}</td>
                                                <td style="width: 70%;">
                                                    <h6 class="text-truncate"
                                                        style="font-size: 12px; word-break: break-word; white-space: normal;">
                                                        {{ $item->campaign->campaign_name ?? ($item->campaign_id ?? 'N/A') }}
                                                    </h6>
                                                </td>
                                                <td class="text-end p-1 pe-2" style="width: 30%;">
                                                    <small class="text-muted">Sales</small>
                                                    <h5 style="font-size: 12px; font-weight: 500;">
                                                        ${{ number_format($item->sales_usd ?? 0, 2) }}
                                                    </h5>
                                                    <small class="text-muted">Spend</small>
                                                    <h5 style="font-size: 12px; font-weight: 500;">
                                                        ${{ number_format($item->cost_usd ?? 0, 2) }}
                                                    </h5>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="3" class="text-center text-muted small p-3">
                                                <div
                                                    class="d-flex flex-column align-items-center justify-content-center">
                                                    <div class="w-50 opacity-50"
                                                        style="filter: grayscale(50%) blur(.5px);">
                                                        <img src="{{ asset('assets/images/empty-folder.png') }}"
                                                            alt="No data" class="img-fluid" style="max-width: 120px;">
                                                    </div>
                                                    <div class="mb-2">No data available now</div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
