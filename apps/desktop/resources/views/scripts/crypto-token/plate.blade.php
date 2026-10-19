@php
    $ore = collect($scene?->backpack->temporary ?? [])->firstWhere('id', 42658);
    $tokens = collect($scene?->backpack->kept ?? [])->firstWhere('id', 42657);
@endphp
<div class="lk-head__line">
    <x-scene.glyph name="package" /> Metal Ore {{ $ore['quantity'] ?? 0 }}/1
    <span class="lk-faint">·</span> {{ number_format($tokens['quantity'] ?? 0) }} Crypto Tokens
    <span class="lk-faint">·</span> {{ number_format($task->kills) }} Sneevils
</div>
