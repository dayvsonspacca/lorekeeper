@php
    $slain = collect($scene?->backpack->temporary ?? [])->firstWhere('id', 5574);
    $blood = collect($scene?->backpack->kept ?? [])->firstWhere('id', 5586);
@endphp
<div class="lk-head__line">
    <x-scene.glyph name="swords" /> {{ $slain['quantity'] ?? 0 }}/50 Gorillaphants
    <span class="lk-faint">·</span> {{ number_format($blood['quantity'] ?? 0) }}/20 Elders' Blood
</div>
<x-scene.bar :current="min(50, $slain['quantity'] ?? 0)" :maximum="50" kind="experience" compact />
