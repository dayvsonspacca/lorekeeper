@php
    $found = collect($scene?->backpack->kept ?? [])->firstWhere('id', 9392);
    $tokens = collect($scene?->backpack->kept ?? [])->firstWhere('id', 9397);
@endphp
<div class="lk-head__line">
    <x-scene.glyph name="swords" /> {{ min(2, $found['quantity'] ?? 0) }}/2 tokens found
    <span class="lk-faint">·</span> {{ number_format($tokens['quantity'] ?? 0) }} Tokens of Collection
</div>
