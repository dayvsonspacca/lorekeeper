<x-filament-panels::page>
    <div wire:poll.2s class="lk lk-board">
        @if ($accounts->isEmpty())
            <x-filament::empty-state icon="heroicon-o-user-group" heading="No characters yet" description="Add the AQW accounts your bots will play." />
        @else
            <div class="lk-table">
                <div class="lk-table__inner">
                    <div class="lk-row lk-row--head">
                        <span></span><span>Character</span><span>Level</span><span>Status</span><span>Map</span><span>Time</span><span></span>
                    </div>

                    @foreach ($accounts as $account)
                        @php
                            $active = $account->playing();
                            $scene = $account->scene();
                            $hero = $active ? $scene?->hero : null;
                            $open = $expanded === $account->id;
                            [$state, $label] = match (true) {
                                $account->started_at === null && !$active => ['offline', 'Never played'],
                                !$active => [$account->status->value, $account->status->getLabel()],
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
                                <span class="lk-dim lk-cut">{{ $active ? ($hero !== null ? $scene->place() : $account->map ?? '—') : '—' }}</span>
                                <span class="lk-dim">{{ $active ? $account->duration() ?? '—' : $account->stopped_at?->shortRelativeDiffForHumans() ?? '—' }}</span>
                                <span class="lk-row__actions">
                                    @if ($active)
                                        {{ ($this->stopAction)(['account' => $account->id]) }}
                                    @else
                                        <x-filament::button size="sm" wire:click="start({{ $account->id }})">Start</x-filament::button>
                                    @endif
                                    {{ ($this->editAccountAction)(['account' => $account->id]) }}
                                    @unless ($active)
                                        {{ ($this->deleteAccountAction)(['account' => $account->id]) }}
                                    @endunless
                                </span>
                            </div>

                            @if ($open && $detail !== null)
                                <div class="lk-detail">
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
                                        @elseif ($account->result !== null)
                                            <div class="lk-dim">{{ $account->status->getLabel() }}: {{ $account->result }}</div>
                                        @elseif ($scene !== null)
                                            <div class="lk-dim">Last seen {{ \Lorekeeper\Apps\Desktop\Bots\Scene\Clock::ago($scene->at, $now) }} in {{ $scene->hero['map'] }}.</div>
                                        @else
                                            <div class="lk-dim">This character has not played yet.</div>
                                        @endif
                                    </div>

                                    <div class="lk-log">
                                        @forelse ($detail['logs'] as $log)
                                            <div class="lk-log__line"><span class="lk-log__at">{{ $log['at'] }}</span><span data-level="{{ $log['level'] }}">{{ $log['message'] }}</span></div>
                                        @empty
                                            <div class="lk-log__at">Nothing logged yet.</div>
                                        @endforelse
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
