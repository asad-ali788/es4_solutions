@extends('layouts.app')
@section('content')
    <style>
        .wide-column {
            min-width: 200px;
        }

        .forecast-column {
            min-width: 250px;
        }

        /* Sticky header */
        .table thead {
            position: sticky;
            top: 0;
            z-index: 2;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
        }

        .table thead {
            position: sticky;
            top: 0;
            z-index: 4;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
        }

        /* Desktop (md and up) */
        @media (min-width: 768px) {
            .sticky-column-0 {
                position: sticky;
                left: -9px;
                z-index: 3;
                background: #fff;
                width: 100px;
            }

            .sticky-column-1 {
                position: sticky;
                left: 87px;
                /* width of sticky-column-0 */
                z-index: 3;
                background: #fff;
                width: 200px;
            }

            .sticky-column-2 {
                position: sticky;
                left: 295px;
                /* sum of previous sticky columns */
                z-index: 3;
                background: #fff;
                width: 280px;
            }
        }

        .table-header-no-border th {
            border: none !important;
        }
    </style>

    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <h4 class="mb-0">
                        Forecast <span
                            class="fs-6 text-primary">{{ !empty(trim($forecast->order_name ?? '')) ? ' - ' . e($forecast->order_name) : '' }}</span>
                    </h4>
                </div>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            <a href="{{ route('admin.orderforecast.index') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Forecasts
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
                @include('pages.admin.orderforecast.partials.order-forecast-tabs', [
                    'forecast' => $forecast,
                ])

                <div class="card-body">
                    <div class="row g-3 align-items-center mb-3">
                        <!-- LEFT: Search + Info -->
                        <div class="col-12 col-md-3">
                            <div class="d-flex align-items-center gap-2 flex-nowrap">
                                <!-- Search Form -->
                                <form method="GET" action="{{ route('admin.orderforecast.show', $forecast->id) }}"
                                    class="flex-grow-1">
                                    <x-elements.search-box />
                                </form>
                                @if ($user->hasRole(['administrator', 'developer']))
                                    <!-- Info Button -->
                                    <button type="button" class="btn btn-light btn-rounded waves-effect waves-light"
                                        data-bs-toggle="modal" data-bs-target="#promptModal"
                                        title="View AI forecast prompt SKU">
                                        <i class="bx bx-info-circle"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="col-12 col-lg-auto ms-lg-auto">
                            <div class="row g-2 justify-content-lg-end">
                                {{-- <div class="col-12 col-sm-auto d-grid d-sm-block">
                                    <form action="{{ route('admin.orderforecast.generateBulkAI', $forecast->id) }}"
                                        method="POST" style="display:inline;">
                                        @csrf
                                        <button class="btn btn-rounded waves-effect waves-light w-100 w-lg-auto btn-grad"
                                            onclick="return confirm('Dispatch AI forecast generation?')">
                                            ✨ AI Generate
                                        </button>
                                    </form>
                                </div> --}}

                                @can('order_forecast.sku-export')
                                    <div class="col-12 col-sm-auto d-grid d-sm-block">
                                        <a href="{{ route('admin.orderforecast.downloadForecastSnapshotsSku', ['id' => $forecast['id']]) }}"
                                            class="btn btn-success btn-rounded waves-effect waves-light w-100 w-lg-auto"
                                            title="Download Excel">
                                            <i class="mdi mdi-file-excel label-icon"></i> Excel Export
                                        </a>
                                    </div>
                                @endcan
                            </div>
                        </div>

                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-bordered w-100 table-hover"
                                style="min-width: 2500px; border-top: 0 !important;">
                                <thead class="table-light table-header-no-border" style="border-style: hidden !important;">
                                    <tr>
                                        <th>#</th>
                                        <th class="sticky-column-0">Image</th>
                                        <th class="sticky-column-1 text-nowrap">SKU</th>
                                        <th class="">Asin</th>
                                        @if ($forecast->status !== 'finalized')
                                            <th class="text-nowrap">AI Recommendation</th>
                                        @endif
                                        <th class="text-nowrap">Prices</th>
                                        <th class="text-nowrap">Stock in Amazon</th>
                                        <th class="text-nowrap">Stock in W/H</th>
                                        <th class="text-nowrap">Routes</th>
                                        <th class="text-nowrap">In Transit</th>
                                        <th class="text-nowrap">YTD Sales</th>

                                        {{-- Last 3 months --}}
                                        @foreach ($snapshots[0]['months_last_3'] ?? [] as $month)
                                            <th class="text-nowrap text-primary">{{ $month['label'] ?? '-' }}</th>
                                        @endforeach

                                        <th class="text-nowrap text-primary">{{ $currentMonthLabel ?? '-' }}</th>
                                        {{-- Next 12 months --}}
                                        @foreach ($snapshots[0]['forecast_months'] ?? [] as $month)
                                            <th class="wide-column">{{ $month['label'] ?? '-' }}</th>
                                        @endforeach
                                        <th class="wide-column">Total Forecast</th>
                                        <th class="text-nowrap">Total Stock</th>
                                        <th class="text-nowrap">Order Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($snapshots as $snapshot)
                                        <tr>
                                            <td>{{ $snapshotPaginator->firstItem() + $loop->index }}</td>

                                            {{-- Product image --}}
                                            <td class="sticky-column-0 align-middle text-center">
                                                <img src="{{ !empty($snapshot['product_img']) ? $snapshot['product_img'] : asset('assets/images/broken_image.png') }}"
                                                    class="rounded avatar-md" alt="Product Image" style="max-height: 100px;"
                                                    onerror="this.onerror=null; this.src='{{ asset('assets/images/broken_image.png') }}';">
                                            </td>

                                            <td class="sticky-column-1">
                                                {{ $snapshot['product_sku'] ?? '--' }}
                                            </td>

                                            <td class="">
                                                {{ $snapshot['product_asin']['asin1'] ?? '--' }}
                                            </td>

                                            @if ($forecast->status !== 'finalized')
                                                <td id="rec-{{ $snapshot['id'] }}" class="td-break-col"
                                                    @if ($snapshot['run_update'] != 1) onclick="generateAIRecommendation({{ $snapshot['id'] }})" style="cursor: pointer;" @endif>
                                                    @if ($snapshot['run_update'] == 1)
                                                        ✅ Generated
                                                    @else
                                                        ✨ Generate AI
                                                    @endif
                                                </td>
                                            @endif

                                            {{-- Price --}}
                                            <td>
                                                @if (!empty($snapshot['product_price']))
                                                    ${{ $snapshot['product_price'] }}
                                                @else
                                                    <span class="text-muted">--</span>
                                                @endif
                                            </td>

                                            {{-- Amazon stock --}}
                                            <td class="text-info">
                                                {{ $snapshot['country'] ?? '--' }} :
                                                <strong class="text-dark">{{ $snapshot['amazon_stock'] ?? '-' }}</strong>
                                            </td>

                                            <td class="wide-column">{{ $snapshot['warehouse_stock'] ?? 0 }}</td>

                                            <td class="text-nowrap text-info">
                                                US Routes :<strong
                                                    class="text-dark">{{ $snapshot['routes']['in_transit'] ?? 0 }}</strong>
                                            </td>

                                            <td></td>

                                            <td>{{ $snapshot['ytd_sales'] ?? 0 }}</td>

                                            {{-- Last 3 months --}}
                                            @foreach ($snapshot['months_last_3'] ?? [] as $month)
                                                <td>{{ $snapshot['sales_by_month_last_3_months'][$month['key']] ?? 0 }}
                                                </td>
                                            @endforeach

                                            <td>-</td> {{-- current month cell --}}

                                            {{-- Next 12 months --}}
                                            @foreach ($snapshot['forecast_months'] ?? [] as $month)
                                                <td class="wide-column">

                                                    {{-- INPUT --}}
                                                    <input type="text" class="form-control form-control-sm sold-input"
                                                        data-snapshot-id="{{ $snapshot['id'] }}"
                                                        data-month-key="{{ $month['key'] }}"
                                                        value="{{ $month['input_value'] }}" maxlength="6" pattern="\d*"
                                                        inputmode="numeric"
                                                        @if ($forecast->status === 'finalized') disabled @endif
                                                        placeholder="Expected units">

                                                    <hr class="my-1" />
                                                    @php
                                                        $aiUnits = $month['ai_sold'] ?? null;
                                                        $soldUnits = $month['sold'] ?? 0;

                                                        // Apply class only when AI < Sold
                                                        $highlightClass =
                                                            $aiUnits !== null && $aiUnits < $soldUnits
                                                                ? 'bg-danger-subtle'
                                                                : '';
                                                    @endphp

                                                    {{-- CELL-WISE TABLE --}}
                                                    <div class="row gx-2 gy-1 align-items-center text-start">

                                                        {{-- AI FC --}}
                                                        <div class="col-6 fw-bold ai-gradient-text">AI FC:</div>
                                                        <div class="col-6 {{ $highlightClass }}"
                                                            id="ai-sold-{{ $snapshot['id'] }}-{{ $month['key'] }}"
                                                            data-sold="{{ $soldUnits }}">
                                                            {{ $aiUnits ?? '-' }}
                                                        </div>

                                                        {{-- SYS FC --}}
                                                        <div class="col-6 fw-bold text-primary">SYS FC:</div>
                                                        <div class="col-6">
                                                            {{ $month['sys_sold'] ?? '-' }}
                                                        </div>

                                                        {{-- SOLD --}}
                                                        <div class="col-6 fw-bold">Sold:</div>
                                                        <div class="col-6">
                                                            {{ $month['sold'] ?? '-' }}
                                                        </div>

                                                        {{-- ASP --}}
                                                        <div class="col-6 fw-bold">ASP:</div>
                                                        <div class="col-6">
                                                            ${{ $month['asp'] ?? '-' }} USD
                                                        </div>

                                                        {{-- <div class="col-6 fw-bold">ACOS:</div>
                                                        <div class="col-6">
                                                            {{ isset($month['acos']) ? round($month['acos'], 2) : '-' }} %
                                                        </div>

                                                        <div class="col-6 fw-bold">TACOS:</div>
                                                        <div class="col-6">
                                                            {{ isset($month['tacos']) ? round($month['tacos'], 2) : '-' }} %
                                                        </div> --}}

                                                        {{-- <div class="col-6 fw-bold">AI ASP:</div>
                                                        <div class="col-6">
                                                            {{ $month['ai_asp'] ?? '-' }}
                                                        </div>

                                                        <div class="col-6 fw-bold">AI ACOS:</div>
                                                        <div class="col-6">
                                                            {{ $month['ai_acos'] ?? '-' }}
                                                        </div>

                                                        <div class="col-6 fw-bold">AI TACOS:</div>
                                                        <div class="col-6">
                                                            {{ $month['ai_tacos'] ?? '-' }}
                                                        </div> --}}
                                                    </div>

                                                    {{-- @if (!empty($month['recommendations']))
                                                            <div class="mt-1">
                                                                <a href="javascript:void(0);"
                                                                    onclick='showRecommendations(@json($month))'
                                                                    title="View AI Recommendations"
                                                                    class="btn btn-sm btn-outline-success d-inline-flex align-items-center">
                                                                    <i class="bx bx-trending-up"></i>
                                                                    <span class="ms-1 small">AI Insights</span>
                                                                </a>
                                                            </div>
                                                        @endif --}}

                                                    {{-- @if (isset($month['asp_original'], $month['asp_currency']) && $month['asp_currency'] !== 'USD')
                                                        <div class="text-muted small mt-1">
                                                            (Original: {{ $month['asp_original'] }} {{ $month['asp_currency'] }})
                                                        </div>
                                                    @endif --}}

                                                </td>
                                            @endforeach
                                            {{-- Total sold --}}
                                            @php
                                                $totalAiFc = 0;
                                                $totalSysFc = 0;
                                                $totalSold = 0;

                                                foreach ($snapshot['forecast_months'] ?? [] as $month) {
                                                    $totalAiFc += (int) ($month['ai_sold'] ?? 0);
                                                    $totalSysFc += (int) ($month['sys_sold'] ?? 0);
                                                    $totalSold += (int) ($month['sold'] ?? 0);
                                                }
                                            @endphp
                                            <td class="text-nowrap">
                                                <div class="row gx-2 gy-1 align-items-center">
                                                    {{-- TOTAL SOLD --}}
                                                    <div class="col-6 fw-bold">Total Sold:</div>
                                                    <div class="col-6">
                                                        <strong class="total-sold"
                                                            data-snapshot-id="{{ $snapshot['id'] }}">
                                                            {{ $snapshot['sold_values_sum'] ?? 0 }}
                                                        </strong>
                                                    </div>
                                                    <div class="col-12">
                                                        <hr class="my-2">
                                                    </div>
                                                    {{-- AI FC --}}
                                                    <div class="col-6 fw-bold ai-gradient-text">AI FC:</div>
                                                    <div class="col-6 fw-bold">
                                                        {{ $totalAiFc }}
                                                    </div>
                                                    {{-- SYS FC --}}
                                                    <div class="col-6 fw-bold text-primary">SYS FC:</div>
                                                    <div class="col-6">
                                                        {{ $totalSysFc }}
                                                    </div>
                                                    {{-- SOLD --}}
                                                    <div class="col-6 fw-bold">Sold:</div>
                                                    <div class="col-6 text-dark">
                                                        {{ $totalSold }}
                                                    </div>
                                                </div>
                                            </td>

                                            {{-- Total stock --}}
                                            <td>
                                                <strong>{{ $snapshot['row_total_stock'] ?? 0 }}</strong>
                                            </td>

                                            {{-- Order amount input --}}
                                            <td class="text-nowrap">
                                                @if ($forecast->status !== 'finalized')
                                                    <input type="text" name="order_amounts[{{ $snapshot['id'] }}]"
                                                        class="form-control form-control-sm order-amount-input"
                                                        data-snapshot-id="{{ $snapshot['id'] }}" maxlength="6"
                                                        pattern="\d+(\.\d{1,2})?" inputmode="decimal"
                                                        placeholder="Enter amount"
                                                        value="{{ old('order_amounts.' . $snapshot['id']) ?? ($snapshot['order_amount'] ?? '') }}">
                                                @else
                                                    <strong>{{ $snapshot['order_amount'] ?? 0 }}</strong>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">
                            {{ $snapshotPaginator->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                        <!-- end table responsive -->
                    </div>
                    <!-- end card body -->
                </div>
                <!-- end card -->
            </div>
            <!-- end col -->
        </div>
    </div>

    <div class="modal fade" id="promptModal" tabindex="-1" aria-labelledby="promptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="promptModalLabel">AI Forecast Prompt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <pre style="white-space: pre-wrap;">{!! $modalPrompt !!}</pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-rounded" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function generateAIRecommendation(snapshotId) {
            const $cell = $(`#rec-${snapshotId}`);
            $cell
                .html('<i class="bx bx-hourglass bx-spin font-size-16 align-middle me-2"></i> Generating...')
                .css('cursor', 'default')
                .off('click');
            $.ajax({
                url: "{{ route('admin.orderforecast.generateAI') }}",
                method: "POST",
                data: {
                    snapshot_id: snapshotId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (!response.success) {
                        $cell.text('✨ Generate AI').css('cursor', 'pointer');
                        showToast('error', response.message || 'Failed to start AI generation.');
                        return;
                    }

                    let attempts = 0;
                    const maxAttempts = 24;
                    const intervalId = setInterval(() => {
                        attempts++;

                        $.ajax({
                            url: `{{ route('admin.orderforecast.getStatus', '') }}/${snapshotId}`,
                            method: "GET",
                            success: function(res) {
                                if (res.success && res.run_update === 1) {
                                    clearInterval(intervalId);
                                    $cell.text('✅ Generated').css('cursor', 'default');

                                    const aiMonths = res.forecast ?? [];
                                    if (!aiMonths.length) {
                                        showToast('warning',
                                            '⚠️ No forecast data returned.');
                                        return;
                                    }

                                    // Update each month's div
                                    aiMonths.forEach(item => {
                                        if (!item.month) return;

                                        const $div = $(
                                            `#ai-sold-${snapshotId}-${item.month}`
                                        );
                                        if (!$div.length) return;

                                        const aiUnits = parseInt(item.ai ?? 0);
                                        const soldUnits = parseInt($div.data(
                                            'sold') ?? 0);

                                        $div.html(
                                            `<strong>AI FC:</strong> <span class="ai-gradient-text">${aiUnits}</span>`
                                        );
                                        $div.toggleClass('bg-danger-subtle',
                                            aiUnits < soldUnits);
                                    });

                                    showToast('success',
                                        '✅ AI recommendations updated successfully!');
                                }
                            },
                            error: function(xhr) {
                                console.error("Polling error:", xhr.responseText);
                            }
                        });

                        if (attempts >= maxAttempts) {
                            clearInterval(intervalId);
                            $cell.text('✨ Generate AI')
                                .css('cursor', 'pointer')
                                .on('click', () => generateAIRecommendation(snapshotId));
                            showToast('warning', '⚠️ AI generation timed out. Please try again.');
                        }
                    }, 5000);
                },
                error: function(xhr) {
                    console.error("Generation call failed:", xhr.responseText);
                    $cell.text('✨ Generate AI')
                        .css('cursor', 'pointer')
                        .on('click', () => generateAIRecommendation(snapshotId));
                    showToast('error', '❌ Failed to generate AI recommendation.');
                }
            });
        }

        $(document).ready(function() {
            let orderAmountTimeout;
            let soldValueTimeout;

            $('.order-amount-input').on('keyup', function() {
                const $input = $(this);
                clearTimeout(orderAmountTimeout);
                orderAmountTimeout = setTimeout(function() {
                    const snapshotId = $input.data('snapshot-id');
                    const value = $input.val();

                    $.ajax({
                        url: "{{ route('admin.orderforecast.updateOrderAmount') }}",
                        method: "PUT",
                        contentType: "application/json",
                        data: JSON.stringify({
                            snapshot_id: snapshotId,
                            order_amount: value
                        }),
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            $input.removeClass('is-invalid');
                            showToast('success', response.message ||
                                'Order amount updated.');
                        },
                        error: function(xhr) {
                            console.error(xhr.responseText);
                            $input.addClass('is-invalid');
                        }
                    });
                }, 500);
            });

            $('.sold-input').on('keyup', function() {
                const $input = $(this);
                clearTimeout(soldValueTimeout);

                soldValueTimeout = setTimeout(function() {
                    const snapshotId = $input.data('snapshot-id');
                    const monthKey = $input.data('month-key');
                    const value = $input.val();

                    // Basic validation
                    if (!/^\d+$/.test(value) && value !== '') {
                        $input.addClass('is-invalid');
                        showToast('error', 'Please enter a valid numeric value.');
                        return;
                    } else {
                        $input.removeClass('is-invalid');
                    }

                    $.ajax({
                        url: "{{ route('admin.orderforecast.updateSoldValue') }}",
                        method: "PUT",
                        contentType: "application/json",
                        data: JSON.stringify({
                            snapshot_id: snapshotId,
                            month_key: monthKey,
                            sold_value: value
                        }),
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                showToast('success', response.message ||
                                    'Sold value updated.');

                                // Update total sold column dynamically
                                const $allInputs = $(
                                    `.sold-input[data-snapshot-id='${snapshotId}']`);
                                let total = 0;
                                $allInputs.each(function() {
                                    const val = parseFloat($(this).val()) || 0;
                                    total += val;
                                });
                                $(`.total-sold[data-snapshot-id='${snapshotId}']`).text(
                                    total);
                            } else {
                                showToast('error', response.message ||
                                    'Failed to update sold value.');
                            }
                        },
                        error: function(xhr) {
                            console.error(xhr.responseText);
                            $input.addClass('is-invalid');
                            showToast('error', 'Error updating sold value.');
                        }
                    });
                }, 600);
            });
        });
    </script>
@endsection
