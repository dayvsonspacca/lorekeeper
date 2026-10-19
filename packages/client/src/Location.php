<?php

declare(strict_types=1);

namespace Lorekeeper\Client;

use Lorekeeper\Core\World\Map\Map;
use Lorekeeper\Core\World\Map\MapSpot;
use Lorekeeper\Server\AreaId;

final readonly class Location
{
    public function __construct(
        public AreaId $area,
        public Map $map,
        public MapSpot $spot,
    ) {}
}
