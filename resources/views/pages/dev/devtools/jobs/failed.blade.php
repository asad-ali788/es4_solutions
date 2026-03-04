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
                                        <th>Connection</th>
                                        <th>Queue</th>
                                        <th>Display Name</th>
                                        <th>Failed At</th>
                                        <th style="width: 300px !important">Exception</th>
                                        <th style="width: 300px !important">Payload / Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (isset($failedJobs) && $failedJobs->count() > 0)
                                        @foreach ($failedJobs as $job)
                                            <tr>
                                                <td>{{ $job->id }}</td>
                                                <td>{{ $job->connection ?? 'N/A' }}</td>
                                                <td>{{ $job->queue ?? 'N/A' }}</td>
                                                <td>{{ $job->displayName }}</td>
                                                <td>{{ \Carbon\Carbon::parse($job->failed_at)->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}
                                                </td>
                                                <td>
                                                    <pre style="white-space: pre-wrap; max-height: 80px; max-width: 500px; overflow-y: auto;">
                                                    {{ $job->exception }}
                                                </pre>
                                                </td>
                                                <td>
                                                    <pre style="white-space: pre-wrap; max-height: 80px;max-width: 500px; overflow-y: auto;">
                                                    {{ json_encode($job->dynamicData, JSON_PRETTY_PRINT) }}
                                                </pre>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="7" class="text-center">No failed jobs found.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>

                            <div class="mt-2">
                                {{ $failedJobs->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
