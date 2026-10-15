<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Npc;

use Lorekeeper\Core\World\Map\MapName;

final readonly class NpcTravel
{
    public function __construct(
        public MapName $mapName,
    ) {}
}
