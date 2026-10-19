<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Map;

use Lorekeeper\Core\Asset\Gamefile;

final readonly class Map
{
    /** @param list<MapMonster> $monsters */
    public function __construct(
        public MapName $name,
        public Gamefile $file,
        public array $monsters,
    ) {}
}
