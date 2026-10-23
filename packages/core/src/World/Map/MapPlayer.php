<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Map;

use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\Game\Attribute\Vitals;
use Lorekeeper\Core\Player\PlayerLevel;
use Lorekeeper\Core\Player\PlayerName;

final readonly class MapPlayer
{
    /** @mago-expect lint:excessive-parameter-list */
    public function __construct(
        public PlayerName $name,
        public PlayerLevel $level,
        public Vitals $vitals,
        public MapSpot $spot,
        public CombatState $state,
        public bool $away,
    ) {}
}
