<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\Asset\Gamefile;

interface MapDiscoverer
{
    public function discover(Gamefile $mapFile): MapAnalysis;
}
