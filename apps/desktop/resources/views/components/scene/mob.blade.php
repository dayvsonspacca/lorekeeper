@props(['monster', 'now'])
@php
    $struck = $monster['struck'] !== null && ($now - $monster['struck']) <= 2 && $monster['state'] !== 'dead';
@endphp
<div
    {{ $attributes->class('lk-mob') }}
    style="--race: {{ \Lorekeeper\Apps\Desktop\Bots\Scene\Palette::race($monster['race']) }};"
    data-state="{{ $monster['state'] }}"
    @if ($struck) data-struck @endif
    title="{{ ucfirst($monster['race']) }}"
>
    <div class="lk-mob__top">
        <span class="lk-mob__name">{{ $monster['name'] }}</span>
        @if ($monster['state'] === 'dead')
            <x-scene.glyph name="skull" class="lk-mob__skull" />
        @else
            <span class="lk-mob__level">Lv {{ $monster['level'] }}</span>
        @endif
    </div>
    <x-scene.bar :current="$monster['health']" :maximum="$monster['maxHealth']" compact />
    <div class="lk-mob__hp">
        <span>{{ $monster['state'] === 'dead' ? 'Respawning…' : number_format($monster['health']) . ' HP' }}</span>
        <span>{{ ucfirst($monster['race']) }}</span>
    </div>
</div>
