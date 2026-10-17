<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\Combat\CombatState;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;

final readonly class SeenMonster
{
    /** @mago-expect lint:excessive-parameter-list */
    public function __construct(
        public MonsterSpawnId $id,
        public CombatState $state,
        public Health $health,
        public Health $maxHealth,
        public Mana $mana,
        public Mana $maxMana,
    ) {}
}
