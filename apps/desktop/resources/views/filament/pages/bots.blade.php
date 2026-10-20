<x-filament-panels::page>
    <div wire:poll.2s class="lk lk-board">
        <div class="lk-sum">
            @foreach ([
                ['Online', "{$realm->online} of {$realm->accounts}", 'var(--lk-good)'],
                ['Played today', $realm->played(), null],
                ['Tasks done today', $realm->tasksQueued === 0 ? number_format($realm->tasksDone) : number_format($realm->tasksDone) . " · {$realm->tasksQueued} waiting", null],
                ['Gold today', number_format($realm->gold), 'var(--lk-gold)'],
                ['Failed in 24h', number_format($realm->troubles), $realm->troubles === 0 ? null : 'var(--lk-bad)'],
            ] as [$label, $value, $tone])
                <div class="lk-sum__cell">
                    <div class="lk-sum__label">{{ $label }}</div>
                    <div class="lk-sum__value" @if ($tone !== null) style="color: {{ $tone }};" @endif>{{ $value }}</div>
                </div>
            @endforeach
        </div>

        @if ($accounts->isEmpty())
            <x-filament::empty-state icon="heroicon-o-user-group" heading="No characters yet" description="Add the AQW characters your bots will play." />
        @else
            <div class="lk-table">
                <div class="lk-table__inner">
                    <div class="lk-row lk-row--head">
                        <span></span><span>Character</span><span>Level</span><span>Status</span><span>Map</span><span>Doing now</span><span>Time</span><span></span>
                    </div>

                    @foreach ($accounts as $account)
                        @php
                            $run = $account->latestRun;
                            $active = $run !== null && $run->status->isActive();
                            $scene = $run?->scene();
                            $hero = $active ? $scene?->hero : null;
                            $task = $active ? $run->currentTask() : null;
                            $percent = $task?->percent();
                            $unfinished = $active ? [] : $account->unfinishedTasks();
                            $open = $expanded === $account->id;
                            [$state, $label] = match (true) {
                                $run === null => ['offline', 'Never ran'],
                                !$active => [$run->status->value, $run->status->getLabel()],
                                $hero === null => ['entering', 'Entering'],
                                $hero['away'] => ['away', 'Away'],
                                $hero['state'] === 'dead' => ['dead', 'Fallen'],
                                $hero['state'] === 'fighting' => ['fighting', 'Fighting'],
                                default => ['idle', 'Idle'],
                            };
                        @endphp
                        <div wire:key="account-{{ $account->id }}">
                            <div class="lk-row" @if ($open) data-open @endif>
                                <button type="button" class="lk-row__toggle" wire:click="toggle({{ $account->id }})" aria-label="{{ $open ? 'Hide' : 'Show' }} {{ $account->name }}" aria-expanded="{{ $open ? 'true' : 'false' }}">
                                    <x-filament::icon icon="heroicon-m-chevron-right" />
                                </button>
                                <button type="button" class="lk-row__who" wire:click="toggle({{ $account->id }})">
                                    <span class="lk-row__name">{{ $hero['name'] ?? $account->name }}</span>
                                    <span class="lk-row__sub">{{ $hero['class'] ?? $account->server ?? 'First open server' }}</span>
                                </button>
                                <span>{{ $hero['level'] ?? $account->level ?? '?' }}</span>
                                <span><span class="lk-tag" data-state="{{ $state }}">{{ $label }}</span></span>
                                <span class="lk-dim lk-cut">{{ $active ? ($hero !== null ? $scene->place() : $run->map ?? '—') : '—' }}</span>
                                <span class="lk-row__doing">
                                    @if ($task !== null)
                                        <span class="lk-cut">{{ $task->title() }}@if ($percent !== null) · {{ $percent }}%@endif</span>
                                        @if ($percent !== null)
                                            <span class="lk-track"><span style="width: {{ $percent }}%;"></span></span>
                                        @endif
                                    @elseif ($active)
                                        <span class="lk-dim">Waiting for a task</span>
                                    @elseif ($unfinished !== [])
                                        <span class="lk-cut"><span class="lk-dim">Left off:</span> {{ $unfinished[0]->title() }}@if (count($unfinished) > 1) <span class="lk-dim">+{{ count($unfinished) - 1 }} more</span>@endif</span>
                                    @else
                                        <span class="lk-dim">—</span>
                                    @endif
                                </span>
                                <span class="lk-dim">{{ $active ? $run->duration() ?? '—' : $run?->finished_at?->shortRelativeDiffForHumans() ?? '—' }}</span>
                                <span class="lk-row__actions">
                                    @if ($active)
                                        {{ ($this->stopAction)(['run' => $run->id]) }}
                                    @else
                                        @if ($unfinished !== [])
                                            <x-filament::button size="sm" wire:click="resume({{ $account->id }})">Resume</x-filament::button>
                                        @endif
                                        <x-filament::button size="sm" :color="$unfinished === [] ? 'primary' : 'gray'" wire:click="start({{ $account->id }})">Start</x-filament::button>
                                    @endif
                                </span>
                            </div>

                            @if ($open)
                                <div class="lk-detail">
                                    @if ($detail === null)
                                        <div class="lk-dim">This character has not played yet. Start it to see what it does here.</div>
                                    @else
                                        <div class="lk-detail__col">
                                            @if ($hero !== null)
                                                <div class="lk-meters">
                                                    <x-scene.bar :current="$hero['health']" :maximum="$hero['maxHealth']" kind="health" />
                                                    <x-scene.bar :current="$hero['mana']" :maximum="$hero['maxMana']" kind="mana" />
                                                    @if (($hero['maxExperience'] ?? 0) > 0 && $hero['level'] < 100)
                                                        <x-scene.bar :current="$hero['experience']" :maximum="$hero['maxExperience']" kind="experience" />
                                                    @endif
                                                </div>
                                                @foreach (array_slice(array_values(array_filter($scene->engaged($now), static fn (array $monster): bool => $monster['state'] !== 'dead')), 0, 2) as $foe)
                                                    <div class="lk-foe" style="--race: {{ \Lorekeeper\Apps\Desktop\Bots\Scene\Palette::race($foe['race']) }};">
                                                        <x-scene.glyph name="swords" style="width: .9rem; height: .9rem; color: var(--lk-bad); flex-shrink: 0;" />
                                                        <span class="lk-foe__name">{{ $foe['name'] }}</span>
                                                        <span class="lk-faint" style="font-size: .68rem;">Lv {{ $foe['level'] }}</span>
                                                        <x-scene.bar class="lk-foe__bar" :current="$foe['health']" :maximum="$foe['maxHealth']" compact />
                                                    </div>
                                                @endforeach
                                            @elseif ($active)
                                                <div class="lk-dim">Entering the game…</div>
                                            @elseif ($scene !== null)
                                                <div class="lk-dim">Last seen {{ \Lorekeeper\Apps\Desktop\Bots\Scene\Clock::ago($scene->at, $now) }} in {{ $scene->hero['map'] }}.</div>
                                            @else
                                                <div class="lk-dim">{{ $run->result ?? 'The bot is off.' }}</div>
                                            @endif
                                        </div>

                                        <div class="lk-detail__col">
                                            @if ($task !== null)
                                                @includeIf($task->script->view(\Lorekeeper\Apps\Desktop\Bots\ScriptView::Plate), ['task' => $task, 'stats' => new \Lorekeeper\Apps\Desktop\Bots\TaskStats($task, now()), 'scene' => $scene, 'now' => $now])
                                            @endif
                                            <div class="lk-detail__label">Queue</div>
                                            @forelse ($detail['queue'] as $queued)
                                                <div class="lk-queue">
                                                    @if ($queued->status === \Lorekeeper\Apps\Desktop\Bots\TaskStatus::Pending)
                                                        <span class="lk-queue__waiting"></span>
                                                    @else
                                                        <x-filament::icon icon="heroicon-m-play" class="lk-queue__playing" />
                                                    @endif
                                                    <span>#{{ $queued->id }} {{ $queued->title() }}</span>
                                                </div>
                                            @empty
                                                <div class="lk-dim">Nothing queued.</div>
                                            @endforelse
                                            @if ($active)
                                                <div>{{ ($this->addTaskAction)(['run' => $run->id]) }}</div>
                                            @endif
                                        </div>

                                        <div class="lk-log">
                                            @forelse ($detail['logs'] as $log)
                                                <div class="lk-log__line"><span class="lk-log__at">{{ $log['at'] }}</span><span data-level="{{ $log['level'] }}">{{ $log['message'] }}</span></div>
                                            @empty
                                                <div class="lk-log__at">Nothing logged yet.</div>
                                            @endforelse
                                            <a class="lk-log__more" href="{{ \Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\BotRunResource::getUrl('view', ['record' => $run]) }}">{{ $active ? 'Watch the run' : 'Open the run' }}</a>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
