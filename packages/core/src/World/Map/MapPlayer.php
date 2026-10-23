<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Map;

use Lorekeeper\Core\Game\Combat\CombatState;
use Lorekeeper\Core\Player\Player;

final readonly class MapPlayer
{
    public function __construct(
        public Player $player,
        public MapSpot $spot,
        public CombatState $state,
        public bool $away,
    ) {}
}
