<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat;

use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Player\PlayerName;
use Override;
use Psl\Option;

final readonly class PlayersHealthChangedEvent extends CombatRoundEvent
{
    /** @param non-empty-list<PlayerHealth> $players */
    public function __construct(
        public array $players,
    ) {}

    #[Override]
    protected static function read(array $monsters, array $players): Option\Option
    {
        $changed = [];
        foreach ($players as $name => $player) {
            if (!array_key_exists('intHP', $player)) {
                continue;
            }

            $changed[] = new PlayerHealth(new PlayerName($name), new Health($player['intHP']));
        }

        return $changed === [] ? Option\none() : Option\some(new static($changed));
    }
}
