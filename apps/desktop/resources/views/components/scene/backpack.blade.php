@props(['backpack'])
@php
    $glyph = static fn (string $kind): string => match ($kind) {
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
<div {{ $attributes->class('lk-backpack') }} x-data="{ kind: 'all', search: '' }">
    <div class="lk-backpack__tools">
        <div class="lk-tabs" role="tablist">
            <button type="button" class="lk-tab" x-on:click="kind = 'all'" x-bind:aria-selected="kind === 'all'">
                All <span class="lk-tab__count">{{ count($backpack->kept) }}</span>
            </button>
            @foreach ($backpack->kinds() as $kind => $count)
                <button type="button" class="lk-tab" wire:key="kind-{{ $kind }}" x-on:click="kind = @js($kind)" x-bind:aria-selected="kind === @js($kind)">
                    <x-scene.glyph :name="$glyph($kind)" /> {{ $kind }} <span class="lk-tab__count">{{ $count }}</span>
                </button>
            @endforeach
        </div>
        <label class="lk-search">
            <x-scene.glyph name="search" />
            <input type="search" x-model="search" placeholder="Find an item" />
        </label>
    </div>

    @if ($backpack->temporary !== [])
        <div class="lk-backpack__label">Held for now <span class="lk-faint">· quest items, gone when the bot leaves</span></div>
        <div class="lk-bag">
            @foreach ($backpack->temporary as $held)
                <div class="lk-item" data-temporary wire:key="temp-{{ $held['id'] }}" x-show="search === '' || @js(mb_strtolower($held['name'])).includes(search.toLowerCase())">
                    <span class="lk-item__icon" data-kind="{{ $held['kind'] }}"><x-scene.glyph :name="$glyph($held['kind'])" /></span>
                    <span class="lk-item__name" title="{{ $held['name'] }}">{{ $held['name'] }}<small>{{ $held['type'] }}</small></span>
                    <span class="lk-item__qty">×{{ number_format($held['quantity']) }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <div class="lk-backpack__label">Kept</div>
    @if ($backpack->kept === [])
        <div class="lk-dim" style="font-size: .8rem;">Nothing yet. The inventory shows up once the bot is in the game.</div>
    @else
        <div class="lk-bag">
            @foreach ($backpack->kept as $held)
                <div
                    class="lk-item"
                    wire:key="item-{{ $held['id'] }}"
                    x-show="(kind === 'all' || kind === @js($held['kind'])) && (search === '' || @js(mb_strtolower($held['name'])).includes(search.toLowerCase()))"
                >
                    <span class="lk-item__icon" data-kind="{{ $held['kind'] }}"><x-scene.glyph :name="$glyph($held['kind'])" /></span>
                    <span class="lk-item__name" title="{{ $held['name'] }}">{{ $held['name'] }}<small>{{ $held['type'] }}</small></span>
                    @if ($held['member'])
                        <span class="lk-item__member" title="Members only">M</span>
                    @endif
                    @if ($held['quantity'] > 1 && $held['kind'] !== 'class')
                        <span class="lk-item__qty">×{{ number_format($held['quantity']) }}</span>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
