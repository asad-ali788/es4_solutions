@props(['value' => 0])

@php
    $percentage = max(0, min(100, $value)); // clamp 0–100

    // Auto-color logic
    if ($percentage < 20) {
        $color = 'bg-danger'; // red (very low)
    } elseif ($percentage < 55) {
        $color = 'bg-warning'; // yellow
    } elseif ($percentage < 70) {
        $color = 'bg-info'; // light blue
    } elseif ($percentage < 85) {
        $color = 'bg-primary'; // blue
    } else {
        $color = 'bg-success'; // green (high)
    }
@endphp

<div class="d-flex align-items-center gap-1">
    <div class="progress progress-sm flex-grow-1 bg-success-subtle">
        <div class="progress-bar {{ $color }} progress-boost" role="progressbar"
            style="width: {{ $percentage }}%;" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
        </div>
    </div>

    <small class="small fw-bold text-success">
        {{ $percentage }}%
    </small>
</div>
