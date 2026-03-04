@extends('layouts.app')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0 font-size-18">{{ $schedule ? 'Edit Campaign Schedule' : 'Create Campaign Schedule' }}</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item active">
                            <a href="{{ route('admin.ads.schedule.index') }}">
                                <i class="bx bx-left-arrow-alt me-1"></i> Back to Under Schedules
                            </a>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">{{ $schedule ? 'Update Schedule Details' : 'Add New Schedule' }}</h4>
                    <p class="card-title-desc"> The campaign will run at the PDT equivalent of the IST time you select. PDT
                        time is shown below each input.
                    </p>
                    <form
                        action="{{ $schedule ? route('admin.ads.schedule.update', $schedule->id) : route('admin.ads.schedule.store') }}"
                        method="POST"
                        onsubmit="return confirm('Are you sure you want to {{ $schedule ? 'update' : 'create' }} this schedule?');">
                        @csrf
                        @if ($schedule)
                            @method('PUT')
                        @endif
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Day of Week</label>
                                    <select name="day_of_week"
                                        class="form-control @error('day_of_week') is-invalid @enderror">
                                        @foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                            <option value="{{ $day }}"
                                                {{ old('day_of_week', $schedule->day_of_week ?? '') == $day ? 'selected' : '' }}>
                                                {{ $day }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('day_of_week')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Country</label>
                                    <select name="country" class="form-control @error('country') is-invalid @enderror">
                                        <option value="US"
                                            {{ old('country', $schedule->country ?? '') == 'US' ? 'selected' : '' }}>US
                                        </option>
                                          <option value="CA"
                                            {{ old('country', $schedule->country ?? '') == 'CA' ? 'selected' : '' }}>CA
                                        </option>
                                    </select>
                                    @error('country')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Start Time (IST)</label>
                                    <input type="time" id="start_time_ist" name="start_time" class="form-control"
                                        value="{{ old('start_time', isset($schedule) ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i') : '') }}">
                                    @error('start_time')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                    <small class="text-muted">PDT Equivalent: <span id="start_time_pdt">--:--</span></small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">End Time (IST)</label>
                                    <input type="time" id="end_time_ist" name="end_time" class="form-control"
                                        value="{{ old('end_time', isset($schedule) ? \Carbon\Carbon::parse($schedule->end_time)->format('H:i') : '') }}">
                                    @error('end_time')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                    <small class="text-muted">PDT Equivalent: <span id="end_time_pdt">--:--</span></small>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <button class="btn btn-primary" type="submit">{{ $schedule ? 'Update' : 'Create' }}</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const startInput = document.getElementById('start_time_ist');
                const endInput = document.getElementById('end_time_ist');
                const startPDT = document.getElementById('start_time_pdt');
                const endPDT = document.getElementById('end_time_pdt');

                function convertISTtoPDT(istTime) {
                    if (!istTime) return '--:--';
                    const [h, m] = istTime.split(':').map(Number);
                    // IST → PDT is -12.5 hours difference (PDT = IST - 12.5h)
                    let date = new Date();
                    date.setHours(h);
                    date.setMinutes(m);
                    date.setSeconds(0);
                    // Subtract 12.5 hours = 12 hours 30 minutes
                    date.setHours(date.getHours() - 12);
                    date.setMinutes(date.getMinutes() - 30);
                    let hh = date.getHours().toString().padStart(2, '0');
                    let mm = date.getMinutes().toString().padStart(2, '0');
                    return `${hh}:${mm}`;
                }

                function updateTimes() {
                    startPDT.textContent = convertISTtoPDT(startInput.value);
                    endPDT.textContent = convertISTtoPDT(endInput.value);
                }
                // Initial update if old values exist
                updateTimes();
                startInput.addEventListener('input', updateTimes);
                endInput.addEventListener('input', updateTimes);
            });
        </script>
    @endpush
@endsection
