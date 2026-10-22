@props(['current', 'maximum', 'kind' => 'health', 'compact' => false, 'caption' => null])
@php
    $percent = $maximum > 0 ? max(0, min(100, ($current * 100) / $maximum)) : 0;
@endphp
<div
    {{ $attributes->class(['lk-meter', 'lk-meter--compact' => $compact]) }}
    data-kind="{{ $kind }}"
    @if ($kind === 'health' && $current > 0 && $percent <= 25) data-low @endif
>
    @unless ($compact)
        <div class="lk-meter__label">
            <span>{{ match ($kind) { 'health' => 'HP', 'mana' => 'MP', default => 'XP' } }}</span>
            @if ($caption !== null)
                <span class="lk-num">{{ $caption }}</span>
            @else
                <span class="lk-num">{{ number_format($current) }} <span class="lk-faint">/ {{ number_format($maximum) }}</span></span>
            @endif
        </div>
    @endunless
    <div class="lk-bar" style="--w: {{ round($percent, 2) }}%;" role="meter" aria-valuenow="{{ $current }}" aria-valuemin="0" aria-valuemax="{{ $maximum }}">
        <div class="lk-bar__trail"></div>
        <div class="lk-bar__fill"></div>
    </div>
</div>
