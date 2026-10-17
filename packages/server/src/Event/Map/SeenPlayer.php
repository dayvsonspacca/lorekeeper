<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\World\Map\Cell;
use Lorekeeper\Core\World\Map\Pad;

final readonly class SeenPlayer
{
    /** @mago-expect lint:excessive-parameter-list */
    public function __construct(
        public PlayerName $name,
        public PlayerLevel $level,
        public Cell $cell,
        public Pad $pad,
        public CombatState $state,
        public Health $health,
        public Health $maxHealth,
        public Mana $mana,
        public Mana $maxMana,
        public bool $away,
    ) {}
}
