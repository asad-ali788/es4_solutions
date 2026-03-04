@props(['filters' => []])

@if (!empty($filters))
    <div class="d-flex flex-wrap gap-2">
        @foreach ($filters as $key => $value)
            @continue($value === '' || $value === null)
            <span class="badge bg-light text-dark" title="{{ $key }}: {{ $value }}">
                {{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $value }}
                <a class="ms-1 text-decoration-none" href="{{ request()->fullUrlWithQuery([$key => null]) }}"
                    aria-label="Clear {{ $key }} filter">&times;</a>
            </span>
        @endforeach
    </div>
@endif
<!-- Budget Recommendation Filter -->
@php
    // Normalize all query values so arrays become comma-separated strings
    $safeQuery = collect(request()->query())
        ->map(fn($v) => is_array($v) ? implode(',', $v) : (string) $v)
        ->all();
@endphp

<x-elements.active-filters :filters="$safeQuery" />
