<div style="display: flex; align-items: baseline; justify-content: space-between; gap: 1rem; margin-bottom: .75rem;">
    <div style="color: var(--gray-500, #6b7280); font-size: .8125rem;">Report</div>
    @if ($stats->level() !== null)
        <div>
            <span style="font-size: 1.5rem; font-weight: 700;">{{ $stats->level() }}</span>
            <span style="color: var(--gray-400, #9ca3af);">/ {{ $stats->target() }}</span>
            <span style="color: var(--gray-500, #6b7280); font-size: .75rem;">level</span>
        </div>
    @endif
</div>
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(8.5rem, 1fr)); gap: .75rem;">
    @foreach ([
        ['Monsters killed', number_format($task->kills), $stats->killsPerMinute() === null ? null : $stats->killsPerMinute() . ' per minute'],
        ['Experience', number_format($task->experience), null],
        ['Gold', number_format($task->gold), null],
        ['Deaths', number_format($task->deaths), null],
        ['Levels gained', $stats->levelsGained(), $stats->minutesPerLevel() === null ? null : $stats->minutesPerLevel() . ' min per level'],
        ['To the target', $stats->levelsLeft() === null ? '—' : $stats->levelsLeft() . ' levels', $task->status->isActive() && $stats->minutesToTarget() !== null ? '≈ ' . $stats->minutesToTarget() . ' min left' : null],
    ] as [$label, $value, $hint])
        <div style="padding: .75rem .875rem; border-radius: .625rem; border: 1px solid var(--gray-200, #e5e7eb);" class="dark:border-white/10">
            <div style="color: var(--gray-500, #6b7280); font-size: .75rem;">{{ $label }}</div>
            <div style="font-size: 1.25rem; font-weight: 600;">{{ $value }}</div>
            @if ($hint !== null)
                <div style="color: var(--gray-500, #6b7280); font-size: .75rem;">{{ $hint }}</div>
            @endif
        </div>
    @endforeach
</div>
