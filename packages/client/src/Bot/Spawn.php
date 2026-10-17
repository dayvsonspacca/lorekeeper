<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Bot;

use Lorekeeper\Core\World\Monster\MonsterSpawnId;
use Lorekeeper\Server\Event\Map\MapMonster;

final readonly class Spawn
{
    public function __construct(
        public MonsterSpawnId $id,
        public MapMonster $monster,
    ) {}
}
