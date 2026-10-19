<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Monster;

use Lorekeeper\Core\Attribute\Vitals;
use Lorekeeper\Core\Combat\CombatState;

final readonly class MonsterSpawn
{
    public function __construct(
        public MonsterSpawnId $id,
        public Monster $monster,
        public Vitals $vitals,
        public CombatState $state,
    ) {}
}
