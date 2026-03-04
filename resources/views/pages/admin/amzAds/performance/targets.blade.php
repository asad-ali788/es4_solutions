@extends('layouts.app')
@section('content')
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Amazon Ads - Target Performance Recommendations</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                @include('pages.admin.amzAds.performance_nav')
                <div class="card-body pt-2">
                    <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap">
                        <!-- Left side: filters + info -->
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <form method="GET" action="{{ route('admin.ads.performance.targets.index') }}"
                                class="d-flex align-items-center gap-2 flex-wrap" id="filterForm">
                                <!-- Search -->
                                <x-elements.search-box />
                                <!-- Country Filter -->
                                <x-elements.country-select :countries="['us' => 'US', 'ca' => 'CA']" />
                                <!-- Campaign Select-->
                                <x-elements.campaign-select :campaigns="['SP' => 'SP', 'SB' => 'SB', 'SD' => 'SD']" />
                                <!-- Date Filter -->
                                <div class="form-floating">
                                    @php
                                        $subdayTime = now(config('timezone.market'))->subDay()->toDateString();
                                    @endphp
                                    <input class="form-control" type="date" name="date"
                                        value="{{ request('date', $subdayTime) }}" max="{{ $subdayTime }}"
                                        onchange="document.getElementById('filterForm').submit()">
                                    <label for="date">Select Date</label>
                                </div>
                            </form>
                            <!-- Info button -->
                            <button type="button" id="viewRulesBtn" data-bs-toggle="modal"
                                data-bs-target="#bs-example-modal-lg"
                                class="btn btn-light position-relative p-0 avatar-xs rounded-circle">
                                <span class="avatar-title bg-transparent text-reset">
                                    <i class="bx bx-info-circle"></i>
                                </span>
                            </button>
                        </div>
                        @can('amazon-ads.target-performance.export')
                            <!-- Right side: Excel Export -->
                            <div>
                                <a onclick="return confirm('Are you sure you want to download Target Performance for the selected date?');"
                                    href="{{ route('admin.ads.performance.targets.export', ['date' => request('date', now(config('timezone.market'))->subDay()->toDateString())]) }}">
                                    <button type="button" class="btn btn-success btn-rounded waves-effect waves-light">
                                        <i class="mdi mdi-file-excel label-icon"></i> Excel Export
                                    </button>
                                </a>
                            </div>
                        @endcan

                    </div>
                    <div class="table-responsive custom-sticky-wrapper">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 custom-sticky-table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Target Id</th>
                                    <th>Target Name</th>
                                    <th>Campaign Id</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Country</th>
                                    <th>Click</th>
                                    <th>Impressions</th>
                                    <th>Cost</th>
                                    <th>Sales</th>
                                    <th>Orders</th>
                                    <th>ACoS (%)</th>
                                    <th class="wide-col">
                                        <span class="ai-gradient-text">AI Recommendation ✨</span>
                                    </th>
                                    <th class="wide-col">
                                        <span class="ai-gradient-text">AI Suggested Bid ✨</span>
                                    </th>
                                    <th class="wide-col">Suggested Bid</th>
                                    <th class="wide-col">Recommendation</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($targets->count() > 0)
                                    @foreach ($targets as $index => $target)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $target->targeting_id ?? 'N/A' }}</td>
                                            <td>{{ $target->targeting_text ?? 'N/A' }}</td>
                                            <td>{{ $target->campaign_id ?? 'N/A' }}</td>
                                            <td>{{ $target->campaign_types ?? 'N/A' }}</td>
                                            <td>{{ $target->date ?? 'N/A' }}</td>
                                            <td>{{ $target->country ?? 'N/A' }}</td>
                                            <td>{{ $target->clicks ?? 'N/A' }}</td>
                                            <td>{{ $target->impressions ?? 'N/A' }}</td>
                                            <td>${{ number_format($target->spend, 2) ?? 'N/A' }}</td>
                                            <td>${{ number_format($target->sales, 2) ?? 'N/A' }}</td>
                                            <td>{{ $target->orders ?? 'N/A' }}</td>
                                            <td>{{ $target->acos ?? 'N/A' }}%</td>
                                            <td id="rec-{{ $target->id }}" class="ai-col td-break-col"
                                                @if ($target->ai_status != 'done') onclick="generateRecommendation({{ $target->id }})" style="cursor:pointer;" @endif>
                                                {{ $target->ai_recommendation ?? '✨Ai Generate' }}
                                            </td>

                                            <td id="bid-{{ $target->id }}">
                                                @if (is_numeric($target->ai_suggested_bid))
                                                    ${{ number_format($target->ai_suggested_bid, 2) }}
                                                @else
                                                    {{ $target->ai_suggested_bid ?? '--' }}
                                                @endif
                                            </td>
                                            <td class="td-break-col">{{ $target->suggested_bid ?? 'N/A' }}</td>
                                            <td class="td-break-col">{{ $target->recommendation ?? 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="100%" class="text-center">No data item available for this</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                        <!-- end table -->
                    </div>
                    <div class="mt-2">
                        {{ $targets->appends(request()->query())->links('pagination::bootstrap-5') }}
                    </div>
                    <!-- end table responsive -->
                </div>
                <!-- end card body -->
            </div>
            <!-- end card -->
        </div>
        <!-- end col -->
    </div>
    <!-- end row -->

    <!-- Modal -->
    <div class="modal fade" id="bs-example-modal-lg" tabindex="-1" aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myLargeModalLabel">Target Optimization Recommendations</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mt-3" role="alert">
                        <strong>⚠️ Disclaimer:</strong> AI-generated recommendations may not always be accurate or suitable
                        for every case. Please review carefully before applying changes.
                    </div>

                    <div class="alert alert-info" role="alert">
                        <strong>✨ Tip:</strong> Click <b>✨ Ai Generate</b> and wait a few seconds to receive a
                        recommendation.
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">Rule based Recommendation</h5>

                        <a href="{{ route('admin.ads.performance.rules.target.index') }}">
                            <button type="button" class="btn btn-success btn-sm btn-rounded waves-effect waves-light">
                                <i class="mdi mdi-pencil label-icon"></i> Edit Target Rules
                            </button>
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead class="table-sm">
                                <tr>
                                    <th style="width: 40%;">Condition</th>
                                    <th>Recommendation</th>
                                </tr>
                            </thead>
                            <tbody id="rules-table-body">
                                <tr>
                                    <td colspan="2" class="text-center text-muted">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            document.getElementById('bs-example-modal-lg')
                .addEventListener('show.bs.modal', function() {
                    const tbody = document.getElementById('rules-table-body');
                    tbody.innerHTML = `<tr><td colspan="2" class="text-center text-muted">Loading...</td></tr>`;

                    fetch("{{ route('admin.ads.performance.rules.target.partials') }}")
                        .then(response => response.json())
                        .then(data => {
                            if (data.length === 0) {
                                tbody.innerHTML =
                                    `<tr><td colspan="2" class="text-center text-muted">No rules defined yet.</td></tr>`;
                            } else {
                                tbody.innerHTML = data.map(rule => `
                        <tr>
                            <td>${rule.condition}</td>
                            <td>${rule.recommendation}</td>
                        </tr>
                    `).join('');
                            }
                        })
                        .catch(() => {
                            tbody.innerHTML =
                                `<tr><td colspan="2" class="text-danger text-center">⚠️ Failed to load rules.</td></tr>`;
                        });
                });
        </script>
        <script>
            function setClickable(id, clickable) {
                const cell = $("#rec-" + id);
                cell.css({
                    "cursor": clickable ? "pointer" : "default",
                    "color": clickable ? "blue" : "inherit",
                    "text-decoration": clickable ? "underline" : "none"
                });
                if (clickable) {
                    cell.off("click").on("click", () => generateRecommendation(id));
                } else {
                    cell.off("click");
                }
            }

            function generateRecommendation(id) {
                const cell = $("#rec-" + id);

                setClickable(id, false);
                cell.text("⏳ Generating...");

                $.post("{{ route('admin.ads.performance.recommendation.targetgenerate', ':id') }}".replace(':id', id), {
                        _token: "{{ csrf_token() }}"
                    })
                    .done(() => {
                        pollStatus(id);
                    })
                    .fail((err) => {
                        console.error("❌ Failed to start generation:", err);
                        cell.text("⚠️ Error while generating");
                        setClickable(id, true);
                    });
            }

            function pollStatus(id, attempt = 1) {
                const recCell = $("#rec-" + id);
                const bidCell = $("#bid-" + id);

                $.get("{{ route('admin.ads.performance.recommendation.poll.targetStatus', ':id') }}".replace(':id', id))
                    .done((data) => {
                        if (data.ai_status === 'done') {
                            if (data.ai_recommendation) {
                                recCell.text(data.ai_recommendation);
                            }
                            if (typeof data.ai_suggested_bid !== "undefined" && data.ai_suggested_bid !== null) {
                                if ($.isNumeric(data.ai_suggested_bid)) {
                                    bidCell.text(`$${parseFloat(data.ai_suggested_bid).toFixed(2)}`);
                                } else {
                                    bidCell.text(data.ai_suggested_bid);
                                }
                            }
                            recCell.css({
                                cursor: "default",
                                color: "inherit",
                                "text-decoration": "none",
                                "pointer-events": "none"
                            }).off("click");
                        } else if (data.ai_status === 'failed') {
                            recCell.text("⚠️ Failed, retry after few seconds");
                            setClickable(id, true);
                        } else if (attempt < 20) {
                            setTimeout(() => pollStatus(id, attempt + 1), 2000);
                        } else {
                            recCell.text("⚠️ Timeout, click to retry");
                            setClickable(id, true);
                        }
                    })
                    .fail((err) => {
                        console.error("❌ Polling error for ID " + id + ":", err);
                    });
            }
        </script>
    @endpush
@endsection
