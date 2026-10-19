@php
    $slain = collect($scene->backpack->temporary)->firstWhere('id', 5574);
    $blood = collect($scene->backpack->kept)->firstWhere('id', 5586);
    $slainCount = min(50, $slain['quantity'] ?? 0);
    $steps = [
        ['Go to arcangrove', $scene->hero['map'] === 'arcangrove' || $slain !== null],
        ['Slay 50 Gorillaphants in LeftBack', $slainCount >= 50],
        ["Turn them in and pick up the Elders' Blood", $task->status === \Lorekeeper\Apps\Desktop\Bots\TaskStatus::Done],
    ];
@endphp
<div class="lk-panel__title">
    <h3><x-scene.glyph name="scroll" /> Experiment 107 · quest 802</h3>
    <span class="lk-faint" style="font-size: .72rem;">{{ $task->title() }}</span>
</div>
<div class="lk-pulse__head">
    <div class="lk-pulse__stat">
        <strong style="color: var(--lk-gold);">{{ $slainCount }}/50</strong>
        <span>Gorillaphants slain</span>
    </div>
    <div class="lk-pulse__stat">
        <strong>{{ number_format($blood['quantity'] ?? 0) }}/20</strong>
        <span>Elders' Blood held</span>
    </div>
    <div class="lk-pulse__stat">
        <strong style="color: var(--lk-bad);">{{ number_format($task->deaths) }}</strong>
        <span>deaths</span>
    </div>
</div>
<div style="margin-top: .9rem;">
    <x-scene.bar :current="$slainCount" :maximum="50" kind="experience" />
</div>
<ol class="lk-steps">
    @foreach ($steps as [$step, $done])
        <li @if ($done) data-done @endif>{{ $step }}</li>
    @endforeach
</ol>
