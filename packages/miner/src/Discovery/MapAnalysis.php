<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\World\Map\MapName;

final readonly class MapAnalysis
{
    /**
     * @param list<MapName> $links
     * @param list<Npc> $npcs
     */
    public function __construct(
        public array $links,
        public array $npcs,
    ) {}
}
