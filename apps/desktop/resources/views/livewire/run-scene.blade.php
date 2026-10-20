<div @if ($run->status->isActive()) wire:poll.1s @endif class="lk lk-stage">
    @if ($scene === null)
        <div class="lk-panel lk-waiting">
            @if ($run->status->isActive())
                <div class="lk-waiting__orb"></div>
                <div>
                    <div style="font-weight: 800; color: var(--lk-text);">Entering the game</div>
                    <div style="font-size: .8rem;">The scene appears as soon as {{ $run->account->name }} is in a map.</div>
                </div>
            @else
                <x-scene.glyph name="moon" style="width: 2rem; height: 2rem;" />
                <div style="font-size: .85rem;">This run never reached the game.</div>
            @endif
        </div>
    @else
        @php
            $hero = $scene->hero;
            $active = $run->status->isActive();
            $state = match (true) {
                !$active => 'offline',
                $hero['away'] => 'away',
                default => $hero['state'],
            };
            $engaged = array_column($scene->engaged($now), 'cell');
        @endphp

        <div class="lk-stage__row">
            <section class="lk-panel lk-hero lk-hue" style="--h: {{ \Lorekeeper\Apps\Desktop\Bots\Scene\Palette::hue($hero['class']) }};" data-state="{{ $state }}">
                <div class="lk-hero__top">
                    <x-scene.crest :name="$hero['name']" :level="$hero['level']" :percent="$scene->rankPercent()" size="lg" />
                    <div class="lk-hero__name">{{ $hero['name'] }}</div>
                    <div class="lk-hero__class">
                        {{ $hero['class'] }} <span class="lk-faint">·</span> Rank {{ $hero['rank'] }} <x-scene.pips :rank="$hero['rank']" />
                        @if ($hero['member'] ?? false)
                            <span class="lk-member">Member</span>
                        @endif
                    </div>
                    <x-scene.chip :state="$state" />
                </div>

                <div class="lk-meters lk-meters--lg">
                    <x-scene.bar :current="$hero['health']" :maximum="$hero['maxHealth']" kind="health" />
                    <x-scene.bar :current="$hero['mana']" :maximum="$hero['maxMana']" kind="mana" />
                    @if (($hero['maxExperience'] ?? 0) > 0 && $hero['level'] < 100)
                        <x-scene.bar :current="$hero['experience']" :maximum="$hero['maxExperience']" kind="experience" />
                    @endif
                </div>

                @if ($hero['skills'] !== [])
                    <x-scene.hotbar :skills="$hero['skills']" />
                @endif

                <div class="lk-hero__facts">
                    <div class="lk-fact">
                        <strong>{{ $hero['rankTo'] === null ? 'Max' : $scene->rankPercent() . '%' }}</strong>
                        <span>to rank {{ min(10, $hero['rank'] + 1) }}</span>
                    </div>
                    <div class="lk-fact">
                        <strong>{{ number_format($hero['points']) }}</strong>
                        <span>class points</span>
                    </div>
                    @if (array_key_exists('gold', $hero))
                        <div class="lk-fact">
                            <strong style="color: var(--lk-gold);">{{ number_format($hero['gold']) }}</strong>
                            <span>gold</span>
                        </div>
                    @endif
                    <a class="lk-fact" href="#lk-inventory">
                        <strong>{{ number_format($hero['items']) }}</strong>
                        <span>items</span>
                    </a>
                </div>
            </section>

            <section class="lk-panel">
                <div class="lk-field__head">
                    <div>
                        <div class="lk-faint" style="font-size: .68rem; letter-spacing: .14em; text-transform: uppercase; font-weight: 800; display: flex; align-items: center; gap: .35rem;">
                            <x-scene.glyph name="pin" style="width: .85rem; height: .85rem;" /> {{ $active ? 'Now in' : 'Last seen in' }}
                        </div>
                        <div class="lk-field__map">{{ $hero['map'] }}</div>
                        <div class="lk-field__sub">
                            {{ $scene->alive() }} of {{ count($scene->monsters) }} monsters standing
                            @if (!$active)
                                · {{ \Lorekeeper\Apps\Desktop\Bots\Scene\Clock::ago($scene->at, $now) }}
                            @endif
                        </div>
                    </div>
                    @if ($scene->players !== [])
                        <div class="lk-crowd" title="Players in the room">
                            @foreach (array_slice($scene->players, 0, 8) as $player)
                                <span>{{ $player }}</span>
                            @endforeach
                            @if (count($scene->players) > 8)
                                <span>+{{ count($scene->players) - 8 }}</span>
                            @endif
                        </div>
                    @endif
                </div>

                @if ($scene->monsters === [])
                    <div class="lk-dim" style="font-size: .85rem; padding: 2rem 0; text-align: center;">No monsters here. A quiet map.</div>
                @else
                    @php
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
        </div>

        <section class="lk-panel" id="lk-inventory">
            <div class="lk-panel__title">
                <h3><x-scene.glyph name="package" /> Inventory · {{ number_format($scene->backpack->count()) }} items</h3>
            </div>
            <x-scene.backpack :backpack="$scene->backpack" />
        </section>

        @php
            $panel = $task?->script->view(\Lorekeeper\Apps\Desktop\Bots\ScriptView::Panel);
            $panel = $panel !== null && view()->exists($panel) ? $panel : null;
        @endphp
        <div @class(['lk-stage__row', 'lk-stage__row--low' => $panel !== null])>
            @if ($panel !== null)
                <section class="lk-panel">
                    @include($panel, ['task' => $task, 'stats' => new \Lorekeeper\Apps\Desktop\Bots\TaskStats($task, now()), 'scene' => $scene, 'now' => $now])
                </section>
            @endif
            <section class="lk-panel" @if ($panel === null) style="grid-column: 1 / -1;" @endif>
                <div class="lk-panel__title">
                    <h3><x-scene.glyph name="scroll" /> Chronicle</h3>
                </div>
                <x-scene.feed :lines="$scene->feed->lines" :now="$now" />
            </section>
        </div>
    @endif
</div>
