<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Api\TravelMap;

use Lorekeeper\Core\World\Region\RegionId;
use Lorekeeper\Core\World\Region\RegionName;

final readonly class TravelMapRegion
{
    /** @param list<TravelMapEntry> $maps */
    public function __construct(
        public RegionId $id,
        public RegionName $name,
        public array $maps,
    ) {}
}
