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
            z-index: 4;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
        }

        /* Desktop (md and up) */
        @media (min-width: 768px) {
            .sticky-column-0 {
                position: sticky;
                left: 0;
                z-index: 3;
                background: #fff;
                width: 100px;
            }

            .sticky-column-1 {
                position: sticky;
                left: 96px;
                /* width of sticky-column-0 */
                z-index: 3;
                background: #fff;
                width: 200px;
            }
        }

        .ai-running {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            min-height: 38px;
            padding: 0.45rem 0.9rem;

            font-size: 0.85rem;
            color: #4b5563;

            background: rgba(99, 102, 241, 0.08);
            border: 1px dashed rgba(99, 102, 241, 0.35);
            border-radius: 999px;
        }

        .table-header-no-border th {
            border: none !important;
        }

        .asin-cell .copy-icon {
            opacity: 0;
            visibility: hidden;
            cursor: pointer;
            transition: opacity 0.15s ease-in-out;
            color: #6c757d;
        }

        .asin-cell:hover .copy-icon {
            opacity: 1;
            visibility: visible;
        }

        .asin-cell .copy-icon:hover {
            color: #198754;
            /* bootstrap success */
        }
    </style>
    @php $prevYear = now()->year - 1; @endphp

    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <h4 class="mb-0">
                        Forecast <span class="fs-6 text-primary">
                            {{ !empty(trim($forecast->order_name ?? '')) ? ' - ' . e($forecast->order_name) : '' }}</span>
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
                                <form method="GET" action="{{ route('admin.orderforecastasin.show', $forecast->id) }}"
                                    class="flex-grow-1">
                                    <x-elements.search-box />
                                </form>
                                @if ($user->hasRole(['administrator', 'developer']))
                                    <!-- Info Button -->
                                    <button type="button" class="btn btn-light btn-rounded waves-effect waves-light"
                                        data-bs-toggle="modal" data-bs-target="#promptModal"
                                        title="View AI forecast prompt Asin">
                                        <i class="bx bx-info-circle"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="col-12 col-lg-auto ms-lg-auto">
                            <div class="row g-2 justify-content-lg-end">
                                {{-- <div class="col-12 col-sm-auto d-grid d-sm-block">
                                    @if ($forecast->status === 'finalized')
                                        <button type="button"
                                            onclick="alert('⚠️ This forecast is finalized. AI generation is not allowed.')"
                                            class="btn btn-rounded waves-effect waves-light w-100 w-lg-auto btn-grad"
                                            disabled>
                                            ✨ AI Generate
                                        </button>
                                    @elseif ($runningCount > 0)
                                        <span class="ai-running w-100 w-lg-auto"
                                            title="The AI forecast job is running. You cannot start a new job until it completes.">
                                            <span class="spinner-border spinner-border-sm me-2"></span>
                                            AI forecast is running…
                                        </span>
                                    @else
                                        <form action="{{ route('admin.orderforecastasin.generateBulkAI', $forecast->id) }}"
                                            method="POST" class="d-inline-block w-100 w-lg-auto"
                                            onsubmit="return confirm('Dispatch AI forecast generation?')">
                                            @csrf
                                            <button type="submit"
                                                class="btn btn-rounded waves-effect waves-light w-100 w-lg-auto btn-grad">
                                                ✨ AI Generate
                                            </button>
                                        </form>
                                    @endif
                                </div> --}}
                                @can('order_forecast.asin-export')
                                    <div class="col-12 col-sm-auto d-grid d-sm-block">
                                        <a href="{{ route('admin.orderforecastasin.downloadForecastSnapshotsAsin', ['id' => $forecast['id']]) }}"
                                            class="btn btn-success btn-rounded waves-effect waves-light w-100 w-lg-auto"
                                            title="Download Excel">
                                            <i class="mdi mdi-file-excel label-icon"></i> Excel Export
                                        </a>
                                    </div>
                                @endcan
                                @if ($forecast->status !== 'finalized')
                                    <div class="col-12 col-sm-auto d-grid d-sm-block">
                                        <button type="button"
                                            class="btn btn-primary btn-rounded waves-effect waves-light w-100 w-lg-auto"
                                            data-bs-toggle="modal" data-bs-target="#bulkUploadModal">
                                            <i class="bx bx-upload me-1"></i> Bulk Update
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-bordered w-100 table-hover"
                            style="min-width: 2500px; border-top: 0 !important;">
                            <thead class="table-light table-header-no-border" style="border-style: hidden !important;">
                                <tr>
                                    <th>#</th>
                                    <th class="sticky-column-0">Image</th>
                                    <th class="sticky-column-1 text-nowrap">Asin</th>
                                    <th class="text-nowrap">Product Name</th>
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

                                        <td class="sticky-column-0 align-middle text-center"><img
                                                src="{{ $snapshot['product_img'] ?? asset('assets/images/broken_image.png') }}"
                                                class="rounded avatar-md" alt="Product"></td>
                                        {{-- <td class="sticky-column-1 wide-column">{{ $snapshot['product_asin'] ?? '--' }}</td> --}}
                                        <td class="sticky-column-1 asin-cell">
                                            @php
                                                $soldData = [
                                                    'asin' => $snapshot['product_asin'],
                                                    'forecast' => collect($snapshot['forecast_months'] ?? [])
                                                        ->map(
                                                            fn($m) => [
                                                                'month' => $m['key'],
                                                                'sold' => (int) ($m['sold'] ?? 0),
                                                            ],
                                                        )
                                                        ->values(),
                                                ];
                                            @endphp

                                            <div class="d-flex align-items-center gap-2">
                                                {{-- ASIN --}}
                                                @if ($user->hasRole(['administrator', 'developer']))
                                                    <a href="{{ route('admin.orderforecastasin.asinSysBreakdown', [
                                                        'asin' => $snapshot['product_asin'],
                                                        'forecastId' => $snapshot['order_forecast_id'],
                                                    ]) }}"
                                                        target="_blank" class="text-success text-decoration-none fw-bold">
                                                        {{ $snapshot['product_asin'] }}
                                                    </a>
                                                    <a href="javascript:void(0)" class="text-decoration-none">
                                                        <i class="bx bx-copy copy-icon text-muted cursor-pointer"
                                                            title="Copy sold data"
                                                            onclick='copySoldData(@json($soldData))'></i>

                                                    </a>
                                                @else
                                                    <span class="fw-bold">{{ $snapshot['product_asin'] }}</span>
                                                @endif
                                                {{-- Copy icon --}}
                                            </div>
                                        </td>

                                        <td class="wide-column ellipsis-text"
                                            title="{{ $asinProductNameMap[$snapshot['product_asin']] ?? '--' }}">
                                            {{ $asinProductNameMap[$snapshot['product_asin']] ?? '--' }}
                                        </td>

                                        @if ($forecast->status !== 'finalized')
                                            <td id="rec-{{ $snapshot['id'] }}" class="td-break-col wide-column"
                                                @if ($snapshot['run_update'] != 1) onclick="generateAIRecommendation({{ $snapshot['id'] }})" style="cursor: pointer;" @endif>
                                                @if ($snapshot['run_update'] == 1)
                                                    ✅ Generated
                                                @else
                                                    ✨ Generate AI
                                                @endif
                                            </td>
                                        @endif
                                        {{-- <td>${{ $snapshot['product_price'] ?? '--' }}</td> --}}
                                        <td>
                                            @if (is_array($snapshot['product_price']))
                                                @foreach ($snapshot['product_price'] as $sku => $price)
                                                    {{ $sku }}: ${{ $price }}<br>
                                                @endforeach
                                            @elseif(!empty($snapshot['product_price']))
                                                ${{ $snapshot['product_price'] }}
                                            @else
                                                --
                                            @endif
                                        </td>

                                        <td class="text-info">
                                            {{ $snapshot['country'] ?? '--' }} :
                                            <strong class="text-dark">{{ $snapshot['amazon_stock'] ?? '-' }}</strong>
                                        </td>

                                        <td>{{ $snapshot['warehouse_stock'] ?? 0 }}</td>

                                        <td class="text-nowrap text-info">
                                            US Routes :<strong class="text-dark">{{ $snapshot['routes']['in_transit'] ?? 0 }}</strong>
                                        </td>

                                        <td></td>

                                        <td>{{ $snapshot['ytd_sales'] ?? 0 }}</td>

                                        {{-- Last 3 months --}}
                                        @foreach ($snapshot['months_last_3'] ?? [] as $month)
                                            <td>{{ $snapshot['sales_by_month_last_3_months'][$month['key']] ?? 0 }}
                                            </td>
                                        @endforeach

                                        {{-- Next 12 months --}}
                                        @foreach ($snapshot['forecast_months'] ?? [] as $month)
                                            <td class="wide-column">
                                                {{-- INPUT / FINAL VALUE --}}
                                                @if ($forecast->status !== 'finalized')
                                                    <livewire:forecast.forecast-asin-sold-input :snapshot-id="$snapshot['id']"
                                                        :month-key="$month['key']" :sold-value="$month['input_value']"
                                                        wire:key="sold_{{ $snapshot['id'] }}_{{ $month['key'] }}" />
                                                @else
                                                    <input type="text" class="form-control"
                                                        value="{{ $month['input_value'] ?? '-' }}" disabled>
                                                @endif
                                                <hr class="my-1" />
                                                @php
                                                    $aiUnits = $month['ai_sold'] ?? null;
                                                    $soldUnits = $month['sold'] ?? 0;
                                                    $showPulse = $aiUnits !== null && $aiUnits < $soldUnits;
                                                @endphp

                                                {{-- 4 ROWS × 2 COLUMNS (LABEL | VALUE) --}}
                                                <div class="row gx-2 gy-1 align-items-center">
                                                    {{-- Row 1 --}}
                                                    <div class="col-6 fw-bold ai-gradient-text">AI FC:</div>
                                                    <div class="col-6 fw-bold">
                                                        {{ $aiUnits ?? '-' }}
                                                        @if ($showPulse)
                                                            <span class="pulse pulse-danger ms-1"
                                                                style="width:8px;height:8px;display:inline-block;"
                                                                title="AI forecast is lower than actual sales"></span>
                                                        @endif
                                                    </div>
                                                    {{-- Row 2 --}}
                                                    <div class="col-6 fw-bold text-primary">SYS FC:</div>
                                                    <div class="col-6">
                                                        {{ $month['sys_sold'] ?? '-' }}
                                                    </div>
                                                    {{-- Row 3 --}}
                                                    <div class="col-6 fw-bold">Sold ({{ $prevYear }}):</div>
                                                    <div class="col-6">
                                                        {{ $soldUnits }}
                                                    </div>
                                                    {{-- Row 4 --}}
                                                    <div class="col-6 fw-bold">ASP:</div>
                                                    <div class="col-6">
                                                         ${{ $month['asp'] ?? '-' }} USD
                                                    </div>
                                                    {{--
                                                    <div class="col-6 fw-semibold">ACOS:</div>
                                                    <div class="col-6">
                                                        {{ isset($month['acos']) ? number_format($month['acos'], 2) : '-' }}
                                                    </div>

                                                    <div class="col-6 fw-semibold">TACOS:</div>
                                                    <div class="col-6">
                                                        {{ isset($month['tacos']) ? number_format($month['tacos'], 2) : '-' }}
                                                    </div> --}}
                                                </div>
                                                {{-- OPTIONAL: Original ASP currency (kept commented as requested) --}}

                                                {{-- @if (isset($month['asp_original'], $month['asp_currency']) && $month['asp_currency'] !== 'USD')
                                                    <div class="text-muted small mt-1">
                                                        (Original: {{ $month['asp_original'] }}
                                                        {{ $month['asp_currency'] }})
                                                    </div>
                                                @endif --}}
                                            </td>
                                        @endforeach
                                        @php
                                            $totalAiFc  = 0;
                                            $totalSysFc = 0;
                                            $totalSold  = 0;

                                            foreach ($snapshot['forecast_months'] ?? [] as $month) {
                                                $totalAiFc  += (int) ($month['ai_sold'] ?? 0);
                                                $totalSysFc += (int) ($month['sys_sold'] ?? 0);
                                                $totalSold  += (int) ($month['sold'] ?? 0);
                                            }
                                        @endphp

                                        <td class="text-nowrap">
                                            {{-- TOTAL SOLD --}}
                                            <div class="row gx-2 gy-1 align-items-center">
                                                <div class="col-6 fw-bold">Total Sold:</div>
                                                <div class="col-6">
                                                    {{ $snapshot['sold_values_sum'] ?? 0 }}
                                                </div>
                                            </div>

                                            <hr class="my-2">
                                            {{-- FORECAST SUMMARY --}}
                                            <div class="row gx-2 gy-1 align-items-center mt-1">
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

                                        <td>{{ $snapshot['row_total_stock'] ?? 0 }}</td>
                                        <td class="text-nowrap">
                                            @if ($forecast->status === 'finalized')
                                                {{ $snapshot['order_amount'] ?? '-' }}
                                            @else
                                                <livewire:forecast.forecast-asin-order-amount-input :row-id="$snapshot['id']"
                                                    :order-amount="$snapshot['order_amount']" wire:key="order_amount_{{ $snapshot['id'] }}" />
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
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="promptModal" tabindex="-1" aria-labelledby="promptModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header d-flex align-items-center gap-3">
                    <h5 class="modal-title mb-0" id="promptModalLabel">
                        AI Forecast Prompt
                    </h5>
                    <!-- Copy Icon -->
                    <a href="javascript:void(0)" onclick="copyPrompt()" class="text-primary">
                        <i class="bx bx-copy fs-5"></i>
                    </a>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <pre id="promptText" style="white-space: pre-wrap;">{!! $modalPrompt !!}</pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-rounded" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bulkUploadModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="bulkUploadModalLabel" aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="bulkUploadModalLabel">
                        Bulk ASIN Forecast Update
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form method="POST" action="{{ route('admin.orderforecastasin.bulkUpload') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="order_forecast_id" value="{{ $forecast->id }}">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="bulkFile" class="form-label">
                                Upload file
                            </label>

                            <input type="file" name="file" id="bulkFile" class="form-control"
                                accept=".xlsx,.xls,.csv" required>

                            <small class="text-muted d-block mt-1">
                                Supported formats: .xlsx, .xls, .csv
                            </small>

                            <a href="{{ route('admin.orderforecastasin.exportTemplate') }}"
                                class="d-inline-block mt-1 text-primary text-decoration-underline" download>
                                Download sample Excel
                            </a>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light btn-rounded" data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit" class="btn btn-primary btn-rounded">
                            Upload & Process
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function copyPrompt() {
            const text = document.getElementById('promptText').innerText;

            // HTTPS / localhost
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text)
                    .then(() => showToast('success', '📋 Prompt copied to clipboard!'))
                    .catch(() => fallbackCopy(text));
            }
            // HTTP fallback
            else {
                fallbackCopy(text);
            }
        }

        function copySoldData(data) {
            const text = JSON.stringify(data, null, 2);

            // Preferred method (HTTPS / localhost)
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text)
                    .then(() => showToast('success', 'Sold data copied to clipboard'))
                    .catch(() => fallbackCopy(text));
            } else {
                // HTTP fallback
                fallbackCopy(text);
            }
        }

        function fallbackCopy(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;

            // Prevent scrolling
            textarea.style.position = 'fixed';
            textarea.style.top = '-9999px';
            textarea.style.left = '-9999px';

            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();

            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    showToast('success', 'Sold data copied to clipboard');
                } else {
                    showToast('error', 'Copy not supported');
                }
            } catch (err) {
                console.error(err);
                showToast('error', 'Clipboard not supported');
            }

            document.body.removeChild(textarea);
        }

        function generateAIRecommendation(snapshotId) {
            const $cell = $(`#rec-${snapshotId}`);
            $cell
                .html('<i class="bx bx-hourglass bx-spin font-size-16 align-middle me-2"></i> Generating...')
                .css('cursor', 'default')
                .off('click');

            $.ajax({
                url: "{{ route('admin.orderforecastasin.generateAI') }}",
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
                            url: `{{ route('admin.orderforecastasin.getStatus', '') }}/${snapshotId}`,
                            method: "GET",
                            success: function(res) {
                                if (res?.success && res?.run_update === 1) {
                                    clearInterval(intervalId);
                                    $cell.text('✅ Generated').css('cursor', 'default');

                                    if (!res.forecast || !Array.isArray(res.forecast)) {
                                        showToast('warning',
                                            '⚠️ No forecast data returned.');
                                        return;
                                    }

                                    // Append AI forecast to each month div
                                    res.forecast.forEach(item => {
                                        if (!item?.month) return;

                                        const selector =
                                            `#ai-sold-${snapshotId}-${item.month}`;
                                        const $div = $(selector);

                                        if ($div.length) {
                                            const soldUnits = parseInt($div
                                                .data(
                                                    'sold') || 0);
                                            const aiUnits = parseInt(item.ai ||
                                                0);

                                            let html = `
                                                <strong class="ai-gradient-text">AI FC:</strong>
                                                <span class="fw-bold">${aiUnits}</span>
                                            `;

                                            // Add pulse if AI < sold
                                            if (aiUnits < soldUnits) {
                                                html += `
                                                    <span class="pulse pulse-danger ms-1"
                                                        style="width: 8px; height: 8px; display: inline-block;"
                                                        title="AI forecast is lower than actual sales">
                                                    </span>
                                                `;
                                            }

                                            // Update DOM
                                            $div.html(html);
                                        }
                                    });

                                    showToast('success',
                                        '✅ AI recommendations updated successfully!'
                                    );
                                }
                            },
                            error: function(xhr) {
                                console.error("Polling failed:", xhr.responseText);
                            }
                        });

                        if (attempts >= maxAttempts) {
                            clearInterval(intervalId);
                            $cell.text('✨ Generate AI')
                                .css('cursor', 'pointer')
                                .on('click', () => generateAIRecommendation(snapshotId));
                            showToast('error', '⚠️ AI generation timed out. Please try again.');
                        }
                    }, 5000);
                },
                error: function(xhr) {
                    console.error("Generation request failed:", xhr.responseText);
                    $cell.text('✨ Generate AI')
                        .css('cursor', 'pointer')
                        .on('click', () => generateAIRecommendation(snapshotId));
                    showToast('error', '❌ Failed to generate AI recommendation.');
                }
            });
        }

        $(document).ready(function() {

            const MAX_FILE_SIZE_KB = 5120;
            const ALLOWED_EXTENSIONS = ['xlsx', 'xls'];

            $('form[action="{{ route('admin.orderforecastasin.bulkUpload') }}"]').on('submit', function(e) {

                let fileInput = $('#bulkFile')[0];
                let errorMessage = '';

                // Remove old errors
                $('.file-error').remove();

                if (!fileInput.files.length) {
                    errorMessage = 'Please select a file.';
                } else {
                    let file = fileInput.files[0];
                    let fileSizeKB = file.size / 1024;
                    let fileName = file.name;
                    let fileExtension = fileName.split('.').pop().toLowerCase();

                    if (!ALLOWED_EXTENSIONS.includes(fileExtension)) {
                        errorMessage = 'Invalid file type. Only .xlsx and .xls files are allowed.';
                    } else if (fileSizeKB > MAX_FILE_SIZE_KB) {
                        errorMessage = 'File size must not exceed 5 MB.';
                    }
                }

                if (errorMessage !== '') {
                    e.preventDefault();

                    $('<div class="text-danger mt-1 file-error"></div>')
                        .text(errorMessage)
                        .insertAfter('#bulkFile');
                }
            });

        });
    </script>
@endsection
