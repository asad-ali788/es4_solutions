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
                        <div>
                            {{-- Timezone selector --}}
                            <form method="GET" action="{{ route('dev.schedule.index') }}" class="row mb-3">
                                <div class="col-md-2">
                                    <div class="form-floating">
                                        <select class="form-select" id="timezone-select" name="timezone"
                                            onchange="this.form.submit()">
                                            @foreach ($timezones as $tzValue => $label)
                                                <option value="{{ $tzValue }}"
                                                    {{ $timezone === $tzValue ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <label for="timezone-select">Display Timezone</label>
                                    </div>
                                </div>
                            </form>

                            <p class="text-muted mb-3">
                                Times shown below are in:
                                <strong>{{ $timezones[$timezone] ?? $timezone }}</strong>
                            </p>

                            {{-- Table --}}
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered align-middle" id="schedule-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Command</th>
                                            <th>Description</th>
                                            <th>Cron</th>
                                            <th>Next Due</th>
                                            <th>Next Due</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($tasks as $index => $task)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td><code>{{ $task['command'] }}</code></td>
                                                <td>{{ $task['description'] ?? '—' }}</td>
                                                <td><code>{{ $task['expression'] }}</code></td>
                                                <td>{{ $task['next_due_date'] ?? '—' }}</td>
                                                <td>{{ $task['next_due_date_human'] ?? '—' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">No scheduled tasks found.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
