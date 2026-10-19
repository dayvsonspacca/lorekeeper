<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Monster;

use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\World\Map\MapCell;

final readonly class MonsterSpawn
{
    public function __construct(
        public MonsterSpawnId $id,
        public Monster $monster,
        public MapCell $cell,
        public Vitals $vitals,
        public CombatState $state,
    ) {}
}
