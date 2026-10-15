<?php

declare(strict_types=1);

namespace Lorekeeper\Wiki\Api;

use Lorekeeper\Core\World\Map\MapExit;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Region\RegionId;
use Lorekeeper\Core\World\Region\RegionMap;
use Lorekeeper\Persistence\Store\FactStore;
use Lorekeeper\Wiki\Names;

final readonly class Atlas
{
    public function __construct(
        private FactStore $store,
        private Names $names,
    ) {}

    /** @return array{nodes: list<AtlasNode>, links: list<array{source: string, target: string}>} */
    public function build(): array
    {
        $nodes = [];
        $links = [];

        foreach ($this->store->aboutAll(MapName::class) as $stored) {
            $map = (string) $stored->fact->subject;
            $value = $stored->fact->value;
            $nodes[$map] ??= new AtlasNode($map);
            $nodes[$map]->learn($value);

            if (!$value instanceof MapExit) {
                continue;
            }

            $nodes[$value->mapName->value] ??= new AtlasNode($value->mapName->value);
            $links[] = ['source' => $map, 'target' => $value->mapName->value];
        }

        foreach ($this->store->aboutAll(RegionId::class) as $stored) {
            $value = $stored->fact->value;
            if (!$value instanceof RegionMap || !array_key_exists($value->mapName->value, $nodes)) {
                continue;
            }

            $nodes[$value->mapName->value]->listIn($this->names->of($stored->fact->subject), $value);
        }

        return ['nodes' => array_values($nodes), 'links' => $links];
    }
}
