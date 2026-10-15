<?php

declare(strict_types=1);

namespace Lorekeeper\Persistence\Codec;

use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\Attribute\Health;
use Lorekeeper\Core\Attribute\Mana;
use Lorekeeper\Core\CharacterClass\ClassDescription;
use Lorekeeper\Core\CharacterClass\ClassId;
use Lorekeeper\Core\CharacterClass\ClassName;
use Lorekeeper\Core\CharacterClass\ManaRegeneration;
use Lorekeeper\Core\Item\ItemDescription;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\World\Faction\FactionId;
use Lorekeeper\Core\World\Faction\FactionName;
use Lorekeeper\Core\World\Map\MapId;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Monster\MonsterId;
use Lorekeeper\Core\World\Monster\MonsterLevel;
use Lorekeeper\Core\World\Monster\MonsterName;
use Lorekeeper\Core\World\Npc\NpcId;
use Lorekeeper\Core\World\Npc\NpcName;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\QuestName;
use Lorekeeper\Core\World\Region\RegionId;
use Lorekeeper\Core\World\Region\RegionName;
use Lorekeeper\Core\World\Shop\ShopId;
use Psl\Type;

final readonly class DefaultCodecs
{
    public static function create(): Codecs
    {
        return new Codecs(self::subjects(), [
            ...self::scalars(),
            ...ItemCodecs::all(),
            ...WorldCodecs::all(),
            ...QuestCodecs::all(),
        ]);
    }

    /** @return list<SubjectCodec> */
    private static function subjects(): array
    {
        $id = static fn(string $id): int => Type\positive_int()->coerce($id);

        return [
            new SubjectCodec('item', ItemId::class, static fn(string $v) => new ItemId($id($v))),
            new SubjectCodec('quest', QuestId::class, static fn(string $v) => new QuestId($id($v))),
            new SubjectCodec('monster', MonsterId::class, static fn(string $v) => new MonsterId($id($v))),
            new SubjectCodec('faction', FactionId::class, static fn(string $v) => new FactionId($id($v))),
            new SubjectCodec('class', ClassId::class, static fn(string $v) => new ClassId($id($v))),
            new SubjectCodec('shop', ShopId::class, static fn(string $v) => new ShopId($id($v))),
            new SubjectCodec('region', RegionId::class, static fn(string $v) => new RegionId($id($v))),
            new SubjectCodec('map', MapName::class, static fn(string $v) => new MapName($v)),
            new SubjectCodec('class_name', ClassName::class, static fn(string $v) => new ClassName($v)),
            new SubjectCodec('npc', NpcId::class, NpcId::fromString(...)),
        ];
    }

    /** @return list<ValueCodec> */
    private static function scalars(): array
    {
        return [
            new StringCodec('item.name', ItemName::class),
            new StringCodec('item.description', ItemDescription::class),
            new StringCodec('asset.linkage', Linkage::class),
            new StringCodec('monster.name', MonsterName::class),
            new StringCodec('quest.name', QuestName::class),
            new StringCodec('faction.name', FactionName::class),
            new StringCodec('region.name', RegionName::class),
            new StringCodec('npc.name', NpcName::class),
            new StringCodec('class.description', ClassDescription::class),
            new StringCodec('class.mana_regeneration', ManaRegeneration::class),
            new IntegerCodec('attribute.health', Health::class, static fn(int $v) => new Health($v)),
            new IntegerCodec('attribute.mana', Mana::class, static fn(int $v) => new Mana($v)),
            new IntegerCodec('monster.level', MonsterLevel::class, static fn(int $v) => new MonsterLevel($v)),
            new IntegerCodec('map.id', MapId::class, static fn(int $v) => new MapId($v)),
        ];
    }
}
