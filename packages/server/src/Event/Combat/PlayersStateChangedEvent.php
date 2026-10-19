<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat;

use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Server\Event\Combat\Helper\CombatStates;
use Override;
use Psl\Option;

final readonly class PlayersStateChangedEvent extends CombatRoundEvent
{
    /** @param non-empty-list<PlayerState> $players */
    public function __construct(
        public array $players,
    ) {}

    #[Override]
    protected static function read(array $monsters, array $players): Option\Option
    {
        $changed = [];
        foreach ($players as $name => $player) {
            if (!array_key_exists('intState', $player)) {
                continue;
            }

            $changed[] = new PlayerState(new PlayerName($name), CombatStates::read($player['intState']));
        }

        return $changed === [] ? Option\none() : Option\some(new static($changed));
    }

    #[Override]
    public function summary(): string
    {
        return implode(', ', array_map(
            static fn(PlayerState $player): string => "{$player->player->value} is " . strtolower($player->state->name),
            $this->players,
        ));
    }
}
