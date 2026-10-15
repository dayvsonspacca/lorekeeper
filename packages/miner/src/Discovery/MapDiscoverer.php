<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\World\Map\MapName;

interface MapDiscoverer
{
    /** @return list<MapName> */
    public function discover(Gamefile $mapFile): array;
}
