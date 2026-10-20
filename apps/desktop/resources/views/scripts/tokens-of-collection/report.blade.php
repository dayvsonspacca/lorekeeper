<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(8.5rem, 1fr)); gap: .75rem;">
    @foreach ([
        ['Result', $task->status === \Lorekeeper\Apps\Desktop\Bots\TaskStatus::Done ? 'Token of Collection' : $task->status->getLabel()],
        ['Crickets defeated', number_format($task->kills)],
        ['Deaths', number_format($task->deaths)],
        ['Took', $task->duration() ?? '—'],
    ] as [$label, $value])
        <div style="padding: .75rem .875rem; border-radius: .625rem; border: 1px solid var(--gray-800);">
            <div style="color: var(--gray-500, #6b7280); font-size: .75rem;">{{ $label }}</div>
            <div style="font-size: 1.25rem; font-weight: 600;">{{ $value }}</div>
        </div>
    @endforeach
</div>
