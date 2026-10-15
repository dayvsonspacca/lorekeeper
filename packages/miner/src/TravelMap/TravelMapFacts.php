<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\TravelMap;

use Lorekeeper\Core\Access\MemberOnly;
use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\Progression\LevelRange;
use Lorekeeper\Core\World\Map\MapDescription;
use Lorekeeper\Core\World\Map\MapId;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapTitle;
use Lorekeeper\Core\World\Region\RegionId;
use Lorekeeper\Core\World\Region\RegionMap;
use Lorekeeper\Core\World\Region\RegionName;
use Lorekeeper\Miner\Exception\TravelMapUnavailableException;
use Psl\Option;
use Psl\Type;
use Psl\Type\Exception\CoercionException;

/**
 * @psalm-type Map = array{
 *     mapID: positive-int,
 *     mapName: non-empty-string,
 *     mapTitle: non-empty-string,
 *     description: string,
 *     bUpgrade: bool,
 *     minLevel: int<0, 100>,
 *     maxLevel: int<0, 100>,
 *     ...
 * }
 */
final readonly class TravelMapFacts
{
    /**
     * @throws TravelMapUnavailableException
     *
     * @return list<Fact<MapName|RegionId, object>>
     */
    public static function fromJson(string $json): array
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

        $facts = [];
        $maps = [];

        foreach ($regions as $region) {
            $regionId = new RegionId($region['regionID']);
            $facts[] = new Fact($regionId, new RegionName($region['regionName']));

            foreach ($region['maps'] ?? [] as $map) {
                $facts[] = new Fact($regionId, self::regionMap($map));
                $maps[$map['mapName']] = $map;
            }
        }

        foreach ($maps as $map) {
            $facts = [...$facts, ...self::mapFacts($map)];
        }

        return $facts;
    }

    /** @param Map $map */
    private static function regionMap(array $map): RegionMap
    {
        $description = trim($map['description']);

        return new RegionMap(
            new MapName($map['mapName']),
            new MapTitle($map['mapTitle']),
            $description === '' ? Option\none() : Option\some(new MapDescription($description)),
            new LevelRange($map['minLevel'], $map['maxLevel']),
        );
    }

    /**
     * @param Map $map
     *
     * @return list<Fact<MapName, MapId|MemberOnly>>
     */
    private static function mapFacts(array $map): array
    {
        $mapName = new MapName($map['mapName']);
        $facts = [new Fact($mapName, new MapId($map['mapID']))];

        if ($map['bUpgrade']) {
            $facts[] = new Fact($mapName, new MemberOnly());
        }

        return $facts;
    }
}
