<div @if ($run->status->isActive()) wire:poll.2s @endif>
    @if ($task === null)
        <div style="color: var(--gray-500, #6b7280); font-size: .875rem;">
            Progress shows up once the bot starts a task.
        </div>
    @else
        <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 1rem; margin-bottom: .75rem;">
            <div>
                <div style="font-weight: 600;">{{ $task->script->getLabel() }}</div>
                <div style="color: var(--gray-500, #6b7280); font-size: .8125rem;">
                    {{ $task->script->summary($task->parameters) }} · {{ $task->status->getLabel() }}
                    @if ($task->duration() !== null) · {{ $task->duration() }} @endif
                </div>
            </div>
            @if ($stats->level() !== null)
                <div style="text-align: right;">
                    <div style="font-size: 1.75rem; font-weight: 700; line-height: 1;">
                        {{ $stats->level() }}@if ($stats->target() !== null)<span style="font-size: 1rem; color: var(--gray-400, #9ca3af); font-weight: 500;"> / {{ $stats->target() }}</span>@endif
                    </div>
                    <div style="color: var(--gray-500, #6b7280); font-size: .75rem;">level</div>
                </div>
            @endif
        </div>

        @if ($task->percent() !== null)
            <div style="display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem;">
                <div style="flex: 1;"><x-progress :percent="$task->percent()" /></div>
                <span style="font-size: .8125rem; color: var(--gray-500, #6b7280); min-width: 3rem; text-align: right;">{{ $task->percent() }}%</span>
            </div>
        @endif

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(8.5rem, 1fr)); gap: .75rem;">
            @foreach ([
                ['Monsters killed', number_format($task->kills), $stats->killsPerMinute() === null ? null : $stats->killsPerMinute() . ' per minute'],
                ['Experience', number_format($task->experience), null],
                ['Gold', number_format($task->gold), null],
                ['Deaths', number_format($task->deaths), null],
                ['Levels gained', $stats->levelsGained(), $stats->minutesPerLevel() === null ? null : $stats->minutesPerLevel() . ' min per level'],
                ['To the target', $stats->levelsLeft() === null ? '—' : $stats->levelsLeft() . ' levels', $stats->minutesToTarget() === null ? null : '≈ ' . $stats->minutesToTarget() . ' min left'],
            ] as [$label, $value, $hint])
                @continue($label === 'To the target' && $stats->target() === null)
                <div style="padding: .75rem .875rem; border-radius: .625rem; border: 1px solid var(--gray-200, #e5e7eb);" class="dark:border-white/10">
                    <div style="color: var(--gray-500, #6b7280); font-size: .75rem;">{{ $label }}</div>
                    <div style="font-size: 1.25rem; font-weight: 600;">{{ $value }}</div>
                    @if ($hint !== null)
                        <div style="color: var(--gray-500, #6b7280); font-size: .75rem;">{{ $hint }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
