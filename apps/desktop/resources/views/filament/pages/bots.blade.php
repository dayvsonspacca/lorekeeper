<x-filament-panels::page>
    <div wire:poll.2s>
        @if ($accounts->isEmpty())
            <x-filament::empty-state icon="heroicon-o-user-group" heading="No characters yet" description="Add the AQW characters your bots will play." />
        @else
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(19rem, 1fr)); gap: 1rem;">
                @foreach ($accounts as $account)
                    @php
                        $run = $account->latestRun;
                        $active = $run !== null && $run->status->isActive();
                        $task = $active ? $run->currentTask() : null;
                        $percent = $task?->percent();
                    @endphp
                    <div wire:key="account-{{ $account->id }}" style="display: flex; flex-direction: column; gap: .75rem; padding: 1.125rem 1.25rem; border-radius: 1rem; border: 1px solid {{ $active ? 'color-mix(in oklab, var(--primary-500) 55%, transparent)' : 'color-mix(in oklab, var(--gray-500) 25%, transparent)' }}; background: {{ $active ? 'color-mix(in oklab, var(--primary-500) 7%, transparent)' : 'transparent' }};">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: .75rem;">
                            <div style="min-width: 0;">
                                <div style="font-weight: 700; font-size: 1.0625rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $account->name }}</div>
                                <div style="color: var(--gray-500, #6b7280); font-size: .8125rem;">
                                    Lv {{ $account->level ?? '?' }} · {{ $run?->map ?? 'not in the game yet' }}
                                </div>
                            </div>
                            @if ($run !== null)
                                <x-filament::badge :color="$run->status->getColor()" :icon="$run->status->getIcon()">
                                    {{ $run->status->getLabel() }}
                                </x-filament::badge>
                            @else
                                <x-filament::badge color="gray">Never ran</x-filament::badge>
                            @endif
                        </div>

                        <div style="min-height: 3.25rem;">
                            @if ($task !== null)
                                <div style="font-size: .875rem; font-weight: 600;">
                                    {{ $task->script->getLabel() }}
                                    <span style="color: var(--gray-500, #6b7280); font-weight: 400;">· {{ $task->script->summary($task->parameters) }}</span>
                                </div>
                                @if ($percent !== null)
                                    <div style="display: flex; align-items: center; gap: .5rem; margin-top: .375rem;">
                                        <div style="flex: 1;"><x-progress :percent="$percent" /></div>
                                        <span style="font-size: .75rem; color: var(--gray-500, #6b7280); min-width: 2.5rem; text-align: right;">{{ $percent }}%</span>
                                    </div>
                                @endif
                                <div style="color: var(--gray-500, #6b7280); font-size: .75rem; margin-top: .375rem;">
                                    {{ number_format($task->kills) }} kills · {{ number_format($task->experience) }} XP · {{ $task->duration() ?? '—' }}
                                </div>
                            @elseif ($active)
                                <div style="color: var(--gray-500, #6b7280); font-size: .875rem;">Waiting for a task</div>
                            @else
                                <div style="color: var(--gray-400, #9ca3af); font-size: .875rem;">Offline</div>
                            @endif
                        </div>

                        <div style="display: flex; gap: .5rem; flex-wrap: wrap;">
                            @if ($active)
                                <x-filament::button size="sm" color="danger" icon="heroicon-m-stop" wire:click="stop({{ $run->id }})" wire:confirm="Stop {{ $account->name }}? The bot logs out.">
                                    Stop
                                </x-filament::button>
                            @else
                                <x-filament::button size="sm" icon="heroicon-m-play" wire:click="start({{ $account->id }})">
                                    Start
                                </x-filament::button>
                            @endif
                            @if ($run !== null)
                                <x-filament::button size="sm" color="gray" icon="heroicon-m-arrow-top-right-on-square" :href="\Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\BotRunResource::getUrl('view', ['record' => $run])" tag="a">
                                    Open
                                </x-filament::button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
