@php
    $found = collect($scene->backpack->kept)->firstWhere('id', 9392);
    $tokens = collect($scene->backpack->kept)->firstWhere('id', 9397);
    $foundCount = min(2, $found['quantity'] ?? 0);
    $done = $task->status === \Lorekeeper\Apps\Desktop\Bots\TaskStatus::Done;
    $steps = [
        ['Go to terrarium', $done || $foundCount > 0 || $scene->hero['map'] === 'terrarium'],
        ['Hunt two tokens from the Carnivorous Crickets in r2', $done || $foundCount >= 2],
        ['Turn them in and pick up the Token of Collection', $done],
    ];
@endphp
<div class="lk-panel__title">
    <h3><x-scene.glyph name="scroll" /> Tokens of Collection · quest 1316</h3>
    <span class="lk-faint" style="font-size: .72rem;">{{ $task->title() }}</span>
</div>
<div class="lk-pulse__head">
    <div class="lk-pulse__stat">
        <strong style="color: var(--lk-gold);">{{ $foundCount }}/2</strong>
        <span>tokens found</span>
    </div>
    <div class="lk-pulse__stat">
        <strong>{{ number_format($tokens['quantity'] ?? 0) }}</strong>
        <span>Tokens of Collection held</span>
    </div>
    <div class="lk-pulse__stat">
        <strong style="color: var(--lk-bad);">{{ number_format($task->kills) }}</strong>
        <span>crickets defeated</span>
    </div>
</div>
<ol class="lk-steps">
    @foreach ($steps as [$step, $stepDone])
        <li @if ($stepDone) data-done @endif>{{ $step }}</li>
    @endforeach
</ol>
