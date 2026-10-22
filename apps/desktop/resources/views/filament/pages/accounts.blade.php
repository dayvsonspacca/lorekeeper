<x-filament-panels::page>
    <div wire:poll.1s class="lk lk-board">
        @if ($counts['all'] === 0)
            <x-filament::empty-state icon="heroicon-o-user-group" heading="No characters yet" description="Add the AQW accounts your bots will play." />
        @else
            <div class="lk-seg" role="group" aria-label="Show">
                @foreach (['all' => 'All', 'game' => 'In game', 'off' => 'Off'] as $key => $name)
                    <button type="button" wire:click="$set('show', '{{ $key }}')" aria-pressed="{{ $show === $key ? 'true' : 'false' }}">{{ $name }} <span class="lk-dim">{{ $counts[$key] }}</span></button>
                @endforeach
            </div>

            <div class="lk-cards">
                @forelse ($accounts as $account)
                    @php
                        $active = $account->playing();
                        $scene = $account->scene();
                        $seen = $scene?->hero;
                        $hero = $active ? $seen : null;
                        $name = $hero['name'] ?? $account->name;
                        [$state, $label] = match (true) {
                            $account->started_at === null && !$active => ['offline', 'Never played'],
                            !$active => [$account->status->value, $account->status->getLabel()],
                            $hero === null => ['entering', 'Entering'],
                            $hero['away'] => ['away', 'Away'],
                            $hero['state'] === 'dead' => ['dead', 'Fallen'],
                            $hero['state'] === 'fighting' => ['fighting', 'Fighting'],
                            default => ['idle', 'Idle'],
                        };
                        $place = match (true) {
                            $hero !== null => $scene->place(),
                            $active => $account->map ?? '—',
                            $seen !== null => $seen['map'],
                            $account->started_at !== null => '—',
                            default => 'Not played yet',
                        };
                        [$tone, $note] = match (true) {
                            !$active && $account->result !== null => [$state, "{$account->status->getLabel()}: {$account->result}"],
                            $account->script !== null => ['script', $account->script->name . ($account->step !== null ? " · {$account->step}" : '')],
                            $active && $hero === null => [$state, 'Entering the game…'],
                            default => [null, null],
                        };
                        $topped = $hero !== null && (($hero['maxExperience'] ?? 0) === 0 || $hero['level'] >= 100);
                    @endphp
                    <article wire:key="account-{{ $account->id }}" class="lk-card" @unless ($active) data-off @endunless>
                        <header class="lk-card__head">
                            <span class="lk-card__avatar" @if ($seen !== null) style="--hue: {{ \Lorekeeper\Apps\Desktop\Bots\Scene\Palette::hue($seen['category']) }};" @endif aria-hidden="true">{{ mb_strtoupper(mb_substr($name, 0, 1)) }}</span>
                            <div class="lk-card__who">
                                <div><span class="lk-card__name">{{ $name }}</span> <span class="lk-dim">Lv {{ $hero['level'] ?? $account->level ?? '?' }}</span></div>
                                <div class="lk-dim lk-cut">{{ $seen !== null ? "{$seen['class']} · Rank {$seen['rank']}" : $account->server ?? 'First open server' }}</div>
                            </div>
                            <span class="lk-tag" data-state="{{ $state }}">{{ $label }}</span>
                        </header>

                        <div class="lk-card__place">
                            <x-filament::icon icon="heroicon-m-map-pin" />
                            <span class="lk-cut">{{ $place }}</span>
                            <span>{{ $active ? $account->duration() ?? '—' : $account->stopped_at?->shortRelativeDiffForHumans() ?? '—' }}</span>
                        </div>

                        <div class="lk-meters" @if ($hero === null) data-unknown title="Unknown until the character is in game" @endif>
                            <x-scene.bar :current="$hero['health'] ?? 0" :maximum="$hero['maxHealth'] ?? 0" kind="health" :caption="$hero === null ? '—' : null" />
                            <x-scene.bar :current="$hero['mana'] ?? 0" :maximum="$hero['maxMana'] ?? 0" kind="mana" :caption="$hero === null ? '—' : null" />
                            <x-scene.bar :current="$topped ? 1 : $hero['experience'] ?? 0" :maximum="$topped ? 1 : $hero['maxExperience'] ?? 0" kind="experience" :caption="$hero === null ? '—' : ($topped ? 'Max level' : null)" />
                        </div>

                        <div class="lk-card__note lk-cut" data-state="{{ $tone }}" @if ($note !== null) title="{{ $note }}" @endif>{{ $note }}</div>

                        <div class="lk-log lk-card__log">
                            @forelse ($account->logs as $log)
                                <div wire:key="log-{{ $log->id }}" class="lk-log__line"><span class="lk-log__at">{{ \Lorekeeper\Apps\Desktop\Native\LocalTime::clock($log->created_at) }}</span><span data-level="{{ $log->level->value }}">{{ $log->message }}</span></div>
                            @empty
                                <div class="lk-log__at">Nothing logged yet.</div>
                            @endforelse
                        </div>

                        <footer class="lk-card__actions">
                            @if ($account->script !== null)
                                {{ ($this->stopScriptAction)(['account' => $account->id]) }}
                            @elseif ($account->status !== \Lorekeeper\Apps\Desktop\Bots\BotStatus::Stopping)
                                {{ ($this->playScriptAction)(['account' => $account->id]) }}
                            @endif
                            @if ($active)
                                {{ ($this->stopAction)(['account' => $account->id]) }}
                            @else
                                <x-filament::button size="sm" wire:click="start({{ $account->id }})">Start</x-filament::button>
                            @endif
                            {{
                                \Filament\Actions\ActionGroup::make([
                                    ($this->editAccountAction)(['account' => $account->id]),
                                    ($this->deleteAccountAction)(['account' => $account->id]),
                                ])
                                    ->label("More for {$account->name}")
                                    ->hiddenLabel()
                                    ->icon('heroicon-m-ellipsis-horizontal')
                                    ->color('gray')
                                    ->size('sm')
                                    ->button()
                                    ->outlined()
                                    ->dropdownPlacement('top-end')
                            }}
                        </footer>
                    </article>
                @empty
                    <div class="lk-dim">No characters here.</div>
                @endforelse
            </div>
        @endif
    </div>
</x-filament-panels::page>
