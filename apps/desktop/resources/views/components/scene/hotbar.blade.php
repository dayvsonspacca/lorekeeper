@props(['skills'])
<div {{ $attributes->class('lk-hotbar') }}>
    @foreach ($skills as $index => $skill)
        @php
            $key = match ($skill['slot']) {
                'AutoAttack' => 'A',
                'Potion' => 'P',
                default => (string) $index,
            };
            $glyph = match (true) {
                $skill['slot'] === 'Potion' => 'flask',
                $skill['slot'] === 'AutoAttack' => 'sword',
                $skill['target'] === 'self' => 'heart',
                $skill['target'] === 'ally' => 'shield',
                default => 'zap',
            };
        @endphp
        <div
            class="lk-slot"
            data-slot="{{ $skill['slot'] }}"
            data-target="{{ $skill['target'] }}"
            @unless ($skill['unlocked']) data-locked @endunless
            title="{{ $skill['unlocked'] ? '' : 'Locked · ' }}{{ ucfirst($skill['target']) }}{{ $skill['targets'] > 1 ? ', up to ' . $skill['targets'] . ' targets' : '' }} · {{ ($skill['restores'] ?? 0) > 0 ? 'restores ' . $skill['restores'] . ' mana' : $skill['cost'] . ' mana' }} · {{ $skill['cooldown'] }}s cooldown"
        >
            <span class="lk-slot__key">{{ $key }}</span>
            <x-scene.glyph :name="$glyph" />
            @if (($skill['restores'] ?? 0) > 0)
                <span class="lk-slot__cost" data-restores>+{{ $skill['restores'] }}</span>
            @elseif ($skill['cost'] > 0)
                <span class="lk-slot__cost">{{ $skill['cost'] }}</span>
            @endif
            @if ($skill['targets'] > 1)
                <span class="lk-slot__aoe">×{{ $skill['targets'] }}</span>
            @endif
        </div>
    @endforeach
</div>
