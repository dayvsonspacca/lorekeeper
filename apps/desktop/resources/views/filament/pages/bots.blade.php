<x-filament-panels::page>
    <div wire:poll.2s class="lk">
        @if ($accounts->isEmpty())
            <x-filament::empty-state icon="heroicon-o-user-group" heading="No characters yet" description="Add the AQW characters your bots will play." />
        @else
            <div class="lk-realm">
                @foreach ([
                    ['Online', "{$realm->online} / {$realm->accounts}", 'bots in the game', 'activity', '#4ade80'],
                    ['Experience', number_format($realm->experiencePerHour), 'per hour, all bots', 'trend', '#c4b5fd'],
                    ['Kills', number_format($realm->kills), 'in the last 30 minutes', 'swords', '#f87171'],
                    ['Gold', number_format($realm->gold), 'earned today', 'coins', '#fbbf24'],
                    ['Levels', number_format($realm->levels), 'gained today', 'star', '#fb923c'],
                ] as [$label, $value, $hint, $glyph, $tone])
                    <div class="lk-tile" style="--tone: {{ $tone }};">
                        <div class="lk-tile__label"><x-scene.glyph :name="$glyph" /> {{ $label }}</div>
                        <div class="lk-tile__value">{{ $value }}</div>
                        <div class="lk-tile__hint">{{ $hint }}</div>
                    </div>
                @endforeach
            </div>

            <div class="lk-deck">
                @foreach ($accounts as $account)
                    @php
                        $run = $account->latestRun;
                        $active = $run !== null && $run->status->isActive();
                        $scene = $run?->scene();
                        $hero = $scene?->hero;
                        $task = $active ? $run->currentTask() : null;
                        $percent = $task?->percent();
                        $unfinished = $active ? [] : $account->unfinishedTasks();
                        $state = match (true) {
                            !$active => 'offline',
                            $hero === null => 'entering',
                            $hero['away'] => 'away',
                            default => $hero['state'],
                        };
                        $foes = $active && $scene !== null
                            ? array_slice(array_values(array_filter($scene->engaged($now), static fn (array $monster): bool => $monster['state'] !== 'dead')), 0, 2)
                            : [];
                        $stats = $task === null ? null : new \Lorekeeper\Apps\Desktop\Bots\TaskStats($task, now());
                    @endphp
                    <article
                        wire:key="account-{{ $account->id }}"
                        class="lk-plate lk-hue"
                        style="--h: {{ \Lorekeeper\Apps\Desktop\Bots\Scene\Palette::hue($hero['class'] ?? $account->name) }};"
                        data-state="{{ $state }}"
                        data-online="{{ $active ? 'true' : 'false' }}"
                    >
                        <header class="lk-head">
                            <x-scene.crest :name="$account->name" :level="$hero['level'] ?? $account->level" :percent="$scene?->rankPercent() ?? 0" />
                            <div class="lk-head__who">
                                <div class="lk-head__name">{{ $hero['name'] ?? $account->name }}</div>
                                @if ($hero !== null)
                                    <div class="lk-head__line">
                                        {{ $hero['class'] }} <x-scene.pips :rank="$hero['rank']" />
                                    </div>
                                    <div class="lk-head__line">
                                        <x-scene.glyph name="pin" /> {{ $hero['map'] }}
                                        @if ($scene->players !== [])
                                            <span class="lk-faint">·</span> <x-scene.glyph name="users" /> {{ count($scene->players) }}
                                        @endif
                                    </div>
                                @else
                                    <div class="lk-head__line">Lv {{ $account->level ?? '?' }} · {{ $run?->map ?? 'never entered the game' }}</div>
                                @endif
                            </div>
                            <x-scene.chip :state="$state" />
                        </header>

                        @if ($active && $hero !== null)
                            <div class="lk-meters">
                                <x-scene.bar :current="$hero['health']" :maximum="$hero['maxHealth']" kind="health" />
                                <x-scene.bar :current="$hero['mana']" :maximum="$hero['maxMana']" kind="mana" />
                            </div>
                        @elseif (!$active && $scene !== null)
                            <div class="lk-head__line">
                                Last seen {{ \Lorekeeper\Apps\Desktop\Bots\Scene\Clock::ago($scene->at, $now) }} in {{ $hero['map'] }}
                            </div>
                        @endif

                        @foreach ($foes as $foe)
                            <div class="lk-foe" style="--race: {{ \Lorekeeper\Apps\Desktop\Bots\Scene\Palette::race($foe['race']) }};">
                                <x-scene.glyph name="swords" style="width: .9rem; height: .9rem; color: var(--lk-bad); flex-shrink: 0;" />
                                <span class="lk-foe__name">{{ $foe['name'] }}</span>
                                <span class="lk-faint" style="font-size: .68rem;">Lv {{ $foe['level'] }}</span>
                                <x-scene.bar class="lk-foe__bar" :current="$foe['health']" :maximum="$foe['maxHealth']" compact />
                            </div>
                        @endforeach

                        @if ($task !== null)
                            <div class="lk-quest">
                                @if ($percent !== null)
                                    <x-scene.ring :percent="$percent" />
                                @else
                                    <x-scene.glyph name="scroll" style="width: 1.5rem; height: 1.5rem; color: var(--lk-hue);" />
                                @endif
                                <div class="lk-quest__body">
                                    <div class="lk-quest__title">{{ $task->title() }}</div>
                                    <div class="lk-quest__meta">
                                        {{ number_format($task->kills) }} kills · {{ number_format($task->experience) }} XP · {{ $task->duration() ?? '—' }}
                                        @if ($stats?->minutesToTarget() !== null)
                                            · ≈ {{ $stats->minutesToTarget() }} min left
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @elseif ($active)
                            <div class="lk-quest">
                                <x-scene.glyph name="scroll" style="width: 1.5rem; height: 1.5rem; color: var(--lk-faint);" />
                                <div class="lk-quest__body">
                                    <div class="lk-quest__title">Waiting for a task</div>
                                    <div class="lk-quest__meta">Open the run to tell the bot what to play.</div>
                                </div>
                            </div>
                        @elseif ($unfinished !== [])
                            <div class="lk-quest">
                                <x-scene.glyph name="scroll" style="width: 1.5rem; height: 1.5rem; color: var(--lk-gold);" />
                                <div class="lk-quest__body">
                                    <div class="lk-quest__meta">Left off</div>
                                    <div class="lk-quest__title">
                                        {{ $unfinished[0]->title() }}
                                        @if (count($unfinished) > 1)
                                            <span class="lk-dim" style="font-weight: 500;">+{{ count($unfinished) - 1 }} more</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if ($active && $scene !== null && array_sum($scene->pulse->experience($now)) === 0)
                            <div class="lk-calm"><x-scene.glyph name="moon" /> No fights in the last 30 minutes</div>
                        @elseif ($active && $scene !== null)
                            <div class="lk-pace">
                                <div class="lk-pace__graph">
                                    <x-scene.spark :values="$scene->pulse->experience($now)" :id="$account->id" />
                                </div>
                                <div class="lk-pace__value">
                                    <strong>{{ number_format($scene->pulse->experiencePerHour($now)) }}</strong>
                                    <span>XP / hour</span>
                                </div>
                            </div>
                        @endif

                        <footer class="lk-actions">
                            @if ($active)
                                {{ ($this->stopAction)(['run' => $run->id]) }}
                            @else
                                @if ($unfinished !== [])
                                    <x-filament::button size="sm" icon="heroicon-m-arrow-path" wire:click="resume({{ $account->id }})">
                                        Resume
                                    </x-filament::button>
                                @endif
                                <x-filament::button size="sm" :color="$unfinished === [] ? 'primary' : 'gray'" icon="heroicon-m-play" wire:click="start({{ $account->id }})">
                                    Start
                                </x-filament::button>
                            @endif
                            @if ($run !== null)
                                <x-filament::button size="sm" color="gray" icon="heroicon-m-arrow-top-right-on-square" :href="\Lorekeeper\Apps\Desktop\Filament\Resources\BotRuns\BotRunResource::getUrl('view', ['record' => $run])" tag="a">
                                    {{ $active ? 'Watch' : 'Open' }}
                                </x-filament::button>
                            @endif
                        </footer>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
