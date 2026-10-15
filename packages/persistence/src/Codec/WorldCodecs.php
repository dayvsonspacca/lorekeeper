<?php

declare(strict_types=1);

namespace Lorekeeper\Persistence\Codec;

use Lorekeeper\Core\Asset\Gamefile;
use Lorekeeper\Core\CharacterClass\ClassCategory;
use Lorekeeper\Core\Progression\LevelRange;
use Lorekeeper\Core\World\Map\MapAnalyzed;
use Lorekeeper\Core\World\Map\MapDescription;
use Lorekeeper\Core\World\Map\MapLink;
use Lorekeeper\Core\World\Map\MapMonster;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapNpc;
use Lorekeeper\Core\World\Map\MapTitle;
use Lorekeeper\Core\World\Map\MapUnavailable;
use Lorekeeper\Core\World\Monster\MonsterId;
use Lorekeeper\Core\World\Monster\Race;
use Lorekeeper\Core\World\Npc\NpcId;
use Lorekeeper\Core\World\Npc\NpcQuest;
use Lorekeeper\Core\World\Npc\NpcShop;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Region\RegionMap;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Persistence\Exception\UnknownTypeException;
use Psl\Option;
use Psl\Type;

final readonly class WorldCodecs
{
    /** @return list<ValueCodec> */
    public static function all(): array
    {
        return [
            new CustomCodec(
                'monster.race',
                Race::class,
                static fn(object $v): string => Type\instance_of(Race::class)->assert($v)->name,
                self::decodeRace(...),
            ),
            new CustomCodec(
                'class.category',
                ClassCategory::class,
                static fn(object $v): string => Type\instance_of(ClassCategory::class)->assert($v)->name,
                static fn(mixed $p): ClassCategory => Type\string()->assert($p) === ClassCategory::Melee->name
                    ? ClassCategory::Melee
                    : ClassCategory::Caster,
            ),
            new CustomCodec(
                'map.monster',
                MapMonster::class,
                static fn(object $v): int => Type\instance_of(MapMonster::class)->assert($v)->monsterId->value,
                static fn(mixed $p): MapMonster => new MapMonster(new MonsterId(Type\positive_int()->assert($p))),
            ),
            new CustomCodec('region.map', RegionMap::class, self::encodeRegionMap(...), self::decodeRegionMap(...)),
            new CustomCodec(
                'map.link',
                MapLink::class,
                static fn(object $v): string => Type\instance_of(MapLink::class)->assert($v)->mapName->value,
                static fn(mixed $p): MapLink => new MapLink(new MapName(Type\string()->assert($p))),
            ),
            new CustomCodec(
                'map.npc',
                MapNpc::class,
                static fn(object $v): string => (string) Type\instance_of(MapNpc::class)->assert($v)->npcId,
                static fn(mixed $p): MapNpc => new MapNpc(NpcId::fromString(Type\string()->assert($p))),
            ),
            new CustomCodec(
                'map.analyzed',
                MapAnalyzed::class,
                static fn(object $v): string => Type\instance_of(MapAnalyzed::class)->assert($v)->gamefile->value,
                static fn(mixed $p): MapAnalyzed => new MapAnalyzed(new Gamefile(Type\string()->assert($p))),
            ),
            new CustomCodec(
                'npc.quest',
                NpcQuest::class,
                static fn(object $v): int => Type\instance_of(NpcQuest::class)->assert($v)->questId->value,
                static fn(mixed $p): NpcQuest => new NpcQuest(new QuestId(Type\positive_int()->assert($p))),
            ),
            new CustomCodec(
                'npc.shop',
                NpcShop::class,
                static fn(object $v): int => Type\instance_of(NpcShop::class)->assert($v)->shopId->value,
                static fn(mixed $p): NpcShop => new NpcShop(new ShopId(Type\positive_int()->assert($p))),
            ),
            new CustomCodec(
                'map.unavailable',
                MapUnavailable::class,
                static fn(object $v): bool => true,
                static fn(mixed $p): MapUnavailable => new MapUnavailable(),
            ),
        ];
    }

    private static function decodeRace(mixed $payload): Race
    {
        $name = Type\non_empty_string()->assert($payload);

        foreach (Race::cases() as $race) {
            if ($race->name === $name) {
                return $race;
            }
        }

        throw new UnknownTypeException("Unknown race '{$name}'");
    }

    /** @return array{map: string, title: string, description: ?string, minLevel: int, maxLevel: int} */
    private static function encodeRegionMap(object $value): array
    {
        $map = Type\instance_of(RegionMap::class)->assert($value);

        return [
            'map' => $map->mapName->value,
            'title' => $map->title->value,
            'description' => $map->description->map(static fn(MapDescription $d): string => $d->value)->unwrapOr(null),
            'minLevel' => $map->levels->minimum,
            'maxLevel' => $map->levels->maximum,
        ];
    }

    private static function decodeRegionMap(mixed $payload): RegionMap
    {
        $p = Type\shape([
            'map' => Type\non_empty_string(),
            'title' => Type\non_empty_string(),
            'description' => Type\nullable(Type\non_empty_string()),
            'minLevel' => Type\int(),
            'maxLevel' => Type\int(),
        ])->assert($payload);

        return new RegionMap(
            new MapName($p['map']),
            new MapTitle($p['title']),
            Option\from_nullable($p['description'])->map(
                static fn(string $d): MapDescription => new MapDescription($d),
            ),
            new LevelRange($p['minLevel'], $p['maxLevel']),
        );
    }
}
