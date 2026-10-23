<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Map;

use Lorekeeper\Core\Game\Attribute\Vitals;
use Lorekeeper\Core\Game\Combat\CombatState;
use Lorekeeper\Core\World\Monster\Monster;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;

final class MapMonster
{
    public function __construct(
        public readonly MonsterSpawnId $id,
        public readonly Monster $monster,
        public readonly MapCell $cell,
        public readonly Vitals $vitals,
        public private(set) CombatState $state,
    ) {}

    public function change(CombatState $state): void
    {
        $this->state = $state;
    }
}
