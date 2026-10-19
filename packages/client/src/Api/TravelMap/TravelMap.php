<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Api\TravelMap;

use Lorekeeper\Core\Progression\LevelRange;
use Lorekeeper\Core\World\Map\MapDescription;
use Lorekeeper\Core\World\Map\MapId;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapTitle;
use Lorekeeper\Core\World\Region\RegionId;
use Lorekeeper\Core\World\Region\RegionMap;
use Lorekeeper\Core\World\Region\RegionName;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

final readonly class TravelMap
{
    /** @param list<TravelMapRegion> $regions */
    public function __construct(
        public array $regions,
    ) {}

    /** @throws TravelMapUnavailableException */
    public static function fromJson(string $json): self
    {
        $type = Type\json_decoded(Type\vec(Type\shape([
            'regionID' => Type\positive_int(),
            'regionName' => Type\non_empty_string(),
            'maps' => Type\optional(Type\vec(Type\shape([
                'mapID' => Type\positive_int(),
                'mapName' => Type\non_empty_string(),
                'mapTitle' => Type\non_empty_string(),
                'description' => Type\string(),
                'bUpgrade' => Type\bool(),
                'minLevel' => Type\int_range(min: 0, max: 100),
                'maxLevel' => Type\int_range(min: 0, max: 100),
            ], allowUnknownFields: true))),
        ], allowUnknownFields: true)));

        try {
            $regions = $type->coerce($json);
        } catch (CoercionException $exception) {
            throw new TravelMapUnavailableException('Unexpected travel map response.', previous: $exception);
        }

        return new self(array_map(
            static fn(array $region): TravelMapRegion => new TravelMapRegion(
                new RegionId($region['regionID']),
                new RegionName($region['regionName']),
                array_map(static function (array $map): TravelMapEntry {
                    $description = trim($map['description']);

                    return new TravelMapEntry(
                        new MapId($map['mapID']),
                        new RegionMap(
                            new MapName($map['mapName']),
                            new MapTitle($map['mapTitle']),
                            $description === '' ? Option\none() : Option\some(new MapDescription($description)),
                            new LevelRange($map['minLevel'], $map['maxLevel']),
                        ),
                        $map['bUpgrade'],
                    );
                }, $region['maps'] ?? []),
            ),
            $regions,
        ));
    }
}
