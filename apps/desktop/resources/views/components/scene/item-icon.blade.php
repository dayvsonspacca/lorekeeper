@props(['kind', 'bare' => false])
@php
    $glyph = match ($kind) {
        'class' => 'crown',
        'armor' => 'shirt',
        'helm' => 'shield',
        'cape' => 'flag',
        'weapon' => 'sword',
        'pet' => 'paw',
        'necklace' => 'gem',
        'ground' => 'pin',
        'house' => 'house',
        'boost' => 'zap',
        'enhancement' => 'sparkles',
        default => 'package',
    };
@endphp
@if ($bare)
    <x-scene.glyph :name="$glyph" />
@else
    <span {{ $attributes->class('lk-item__icon') }} data-kind="{{ $kind }}"><x-scene.glyph :name="$glyph" /></span>
@endif
