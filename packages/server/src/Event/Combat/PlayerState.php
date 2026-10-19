<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat;

use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Player\PlayerName;

final readonly class PlayerState
{
    public function __construct(
        public PlayerName $player,
        public CombatState $state,
    ) {}
}
