<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat;

use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Player\PlayerName;
use Override;
use Psl\Option;

final readonly class PlayersManaChangedEvent extends CombatRoundEvent
{
    /** @param non-empty-list<PlayerMana> $players */
    public function __construct(
        public array $players,
    ) {}

    #[Override]
    protected static function read(array $monsters, array $players): Option\Option
    {
        $changed = [];
        foreach ($players as $name => $player) {
            if (!array_key_exists('intMP', $player)) {
                continue;
            }

            $changed[] = new PlayerMana(new PlayerName($name), new Mana($player['intMP']));
        }

        return $changed === [] ? Option\none() : Option\some(new static($changed));
    }
}
