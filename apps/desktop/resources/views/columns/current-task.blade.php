@php
    $run = $getState() instanceof \Lorekeeper\Apps\Desktop\Models\BotRun ? $getState() : $getRecord();
    $task = $run instanceof \Lorekeeper\Apps\Desktop\Models\BotRun && $run->status->isActive() ? $run->currentTask() : null;
    $percent = $task?->percent();
@endphp
<div style="min-width: 12rem; padding: .25rem 0;" class="fi-ta-text">
    @if ($task !== null)
        <div style="font-size: .8125rem; font-weight: 500; margin-bottom: .25rem;">
            {{ $task->script->getLabel() }}
            <span style="color: var(--gray-500, #6b7280); font-weight: 400;">· {{ $task->script->summary($task->parameters) }}</span>
        </div>
        @if ($percent !== null)
            <div style="display: flex; align-items: center; gap: .5rem;">
                <x-progress :percent="$percent" />
                <span style="font-size: .75rem; color: var(--gray-500, #6b7280); min-width: 2.5rem; text-align: right;">{{ $percent }}%</span>
            </div>
        @endif
    @elseif ($run instanceof \Lorekeeper\Apps\Desktop\Models\BotRun && $run->status->isActive())
        <span style="font-size: .8125rem; color: var(--gray-500, #6b7280);">Waiting for tasks</span>
    @else
        <span style="font-size: .8125rem; color: var(--gray-400, #9ca3af);">—</span>
    @endif
</div>
