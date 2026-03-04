@extends('layouts.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">Developer Tools</h4>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            @include('pages.dev.devtools.index')
            <div class="card">
                @include('pages.dev.devtools.nav')
                <div class="card-body">
                    <div class="row g-3 align-items-center justify-content-between mb-3">
                        <div class="table-responsive">
                            <table class="table align-middle table-nowrap dt-responsive nowrap w-100 table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Queue</th>
                                        <th>Display Name</th>
                                        <th style="width: 300px !important">Payload / Data</th>
                                        <th>Attempts</th>
                                        <th>Created At</th>
                                        <th>Reserved At</th>
                                        <th>Available At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (isset($jobs) && $jobs->count() > 0)
                                        @foreach ($jobs as $job)
                                            <tr class="{{ $job->isRunning ? 'table-warning' : '' }}">
                                                <td>{{ $job->id }}</td>
                                                <td>{{ $job->queue ?? 'N/A' }}</td>
                                                <td>{{ $job->displayName }}</td>
                                                <td style="width: 300px">
                                                    <pre style="white-space: pre-wrap; max-height: 80px; max-width: 500px; overflow-y: auto;">
                                                    {{ json_encode($job->dynamicData, JSON_PRETTY_PRINT) }}
                                                    </pre>
                                                </td>
                                                <td>{{ $job->attempts }}</td>
                                                <td>{{ \Carbon\Carbon::parse($job->created_at)->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}
                                                </td>
                                                <td>
                                                    {{ $job->reserved_at
                                                        ? \Carbon\Carbon::parse($job->reserved_at)->timezone(config('app.timezone'))->format('Y-m-d H:i:s')
                                                        : '-' }}
                                                </td>
                                                <td>
                                                    {{ $job->available_at
                                                        ? \Carbon\Carbon::parse($job->available_at)->timezone(config('app.timezone'))->format('Y-m-d H:i:s')
                                                        : '-' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="8" class="text-center">No running jobs found.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>

                            <div class="mb-2">
                                <span class="badge bg-warning text-dark">
                                    Highlighted rows indicate currently running jobs
                                </span>
                            </div>

                            <div class="mt-2">
                                {{ $jobs->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
