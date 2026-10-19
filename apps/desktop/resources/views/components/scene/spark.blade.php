@props(['values', 'id'])
@php
    $height = 32;
    $width = 120;
    $peak = max(1, ...$values);
    $last = max(1, count($values) - 1);
    $points = implode(' ', array_map(
        static fn(int $index, int $value): string => round(($index * $width) / $last, 2) . ',' . round($height - 2 - (($value / $peak) * ($height - 6)), 2),
        array_keys($values),
        $values,
    ));
@endphp
<svg {{ $attributes->class('lk-spark') }} viewBox="0 0 {{ $width }} {{ $height }}" preserveAspectRatio="none" aria-hidden="true">
    <defs>
        <linearGradient id="lk-spark-{{ $id }}" x1="0" x2="0" y1="0" y2="1">
            <stop offset="0%" stop-color="currentColor" stop-opacity=".45" />
            <stop offset="100%" stop-color="currentColor" stop-opacity="0" />
        </linearGradient>
    </defs>
    <polygon points="0,{{ $height }} {{ $points }} {{ $width }},{{ $height }}" fill="url(#lk-spark-{{ $id }})" />
    <polyline points="{{ $points }}" fill="none" stroke="currentColor" stroke-width="1.6" vector-effect="non-scaling-stroke" stroke-linejoin="round" />
</svg>
