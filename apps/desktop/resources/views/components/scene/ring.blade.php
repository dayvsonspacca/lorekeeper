@props(['percent', 'size' => 46])
<svg {{ $attributes->class('lk-ring') }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 36 36" role="img" aria-label="{{ $percent }}%">
    <circle class="lk-ring__track" cx="18" cy="18" r="15.5" />
    <circle class="lk-ring__value" cx="18" cy="18" r="15.5" stroke-dasharray="{{ round(($percent * 97.39) / 100, 2) }} 97.39" transform="rotate(-90 18 18)" />
    <text x="18" y="20.5" text-anchor="middle">{{ $percent }}%</text>
</svg>
