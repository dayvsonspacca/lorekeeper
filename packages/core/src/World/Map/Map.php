<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Map;

use Lorekeeper\Core\Game\Asset\Gamefile;

final readonly class Map
{
    public function __construct(
        public MapName $name,
        public Gamefile $file,
        public MapMonsters $monsters,
    ) {}
}
