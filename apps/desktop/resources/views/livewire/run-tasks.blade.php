<div @if ($run->status->isActive()) wire:poll.1s @endif style="display: flex; flex-direction: column; gap: .5rem;">
    @forelse ($tasks as $task)
        <div
            wire:key="task-{{ $task->id }}"
            style="padding: .625rem .875rem; border-radius: .625rem; border: 1px solid var(--gray-200, #e5e7eb);"
        >
        <div style="display: flex; align-items: center; gap: 1rem;">
            <span style="color: var(--gray-400, #9ca3af); font-size: .75rem; min-width: 2.5rem;">#{{ $task->id }}</span>
            <x-filament::badge :color="$task->status->getColor()" :icon="$task->status->getIcon()">
                {{ $task->status->getLabel() }}
            </x-filament::badge>
            <div style="flex: 1; min-width: 0;">
                <div style="font-weight: 600; font-size: .875rem;">{{ $task->script->getLabel() }}</div>
                <div style="color: var(--gray-500, #6b7280); font-size: .8125rem;">
                    {{ $task->script->summary($task->parameters) }}@if ($task->result !== null) · {{ $task->result }}@endif
                </div>
                @if ($task->status->isActive() && $task->percent() !== null)
                    <div style="display: flex; align-items: center; gap: .5rem; margin-top: .375rem; max-width: 28rem;">
                        <x-progress :percent="$task->percent()" />
                        <span style="font-size: .75rem; color: var(--gray-500, #6b7280); min-width: 4.5rem; text-align: right;">
                            {{ $task->progress_done }}/{{ $task->progress_total }} · {{ $task->percent() }}%
                        </span>
                    </div>
                @endif
            </div>
            @if ($task->started_at !== null)
                <span style="color: var(--gray-500, #6b7280); font-size: .75rem;">
                    {{ $task->duration() }}
                </span>
            @endif
            @if ($task->cancellable())
                <x-filament::button
                    size="xs"
                    color="gray"
                    icon="heroicon-m-x-mark"
                    wire:click="cancel({{ $task->id }})"
                    wire:confirm="Cancel this task?"
                >
                    Cancel
                </x-filament::button>
            @endif
        </div>
        @if ($task->script->report() !== null && $task->started_at !== null)
            <div style="margin-top: .75rem; padding-top: .75rem; border-top: 1px solid var(--gray-200, #e5e7eb);">
                @include($task->script->report(), ['task' => $task, 'stats' => new \Lorekeeper\Apps\Desktop\Bots\TaskStats($task, now())])
            </div>
        @endif
        </div>
    @empty
        <div style="color: var(--gray-500, #6b7280); font-size: .875rem;">
            @if ($run->status->isActive())
                No tasks yet. Use "Add task" to tell the bot what to do; it waits in the game meanwhile.
            @else
                This run had no tasks.
            @endif
        </div>
    @endforelse
</div>
