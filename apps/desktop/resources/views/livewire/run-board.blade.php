<div @if ($run->status->isActive()) wire:poll.2s @endif class="lk lk-board lk-run">
    @php
        $active = $run->status->isActive();
        $hero = $scene?->hero;
        [$state, $label] = match (true) {
            !$active => [$run->status->value, $run->status->getLabel()],
            $hero === null => ['entering', 'Entering'],
            $hero['away'] => ['away', 'Away'],
            $hero['state'] === 'dead' => ['dead', 'Fallen'],
            $hero['state'] === 'fighting' => ['fighting', 'Fighting'],
            default => ['idle', 'Idle'],
        };
        $panel = $task?->script->view(\Lorekeeper\Apps\Desktop\Bots\ScriptView::Panel);
        $panel = $scene !== null && $panel !== null && view()->exists($panel) ? $panel : null;
    @endphp

    <div class="lk-sum">
        <div class="lk-sum__cell">
            <div class="lk-sum__label">Status</div>
            <div class="lk-run__line"><span class="lk-tag" data-state="{{ $state }}">{{ $label }}</span> <span class="lk-dim">{{ $run->duration() ?? '—' }}</span></div>
        </div>
        @if ($hero !== null)
            <div class="lk-sum__cell">
                <div class="lk-sum__label">Level {{ $hero['level'] }} · rank {{ $hero['rank'] }}</div>
                <div class="lk-run__line">
                    <span class="lk-cut">{{ $hero['class'] }}</span>
                    @if ($hero['member'] ?? false)
                        <span class="lk-member" title="Member">M</span>
                    @endif
                </div>
            </div>
            <div class="lk-sum__cell">
                <div class="lk-sum__label">{{ $active ? 'Map' : 'Last seen ' . \Lorekeeper\Apps\Desktop\Bots\Scene\Clock::ago($scene->at, $now) }}</div>
                <div class="lk-run__line">
                    <span class="lk-cut">{{ $scene->place() }}</span>
                    @if ($scene->players !== [])
                        <span class="lk-dim" title="{{ implode(', ', $scene->players) }}">· {{ count($scene->players) }} {{ count($scene->players) === 1 ? 'player' : 'players' }}</span>
                    @endif
                </div>
            </div>
            <div class="lk-sum__cell lk-run__meter"><x-scene.bar :current="$hero['health']" :maximum="$hero['maxHealth']" kind="health" /></div>
            <div class="lk-sum__cell lk-run__meter"><x-scene.bar :current="$hero['mana']" :maximum="$hero['maxMana']" kind="mana" /></div>
            @if (($hero['maxExperience'] ?? 0) > 0 && $hero['level'] < 100)
                <div class="lk-sum__cell lk-run__meter"><x-scene.bar :current="$hero['experience']" :maximum="$hero['maxExperience']" kind="experience" /></div>
            @endif
            @if (array_key_exists('gold', $hero))
                <div class="lk-sum__cell">
                    <div class="lk-sum__label">Gold</div>
                    <div class="lk-run__line" style="color: var(--lk-gold);">{{ number_format($hero['gold']) }}</div>
                </div>
            @endif
        @else
            <div class="lk-sum__cell" style="grid-column: span 3;">
                <div class="lk-sum__label">{{ $run->account->name }}</div>
                <div class="lk-run__line lk-dim">{{ $active ? 'Entering the game…' : $run->result ?? 'This run never reached the game.' }}</div>
            </div>
        @endif
    </div>

    <div class="lk-run__main">
        <div class="lk-run__col">
            <section class="lk-box">
                <div class="lk-box__head">
                    <span>Now</span>
                    @if ($task !== null && $task->started_at !== null)
                        <span class="lk-dim">{{ $task->status->isActive() ? 'playing for' : 'played for' }} {{ $task->duration() }}</span>
                    @endif
                </div>
                @if ($task !== null)
                    <div class="lk-run__task">
                        <span class="lk-tag" data-state="{{ $task->status->value }}">{{ $task->status->getLabel() }}</span>
                        <span class="lk-cut" style="font-weight: 600;">{{ $task->title() }}</span>
                    </div>
                    @if ($task->result !== null)
                        <div class="lk-dim">{{ $task->result }}</div>
                    @endif
                    @if ($task->status->isActive() && $task->percent() !== null)
                        <div class="lk-run__progress">
                            <span class="lk-track"><span style="width: {{ $task->percent() }}%;"></span></span>
                            <span class="lk-dim">{{ $task->progress_done }}/{{ $task->progress_total }} · {{ $task->percent() }}%</span>
                        </div>
                    @endif
                    @if ($panel !== null)
                        <div class="lk-run__panel">
                            @include($panel, ['task' => $task, 'stats' => new \Lorekeeper\Apps\Desktop\Bots\TaskStats($task, now()), 'scene' => $scene, 'now' => $now])
                        </div>
                    @endif
                @elseif ($active)
                    <div class="lk-dim">Waiting for a task. Use "Add task" to tell the bot what to play.</div>
                @else
                    <div class="lk-dim">{{ $run->result ?? 'The run ended.' }}</div>
                @endif
            </section>

            <section class="lk-box">
                <div class="lk-box__head"><span>Tasks</span><span class="lk-dim">{{ $tasks->count() }}</span></div>
                <div class="lk-run__tasks">
                @forelse ($tasks as $queued)
                    <div class="lk-run__row" wire:key="task-{{ $queued->id }}" x-data="{ open: false }">
                        @php
                            $report = $queued->started_at !== null ? $queued->script->view(\Lorekeeper\Apps\Desktop\Bots\ScriptView::Report) : null;
                            $report = $report !== null && view()->exists($report) ? $report : null;
                        @endphp
                        <div class="lk-run__rowhead">
                            @if ($report !== null)
                                <button type="button" class="lk-row__toggle" x-on:click="open = ! open" x-bind:aria-expanded="open" aria-label="Report of task #{{ $queued->id }}">
                                    <x-filament::icon icon="heroicon-m-chevron-right" x-bind:style="{ transform: open ? 'rotate(90deg)' : 'none' }" />
                                </button>
                            @else
                                <span></span>
                            @endif
                            <span class="lk-dim">#{{ $queued->id }}</span>
                            <span class="lk-tag" data-state="{{ $queued->status->value }}">{{ $queued->status->getLabel() }}</span>
                            <span class="lk-run__what">
                                <span class="lk-cut">{{ $queued->title() }}</span>
                                @if ($queued->result !== null)
                                    <span class="lk-dim lk-cut">{{ $queued->result }}</span>
                                @elseif ($queued->status->isActive() && $queued->percent() !== null)
                                    <span class="lk-dim">{{ $queued->progress_done }}/{{ $queued->progress_total }} · {{ $queued->percent() }}%</span>
                                @endif
                            </span>
                            <span class="lk-dim">{{ $queued->duration() ?? '' }}</span>
                            <span>
                                @if ($queued->cancellable())
                                    {{ ($this->cancelAction)(['task' => $queued->id]) }}
                                @endif
                            </span>
                        </div>
                        @if ($report !== null)
                            <div class="lk-run__report" x-show="open" x-collapse x-cloak>
                                @include($report, ['task' => $queued, 'stats' => new \Lorekeeper\Apps\Desktop\Bots\TaskStats($queued, now())])
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="lk-dim">{{ $active ? 'No tasks yet.' : 'This run had no tasks.' }}</div>
                @endforelse
                </div>
            </section>
        </div>

        <section class="lk-box lk-run__logbox">
            <div class="lk-box__head"><span>Log</span>@if ($active)<span class="lk-dim">live</span>@endif</div>
            <div
                class="lk-log lk-run__log"
                x-data="{ stick: true }"
                x-init="$el.scrollTop = $el.scrollHeight; new MutationObserver(() => { if (stick) $el.scrollTop = $el.scrollHeight }).observe($el, { childList: true, subtree: true })"
                x-on:scroll="stick = $el.scrollTop + $el.clientHeight >= $el.scrollHeight - 24"
            >
                @forelse ($logs as $log)
                    <div class="lk-log__line">
                        <span class="lk-log__at">{{ $log['at'] }}</span>
                        <span data-level="{{ $log['level'] }}">{{ $log['message'] }}</span>
                    </div>
                @empty
                    <div class="lk-log__at">Waiting for the bot to start…</div>
                @endforelse
            </div>
        </section>
    </div>

    @if ($scene !== null)
        <section class="lk-box">
            <div class="lk-run__tabs" role="tablist">
                @foreach ([
                    'map' => ['Map', "{$scene->alive()}/" . count($scene->monsters)],
                    'inventory' => ['Inventory', number_format($scene->backpack->count())],
                ] as $name => [$title, $count])
                    <button type="button" role="tab" class="lk-run__tab" wire:click="open('{{ $name }}')" aria-selected="{{ $tab === $name ? 'true' : 'false' }}">
                        {{ $title }} <span class="lk-dim">{{ $count }}</span>
                    </button>
                @endforeach
            </div>

            @if ($tab === 'inventory')
                <x-scene.backpack :backpack="$scene->backpack" />
            @elseif ($scene->monsters === [])
                <div class="lk-dim">No monsters in {{ $hero['map'] }}.</div>
            @else
                @php
                    $engaged = array_column($scene->engaged($now), 'cell');
                    $cells = $scene->cells($now);
                    $shown = $cell !== null && array_key_exists($cell, $cells) ? $cell : ($engaged[0] ?? array_key_first($cells));
                @endphp
                <div class="lk-tabs" role="tablist">
                    @foreach ($cells as $name => $monsters)
                        @php
                            $standing = count(array_filter($monsters, static fn (array $monster): bool => $monster['state'] !== 'dead'));
                        @endphp
                        <button
                            type="button"
                            role="tab"
                            class="lk-tab"
                            wire:key="tab-{{ $name }}"
                            wire:click="show(@js($name))"
                            aria-selected="{{ $name === $shown ? 'true' : 'false' }}"
                            @if (in_array($name, $engaged, true)) data-engaged @endif
                            @if ($standing === 0) data-empty @endif
                        >
                            @if (in_array($name, $engaged, true))
                                <x-scene.glyph name="swords" />
                            @endif
                            {{ $name }}
                            <span class="lk-tab__count">{{ $standing }}/{{ count($monsters) }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="lk-cell" role="tabpanel" wire:key="cell-{{ $shown }}" @if (in_array($shown, $engaged, true)) data-engaged @endif>
                    <div class="lk-cell__name">
                        @if (in_array($shown, $engaged, true))
                            <x-scene.glyph name="swords" /> {{ $shown }} · fighting here
                        @else
                            {{ $shown }}
                            @if ($cell !== null && $engaged !== [])
                                <button type="button" class="lk-cell__follow" wire:click="follow">
                                    <x-scene.glyph name="swords" /> back to the fight in {{ $engaged[0] }}
                                </button>
                            @endif
                        @endif
                    </div>
                    <div class="lk-cell__mobs">
                        @foreach ($cells[$shown] as $monster)
                            <x-scene.mob :monster="$monster" :now="$now" wire:key="mob-{{ $monster['id'] }}" />
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
    @endif

    <x-filament-actions::modals />
</div>
