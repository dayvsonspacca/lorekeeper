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
            $experience = $scene->pulse->experience($now);
            $kills = $scene->pulse->kills($now);
            $peak = max(1, ...$experience);
        @endphp

        <div class="lk-stage__row">
            <section class="lk-panel lk-hero lk-hue" style="--h: {{ \Lorekeeper\Apps\Desktop\Bots\Scene\Palette::hue($hero['class']) }};" data-state="{{ $state }}">
                <div class="lk-hero__top">
                    <x-scene.crest :name="$hero['name']" :level="$hero['level']" :percent="$scene->rankPercent()" size="lg" />
                    <div class="lk-hero__name">{{ $hero['name'] }}</div>
                    <div class="lk-hero__class">
                        {{ $hero['class'] }} <span class="lk-faint">·</span> Rank {{ $hero['rank'] }} <x-scene.pips :rank="$hero['rank']" />
                    </div>
                    <x-scene.chip :state="$state" />
                </div>

                <div class="lk-meters lk-meters--lg">
                    <x-scene.bar :current="$hero['health']" :maximum="$hero['maxHealth']" kind="health" />
                    <x-scene.bar :current="$hero['mana']" :maximum="$hero['maxMana']" kind="mana" />
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
                    <div class="lk-fact">
                        <strong>{{ number_format($hero['items']) }}</strong>
                        <span>items</span>
                    </div>
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
                    <div class="lk-cells">
                        @foreach ($scene->cells($now) as $cell => $monsters)
                            <div class="lk-cell" wire:key="cell-{{ $cell }}" @if (in_array($cell, $engaged, true)) data-engaged @endif>
                                <div class="lk-cell__name">
                                    @if (in_array($cell, $engaged, true))
                                        <x-scene.glyph name="swords" /> {{ $cell }} · fighting here
                                    @else
                                        {{ $cell }}
                                    @endif
                                </div>
                                <div class="lk-cell__mobs">
                                    @foreach ($monsters as $monster)
                                        <x-scene.mob :monster="$monster" :now="$now" wire:key="mob-{{ $monster['id'] }}" />
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <div class="lk-stage__row lk-stage__row--low">
            <section class="lk-panel">
                <div class="lk-panel__title">
                    <h3><x-scene.glyph name="activity" /> Pulse · last 30 minutes</h3>
                </div>
                <div class="lk-pulse__head">
                    <div class="lk-pulse__stat">
                        <strong style="color: var(--lk-xp);">{{ number_format($scene->pulse->experiencePerHour($now)) }}</strong>
                        <span>XP per hour</span>
                    </div>
                    <div class="lk-pulse__stat">
                        <strong>{{ number_format(array_sum($experience)) }}</strong>
                        <span>XP earned</span>
                    </div>
                    <div class="lk-pulse__stat">
                        <strong style="color: var(--lk-bad);">{{ number_format(array_sum($kills)) }}</strong>
                        <span>kills</span>
                    </div>
                    <div class="lk-pulse__stat">
                        <strong>{{ array_sum($kills) === 0 ? '—' : number_format(intdiv(array_sum($experience), array_sum($kills))) }}</strong>
                        <span>XP per kill</span>
                    </div>
                </div>
                <div class="lk-chart">
                    @foreach ($experience as $minute => $earned)
                        <div class="lk-chart__bar" title="{{ \Lorekeeper\Apps\Desktop\Bots\Scene\Pulse::MINUTES - 1 - $minute }} min ago · {{ number_format($earned) }} XP · {{ $kills[$minute] }} kills">
                            <i style="--v: {{ round(($earned * 100) / $peak, 1) }}%;"></i>
                        </div>
                    @endforeach
                </div>
                <div class="lk-chart__axis"><span>30 min ago</span><span>now</span></div>
            </section>

            <section class="lk-panel">
                <div class="lk-panel__title">
                    <h3><x-scene.glyph name="scroll" /> Chronicle</h3>
                </div>
                <x-scene.feed :lines="$scene->feed->lines" :now="$now" />
            </section>
        </div>
    @endif
</div>
