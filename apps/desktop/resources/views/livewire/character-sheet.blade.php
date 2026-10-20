<div @if ($playing) wire:poll.5s @endif class="lk lk-board lk-run lk-sheet">
    @if ($scene === null)
        <section class="lk-box">
            <div class="lk-dim">The bot has not seen {{ $account->name }} in the game yet. Start a run and the sheet fills in once the bot enters.</div>
        </section>
    @else
        @php
            $hero = $scene->hero;
            $equipped = $scene->backpack->equipped();
            $enchantable = ['class', 'weapon', 'helm', 'cape'];
        @endphp

        <div class="lk-sum">
            <div class="lk-sum__cell">
                <div class="lk-sum__label">Level {{ $hero['level'] }}</div>
                <div class="lk-run__line">
                    <span class="lk-cut">{{ $hero['class'] }}</span>
                    @if ($hero['member'] ?? false)
                        <span class="lk-member" title="Member">M</span>
                    @endif
                </div>
            </div>
            <div class="lk-sum__cell">
                <div class="lk-sum__label">{{ $playing ? 'Playing in' : 'Last seen ' . \Lorekeeper\Apps\Desktop\Bots\Scene\Clock::ago($scene->at, $now) }}</div>
                <div class="lk-run__line"><span class="lk-cut">{{ $scene->place() }}</span></div>
            </div>
            <div class="lk-sum__cell">
                <div class="lk-sum__label">Health</div>
                <div class="lk-run__line" style="color: var(--lk-hp-b);">{{ number_format($hero['maxHealth']) }}</div>
            </div>
            <div class="lk-sum__cell">
                <div class="lk-sum__label">Mana</div>
                <div class="lk-run__line" style="color: var(--lk-mp-b);">{{ number_format($hero['maxMana']) }}</div>
            </div>
            @if (($hero['maxExperience'] ?? 0) > 0 && $hero['level'] < 100)
                <div class="lk-sum__cell lk-run__meter"><x-scene.bar :current="$hero['experience']" :maximum="$hero['maxExperience']" kind="experience" /></div>
            @endif
            @if (array_key_exists('gold', $hero))
                <div class="lk-sum__cell">
                    <div class="lk-sum__label">Gold</div>
                    <div class="lk-run__line" style="color: var(--lk-gold);">{{ number_format($hero['gold']) }}</div>
                </div>
            @endif
        </div>

        <div class="lk-run__main">
            <div class="lk-run__col">
                <section class="lk-box">
                    <div class="lk-box__head"><span>Equipment</span><span class="lk-dim">{{ count($equipped) }} worn</span></div>
                    @forelse ($equipped as $held)
                        <div class="lk-gear" wire:key="worn-{{ $held['id'] }}">
                            <x-scene.item-icon :kind="$held['kind']" />
                            <span class="lk-gear__what">
                                <span class="lk-cut lk-gear__name" title="{{ $held['name'] }}">{{ $held['name'] }}</span>
                                <span class="lk-dim lk-cut">
                                    {{ $held['type'] }}
                                    @isset ($held['level'])
                                        · level {{ $held['level'] }}
                                    @endisset
                                    @isset ($held['variance'])
                                        · {{ $held['variance'] === 0 ? 'steady damage' : '±' . $held['variance'] . '% damage' }}
                                    @endisset
                                </span>
                            </span>
                            @if (($held['enhancement'] ?? null) !== null)
                                @php $enhancement = $held['enhancement']; @endphp
                                <span class="lk-gear__enh" @if ($enhancement['effect'] !== null) title="{{ $enhancement['effect']['description'] }}" @endif>
                                    <span>{{ $enhancement['type'] }} {{ $enhancement['rarity'] }}</span>
                                    <small>
                                        level {{ $enhancement['level'] }}
                                        @if ($enhancement['effect'] !== null)
                                            · {{ $enhancement['effect']['name'] }}
                                        @endif
                                    </small>
                                </span>
                            @elseif (in_array($held['kind'], $enchantable, true))
                                <span class="lk-faint lk-gear__bare">Not enhanced</span>
                            @endif
                        </div>
                    @empty
                        <div class="lk-dim">The bot has not seen what {{ $hero['name'] }} wears yet. It shows up after the next run.</div>
                    @endforelse
                </section>

                @if (($hero['stats'] ?? []) !== [])
                    <section class="lk-box">
                        <div class="lk-box__head"><span>Stats</span><span class="lk-dim">hover a stat to see what it does</span></div>
                        <div class="lk-stats">
                            @foreach (collect($hero['stats'])->groupBy('group') as $group => $rows)
                                <div class="lk-stats__group" wire:key="stats-{{ $group }}">
                                    <div class="lk-sheet__label">{{ $group }}</div>
                                    @foreach ($rows as $row)
                                        <div class="lk-stats__row" title="{{ $row['about'] }}">
                                            <span class="lk-stats__name">{{ $row['name'] }}</span>
                                            <span class="lk-num">{{ $row['shown'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="lk-box">
                    <div class="lk-box__head"><span>Inventory</span><span class="lk-dim">{{ number_format($scene->backpack->count()) }}</span></div>
                    <x-scene.backpack :backpack="$scene->backpack" />
                </section>
            </div>

            <section class="lk-box lk-sheet__class">
                <div class="lk-box__head">
                    <span>{{ $hero['class'] }}</span>
                    <span class="lk-dim">Rank {{ $hero['rank'] }} · {{ $hero['rankTo'] === null ? 'max rank' : $scene->rankPercent() . '% to rank ' . ($hero['rank'] + 1) }}</span>
                </div>
                <span class="lk-track lk-sheet__rank"><span style="width: {{ $scene->rankPercent() }}%;"></span></span>

                @forelse ($hero['skills'] as $index => $skill)
                    @php
                        $key = match ($skill['slot']) {
                            'AutoAttack' => 'A',
                            'Potion' => 'P',
                            default => (string) $index,
                        };
                        $facts = array_filter([
                            ($skill['restores'] ?? 0) > 0 ? 'restores ' . $skill['restores'] . ' mana' : ($skill['cost'] > 0 ? $skill['cost'] . ' mana' : null),
                            $skill['cooldown'] . 's cooldown',
                            $skill['targets'] > 1 ? 'up to ' . $skill['targets'] . ' targets' : null,
                            ($skill['damage'] ?? null) !== null ? (int) round($skill['damage'] * 100) . '% ' . ($skill['source'] ?? '') . ' damage' : null,
                        ]);
                    @endphp
                    <div class="lk-skill" wire:key="skill-{{ $skill['slot'] }}" @unless ($skill['unlocked']) data-locked @endunless>
                        <span class="lk-skill__key" data-target="{{ $skill['target'] }}">{{ $key }}</span>
                        <div class="lk-skill__body">
                            <div class="lk-skill__head">
                                <span class="lk-skill__name">{{ $skill['name'] ?? $skill['slot'] }}</span>
                                @unless ($skill['unlocked'])
                                    <span class="lk-tag">Locked</span>
                                @endunless
                            </div>
                            <div class="lk-dim">{{ ucfirst($skill['target']) }} · {{ implode(' · ', $facts) }}</div>
                            @if (($skill['description'] ?? null) !== null)
                                <div class="lk-skill__text">{{ $skill['description'] }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="lk-dim">The bot has not seen this class's skills yet.</div>
                @endforelse

                @if (($hero['passives'] ?? []) !== [])
                    <div class="lk-sheet__label">Passives</div>
                    @foreach ($hero['passives'] as $passive)
                        <div class="lk-passive" wire:key="passive-{{ $loop->index }}">
                            <span class="lk-skill__name">{{ $passive['name'] }}</span>
                            <span class="lk-skill__text">{{ $passive['description'] }}</span>
                        </div>
                    @endforeach
                @endif
            </section>
        </div>

    @endif
</div>
