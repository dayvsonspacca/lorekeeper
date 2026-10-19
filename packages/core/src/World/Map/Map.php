<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Map;

use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\World\Monster\MonsterSpawn;

final readonly class Map
{
    /** @param list<MonsterSpawn> $spawns */
    public function __construct(
        public MapName $name,
        public Gamefile $file,
        public array $spawns,
    ) {}
}
