@props(['rank'])
<span {{ $attributes->class('lk-pips') }} title="Rank {{ $rank }}">
    @for ($pip = 1; $pip <= 10; $pip++)
        <i @if ($pip <= $rank) data-on @endif></i>
    @endfor
</span>
