@props(['name', 'level' => null, 'percent' => 0, 'size' => 'md'])
<div {{ $attributes->class(['lk-crest', 'lk-crest--lg' => $size === 'lg']) }} style="--p: {{ $percent }};">
    <span class="lk-crest__face">{{ mb_strtoupper(mb_substr($name, 0, 1)) }}</span>
    @if ($level !== null)
        <span class="lk-crest__level">{{ $level }}</span>
    @endif
</div>
