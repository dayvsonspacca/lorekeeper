<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Map;

use Lorekeeper\Core\World\Map\MapCell;
use Lorekeeper\Core\World\Monster\MonsterId;
use Lorekeeper\Core\World\Monster\MonsterSpawnId;

final readonly class MonsterPlacement
{
    public function __construct(
        public MonsterSpawnId $id,
        public MonsterId $monster,
        public MapCell $cell,
    ) {}
}
