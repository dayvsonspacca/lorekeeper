@php
    $experience = $scene->pulse->experience($now);
    $kills = $scene->pulse->kills($now);
    $peak = max(1, ...$experience);
@endphp
<div class="lk-panel__title">
    <h3><x-scene.glyph name="activity" /> Pace · last 30 minutes</h3>
    <span class="lk-faint" style="font-size: .72rem;">{{ $task->title() }}</span>
</div>
<div class="lk-pulse__head">
    <div class="lk-pulse__stat">
        <strong style="color: var(--lk-xp);">{{ number_format($scene->pulse->experiencePerHour($now)) }}</strong>
        <span>XP per hour</span>
    </div>
    <div class="lk-pulse__stat">
        <strong>{{ number_format(array_sum($experience)) }}</strong>
        <span>XP earned</span>
    </div>
    <div class="lk-pulse__stat">
        <strong style="color: var(--lk-bad);">{{ number_format(array_sum($kills)) }}</strong>
        <span>kills</span>
    </div>
    <div class="lk-pulse__stat">
        <strong>{{ $stats->minutesToTarget() === null ? '—' : '≈ ' . $stats->minutesToTarget() . ' min' }}</strong>
        <span>to level {{ $stats->target() }}</span>
    </div>
</div>
<div class="lk-chart">
    @foreach ($experience as $minute => $earned)
        <div class="lk-chart__bar" title="{{ \Lorekeeper\Apps\Desktop\Bots\Scene\Pulse::MINUTES - 1 - $minute }} min ago · {{ number_format($earned) }} XP · {{ $kills[$minute] }} kills">
            <i style="--v: {{ round(($earned * 100) / $peak, 1) }}%;"></i>
        </div>
    @endforeach
</div>
<div class="lk-chart__axis"><span>30 min ago</span><span>now</span></div>
