@php
    $ore = collect($scene->backpack->temporary)->firstWhere('id', 42658);
    $tokens = collect($scene->backpack->kept)->firstWhere('id', 42657);
    $done = $task->status === \Lorekeeper\Apps\Desktop\Bots\TaskStatus::Done;
    $steps = [
        ['Go to boxes', $done || $ore !== null || $scene->hero['map'] === 'boxes'],
        ['Hunt a Metal Ore from the Sneevils in the Closet', $done || $ore !== null],
        ['Turn in the Metal Ore and pick up the Crypto Token', $done],
    ];
@endphp
<div class="lk-panel__title">
    <h3><x-scene.glyph name="scroll" /> Odd's 'n' Ends · quest 6187</h3>
    <span class="lk-faint" style="font-size: .72rem;">{{ $task->title() }}</span>
</div>
<div class="lk-pulse__head">
    <div class="lk-pulse__stat">
        <strong style="color: var(--lk-gold);">{{ $ore['quantity'] ?? 0 }}/1</strong>
        <span>Metal Ore</span>
    </div>
    <div class="lk-pulse__stat">
        <strong>{{ number_format($tokens['quantity'] ?? 0) }}</strong>
        <span>Crypto Tokens held</span>
    </div>
    <div class="lk-pulse__stat">
        <strong style="color: var(--lk-bad);">{{ number_format($task->kills) }}</strong>
        <span>Sneevils defeated</span>
    </div>
</div>
<ol class="lk-steps">
    @foreach ($steps as [$step, $done])
        <li @if ($done) data-done @endif>{{ $step }}</li>
    @endforeach
</ol>
