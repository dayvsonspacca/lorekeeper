<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Map;

use Lorekeeper\Core\Game\Combat\CombatState;
use Lorekeeper\Core\Player\Player;

final class MapPlayer
{
    public function __construct(
        public readonly Player $player,
        public private(set) MapSpot $spot,
        public private(set) CombatState $state,
    ) {}

    public function move(MapSpot $spot): void
    {
        $this->spot = $spot;
    }

    public function change(CombatState $state): void
    {
        $this->state = $state;
    }
}
