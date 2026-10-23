<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Combat;

use Lorekeeper\Core\Game\Attribute\Health;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;

final readonly class MonsterHealth
{
    public function __construct(
        public MonsterSpawnId $monster,
        public Health $health,
    ) {}
}
