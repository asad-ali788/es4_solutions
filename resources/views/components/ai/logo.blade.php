@props([
    'width' => '34',
    'height' => '34',
])

<svg width="{{ $width }}" height="{{ $height }}" viewBox="0 0 128 128" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
    <defs>
        <linearGradient id="ai-logo-grad" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#8A5BFF" />
            <stop offset="100%" stop-color="#FF3FA4" />
        </linearGradient>
    </defs>
    <path d="M64 28 L70 56 L98 62 L70 68 L64 96 L58 68 L30 62 L58 56 Z" fill="none"
        stroke="url(#ai-logo-grad)" stroke-width="8" stroke-linejoin="round" stroke-linecap="round" />
    <g stroke="url(#ai-logo-grad)" stroke-width="8" stroke-linecap="round">
        <line x1="92" y1="36" x2="92" y2="48" />
        <line x1="86" y1="42" x2="98" y2="42" />
    </g>
    <circle cx="34" cy="86" r="6" fill="none" stroke="url(#ai-logo-grad)" stroke-width="8" />
</svg>
