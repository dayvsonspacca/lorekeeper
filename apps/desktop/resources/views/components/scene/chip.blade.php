@props(['state'])
<span {{ $attributes->class('lk-chip') }} data-state="{{ $state }}">
    <x-scene.glyph :name="match ($state) {
        'fighting' => 'swords',
        'dead' => 'skull',
        'away' => 'moon',
        'entering' => 'loader',
        'idle' => 'shield',
        default => 'moon',
    }" />
    {{ match ($state) {
        'fighting' => 'Fighting',
        'dead' => 'Fallen',
        'away' => 'Away',
        'entering' => 'Entering',
        'idle' => 'Idle',
        default => 'Offline',
    } }}
</span>
