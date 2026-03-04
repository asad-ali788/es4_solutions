@extends('layouts.app')
@section('content')
    <!-- start page title -->
    @php
        $subdayTime = now(config('timezone.market'))->subDay()->toDateString();
        $isPastDate = $date !== $subdayTime;
    @endphp
    <div class="row">
        <div class="col-12 d-flex align-items-center justify-content-between">
            <div>
                <h4 class="mb-sm-0 font-size-18">
                    Performance Logs
                    @if ($type !== 'all')
                        - {{ ucfirst($type) }}
                    @endif
                    ({{ $date }})
                </h4>

            </div>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item active">
                        <a
                            href="{{ $type === 'campaign'
                                ? route('admin.ads.performance.capaigns.index')
                                : route('admin.ads.performance.keywords.index') }}">
                            <i class="bx bx-left-arrow-alt me-1"></i> Back to Performance
                        </a>
                    </li>
                </ol>
            </div>
        </div>
    </div>

    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-2 align-items-center">
                        <div class="col-sm-8 d-flex gap-2">
                            <form id="filterForm" method="GET"
                                action="{{ route('admin.ads.performance.showLogs', [$type, $date]) }}"
                                class="d-flex align-items-center gap-2 flex-nowrap">

                                <x-elements.search-box />

                                <div class="form-floating">
                                    <select class="form-select custom-dropdown" name="campaign"
                                        onchange="this.form.submit()" id="floatingSelectCampaign">
                                        <option value="all" {{ ($campaignType ?? 'SP') === null ? 'selected' : '' }}>
                                            All Campaigns
                                        </option>

                                        <option value="SP" {{ ($campaignType ?? 'SP') === 'SP' ? 'selected' : '' }}>
                                            SP
                                        </option>

                                        <option value="SB" {{ ($campaignType ?? 'SP') === 'SB' ? 'selected' : '' }}>
                                            SB
                                        </option>

                                        <option value="SD" {{ ($campaignType ?? 'SP') === 'SD' ? 'selected' : '' }}>
                                            SD
                                        </option>
                                    </select>

                                    <label for="floatingSelectCampaign">Campaign Type</label>
                                </div>

                                <div class="form-floating">
                                    <select class="form-select custom-dropdown" name="type"
                                        onchange="this.form.submit()">
                                        <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All</option>
                                        <option value="campaign" {{ $type === 'campaign' ? 'selected' : '' }}>Campaign
                                        </option>
                                        <option value="keyword" {{ $type === 'keyword' ? 'selected' : '' }}>Keyword
                                        </option>
                                    </select>
                                    <label for="floatingSelectType">Change Type</label>
                                </div>

                                <div class="form-floating">

                                    <input class="form-control" type="date" name="date"
                                        value="{{ request('date', $subdayTime) }}" max="{{ $subdayTime }}"
                                        onchange="document.getElementById('filterForm').submit()"
                                        onclick="this.showPicker()">
                                    <label for="date">Select Date</label>
                                </div>
                            </form>
                        </div>
                        @if (!$isPastDate)
                            <div class="col-12 col-lg-auto ms-lg-auto">
                                <div class="row g-2 justify-content-lg-end">
                                    <div class="col-12 col-sm-auto d-grid d-sm-block">
                                        <form method="POST"
                                            action="{{ route('admin.ads.performance.performanceLogsMakeRevertLive', request()->query()) }}">
                                            @csrf
                                            <input type="hidden" name="date"
                                                value="{{ request('date', $subdayTime) }}">
                                            <input type="hidden" name="type" value="{{ request('type', 'all') }}">

                                            <button class="btn btn-primary btn-rounded waves-effect waves-light">
                                                Revert Changes Live
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Campaign ID</th>
                                    <th>Keyword ID</th>
                                    <th>Target ID</th>
                                    @if ($type == 'all')
                                        <th>Change Type</th>
                                    @endif
                                    <th>Country</th>
                                    <th>Date</th>

                                    @if ($type === 'all')
                                        <th>Old Budget</th>
                                        <th>New Budget</th>
                                        <th>Old Bid</th>
                                        <th>New Bid</th>
                                    @else
                                        <th>{{ $type === 'campaign' ? 'Old Budget' : 'Old Bid' }}</th>
                                        <th>{{ $type === 'campaign' ? 'New Budget' : 'New Bid' }}</th>
                                    @endif
                                    <th>Type</th>
                                    <th>Changed By</th>
                                    <th>Changed At</th>
                                    <th>Reverted At</th>
                                    <th>Reverted By</th>
                                    <th>Revert Summary</th>
                                    @if (!$isPastDate)
                                        <th>
                                            <div class="dropdown" data-bs-auto-close="false">
                                                <button class="btn btn-outline-info btn-sm dropdown-toggle fw-bold"
                                                    type="button" data-bs-toggle="dropdown">
                                                    Apply to All
                                                </button>

                                                <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 220px">
                                                    <form method="POST"
                                                        action="{{ route('admin.ads.performance.runPerformanceLogUpdate', request()->query()) }}">
                                                        @csrf
                                                        <input type="hidden" name="bulk" value="1">

                                                        {{-- Ensure filters are always sent (helps if query string isn't present for any reason) --}}
                                                        <input type="hidden" name="search"
                                                            value="{{ request('search') }}">
                                                        <input type="hidden" name="type"
                                                            value="{{ request('type', 'all') }}">
                                                        <input type="hidden" name="date" value="{{ request('date') }}">

                                                        <div class="mb-2">
                                                            <input class="form-check-input" type="radio" name="run_update"
                                                                value="1" id="bulkCheckAll" required>
                                                            <label class="form-check-label ms-1" for="bulkCheckAll">Check
                                                                All</label>
                                                        </div>

                                                        <div class="mb-3">
                                                            <input class="form-check-input" type="radio" name="run_update"
                                                                value="0" id="bulkUncheckAll" required>
                                                            <label class="form-check-label ms-1"
                                                                for="bulkUncheckAll">Uncheck
                                                                All</label>
                                                        </div>

                                                        <button type="submit" class="btn btn-primary w-100">Apply</button>
                                                    </form>

                                                </div>
                                            </div>
                                        </th>
                                    @endif
                                </tr>
                            </thead>

                            <tbody>
                                @if ($logs->count())
                                    @foreach ($logs as $log)
                                        <tr>
                                            <td>{{ $logs->firstItem() + $loop->index }}</td>
                                            <td>{{ $log['campaign_id'] ?? '--' }}</td>
                                            <td>{{ $log['keyword_id'] ?? '--' }}</td>
                                            <td>{{ $log['target_id'] ?? '--' }}</td>
                                            @if ($type == 'all')
                                                <td>{{ ucfirst($log['change_type'] ?? '--') }}</td>
                                            @endif
                                            <td>{{ $log['country'] ?? '--' }}</td>
                                            <td>{{ \Carbon\Carbon::parse($log['date'])->format('Y-m-d') ?? '--' }}</td>

                                            @if ($type === 'all')
                                                @if ($log['change_type'] === 'campaign')
                                                    <td class="text-danger fw-bold">{{ $log['old_value'] ?? '--' }}</td>
                                                    <td class="text-success fw-bold">{{ $log['new_value'] ?? '--' }}</td>
                                                    <td class="text-muted fw-bold">--</td>
                                                    <td class="text-muted fw-bold">--</td>
                                                @else
                                                    <td class="text-muted fw-bold">--</td>
                                                    <td class="text-muted fw-bold">--</td>
                                                    <td class="text-danger fw-bold">{{ $log['old_value'] ?? '--' }}</td>
                                                    <td class="text-success fw-bold">{{ $log['new_value'] ?? '--' }}</td>
                                                @endif
                                            @else
                                                <td class="text-danger fw-bold">{{ $log['old_value'] ?? '--' }}</td>
                                                <td class="text-success fw-bold">{{ $log['new_value'] ?? '--' }}</td>
                                            @endif
                                            <td>{{ $log['type'] ?? '--' }}</td>
                                            <td>{{ $log['executed_by_name'] ?? '--' }}</td>
                                            <td>{{ $log['executed_at_formatted'] ?? '--' }}</td>
                                            <td>{{ $log['revert_executed_at_formatted'] ?? '--' }}</td>
                                            <td>{{ $log['reverted_by_name'] ?? '--' }}</td>
                                            <td>
                                                @if (($log['run_status'] ?? null) === 'reverted')
                                                    @php
                                                        $changeType = $log['change_type'] ?? $type;
                                                        $oldValue = $log['old_value'] ?? '--';
                                                    @endphp

                                                    @if ($changeType === 'campaign')
                                                        <span class="fw-bold">
                                                            Budget
                                                            <span class="mx-1">↩</span>
                                                            <span class="text-success">{{ $oldValue }}</span>
                                                        </span>
                                                    @elseif ($changeType === 'keyword')
                                                        <span class="fw-bold">
                                                            Bid
                                                            <span class="mx-1">↩</span>
                                                            <span class="text-success">{{ $oldValue }}</span>
                                                        </span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">--</span>
                                                @endif
                                            </td>


                                            @if (!$isPastDate)
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <input type="checkbox" class="form-check-input row-checkbox"
                                                            data-log-id="{{ $log['id'] }}"
                                                            {{ $log['run_update'] ? 'checked' : '' }}
                                                            @if (in_array($log['run_status'], ['dispatched', 'reverted'])) disabled @endif />

                                                        @if ($log['run_update'])
                                                            @switch($log['run_status'])
                                                                @case('pending')
                                                                    <span class="badge bg-info">Pending</span>
                                                                @break

                                                                @case('dispatched')
                                                                    <span class="badge bg-warning text-dark">Batch</span>
                                                                @break

                                                                @case('reverted')
                                                                    <span class="badge bg-success">Reverted</span>
                                                                @break

                                                                @case('failed')
                                                                    <span class="badge bg-danger">Failed</span>
                                                                @break
                                                            @endswitch
                                                        @endif
                                                    </div>
                                                </td>
                                            @endif

                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="{{ $type === 'all' ? 17 : 16 }}"
                                            class="text-center text-danger fw-bold">No logs available for this date</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                        <div class="mt-3">
                            {{ $logs->links('pagination::bootstrap-5') }}
                        </div>
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
    <script>
        $(document).ready(function() {
            const csrf = $('meta[name="csrf-token"]').attr('content');

            $('.row-checkbox').on('change', function() {
                const $el = $(this);
                const logId = $el.data('log-id'); // data-log-id
                const runUpdate = $el.is(':checked') ? 1 : 0;

                if (!logId) {
                    $el.prop('checked', !$el.prop('checked'));
                    showToast('error', 'Missing log id in checkbox.');
                    return;
                }

                $el.prop('disabled', true);

                const formData = new FormData();
                formData.append('_token', csrf);
                formData.append('bulk', '0');
                formData.append('log_id', logId);
                formData.append('run_update', runUpdate);

                $.ajax({
                    url: "{{ route('admin.ads.performance.runPerformanceLogUpdate', request()->query()) }}",
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',

                    success: function(response) {
                        if (!response.success) {
                            $el.prop('checked', !$el.prop('checked'));
                        }

                        showToast(
                            response.success ? 'success' : 'error',
                            response.message || 'Keyword update completed.'
                        );
                    },

                    error: function(xhr) {
                        $el.prop('checked', !$el.prop('checked'));

                        let message = 'Something went wrong.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }

                        showToast('error', message);
                    },

                    complete: function() {
                        $el.prop('disabled', false);
                    }
                });
            });
        });
    </script>

@endsection
