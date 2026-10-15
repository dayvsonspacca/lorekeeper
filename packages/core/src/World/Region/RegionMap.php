<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Region;

use Lorekeeper\Core\Progression\LevelRange;
use Lorekeeper\Core\World\Map\MapDescription;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapTitle;
use Psl\Option\Option;

final readonly class RegionMap
{
    /** @param Option<MapDescription> $description */
    public function __construct(
        public MapName $mapName,
        public MapTitle $title,
        public Option $description,
        public LevelRange $levels,
    ) {}
}
