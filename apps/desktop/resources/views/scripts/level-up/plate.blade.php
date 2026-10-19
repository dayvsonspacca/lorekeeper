@php
    $experience = $scene?->pulse->experience($now) ?? [];
@endphp
<div class="lk-head__line">
    <x-scene.glyph name="swords" /> {{ number_format($task->kills) }} kills
    <span class="lk-faint">·</span> {{ number_format($task->experience) }} XP
    @if ($stats->minutesToTarget() !== null)
        <span class="lk-faint">·</span> ≈ {{ $stats->minutesToTarget() }} min to level {{ $stats->target() }}
    @endif
</div>
@if (array_sum($experience) === 0)
    <div class="lk-calm"><x-scene.glyph name="moon" /> No experience in the last 30 minutes</div>
@else
    <div class="lk-pace">
        <div class="lk-pace__graph">
            <x-scene.spark :values="$experience" :id="$task->id" />
        </div>
        <div class="lk-pace__value">
            <strong>{{ number_format($scene->pulse->experiencePerHour($now)) }}</strong>
            <span>XP / hour</span>
        </div>
    </div>
@endif
