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
                        {{-- Backup list --}}
                        <p class="text-muted mb-0">
                            A new backup is generated every Sunday, and older backups are deleted. Only the backups from the
                            last two weeks are kept. </p>
                        @if ($files->isEmpty())
                            <div class="text-center py-5">
                                <i class="bx bx-data fs-1 text-muted mb-3 d-block"></i>
                                <p class="text-muted mb-0">
                                    No backups found in <code>storage/app/my-backups/database-backup</code>.
                                </p>
                            </div>
                        @else
                            @foreach ($files as $backup)
                                <div
                                    class="d-flex align-items-center justify-content-between p-3 mb-3 rounded-3 border bg-light">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3 d-flex align-items-center justify-content-center rounded-circle bg-success-subtle"
                                            style="width: 44px; height: 44px;">
                                            <i class="bx bx-data fs-4"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">
                                                DB Backup – {{ $backup['date']->format('d M Y H:i') }}
                                            </h6>
                                            <small class="text-muted d-block">
                                                {{ $backup['file'] }}
                                            </small>
                                            <small class="text-muted">
                                                Size: {{ number_format($backup['size'] / 1024 / 1024, 2) }} MB
                                            </small>
                                        </div>
                                    </div>

                                    <a href="{{ route('dev.backups.download', $backup['file']) }}"
                                        class="btn btn-outline-secondary btn-sm" title="Download">
                                        <i class="bx bx-cloud-download fs-5 me-1"></i>
                                        Download
                                    </a>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
