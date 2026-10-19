<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(8.5rem, 1fr)); gap: .75rem;">
    @foreach ([
        ['Result', $task->status === \Lorekeeper\Apps\Desktop\Bots\TaskStatus::Done ? "Elders' Blood" : $task->status->getLabel()],
        ['Gorillaphants slain', number_format($task->kills)],
        ['Experience', number_format($task->experience)],
        ['Deaths', number_format($task->deaths)],
        ['Took', $task->duration() ?? '—'],
    ] as [$label, $value])
        <div style="padding: .75rem .875rem; border-radius: .625rem; border: 1px solid var(--gray-200, #e5e7eb);" class="dark:border-white/10">
            <div style="color: var(--gray-500, #6b7280); font-size: .75rem;">{{ $label }}</div>
            <div style="font-size: 1.25rem; font-weight: 600;">{{ $value }}</div>
        </div>
    @endforeach
</div>
@if ($task->result !== null)
    <div style="margin-top: .75rem; color: var(--gray-500, #6b7280); font-size: .8125rem;">{{ $task->result }}</div>
@endif
