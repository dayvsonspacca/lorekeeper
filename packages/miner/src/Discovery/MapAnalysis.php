<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\World\Map\MapExit;
use Lorekeeper\Core\World\Map\MapName;

final readonly class MapAnalysis
{
    /**
     * @param list<MapName> $mentions
     * @param list<MapExit> $exits
     * @param list<Npc> $npcs
     */
    public function __construct(
        public array $mentions,
        public array $exits,
        public array $npcs,
    ) {}
}
