<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Map;

use Lorekeeper\Core\Game\Attribute\Vitals;
use Lorekeeper\Core\Game\Combat\CombatState;
use Lorekeeper\Core\World\Monster\Monster;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;

final readonly class MapMonster
{
    public function __construct(
        public MonsterSpawnId $id,
        public Monster $monster,
        public MapCell $cell,
        public Vitals $vitals,
        public CombatState $state,
    ) {}
}
