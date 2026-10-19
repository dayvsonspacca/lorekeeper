<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Api\TravelMap;

use Lorekeeper\Core\World\Map\MapId;
use Lorekeeper\Core\World\Region\RegionMap;

final readonly class TravelMapEntry
{
    public function __construct(
        public MapId $id,
        public RegionMap $map,
        public bool $memberOnly,
    ) {}
}
