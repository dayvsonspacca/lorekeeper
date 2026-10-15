<?php

declare(strict_types=1);

namespace Lorekeeper\Wiki;

use Lorekeeper\Core\CharacterClass\ClassName;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Core\World\Faction\FactionId;
use Lorekeeper\Core\World\Faction\FactionName;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Monster\MonsterId;
use Lorekeeper\Core\World\Monster\MonsterName;
use Lorekeeper\Core\World\Npc\NpcId;
use Lorekeeper\Core\World\Npc\NpcName;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\QuestName;
use Lorekeeper\Core\World\Region\RegionId;
use Lorekeeper\Core\World\Region\RegionName;
use Lorekeeper\Core\World\Shop\ShopId;
use Lorekeeper\Core\World\Shop\ShopName;
use Psl\Option;

final readonly class Kinds
{
    /** @return list<Kind> */
    public static function all(): array
    {
        return [
            new Kind('map', 'Map', 'Maps', MapName::class, null),
            new Kind('region', 'Region', 'Regions', RegionId::class, RegionName::class),
            new Kind('monster', 'Monster', 'Monsters', MonsterId::class, MonsterName::class),
            new Kind('npc', 'NPC', 'NPCs', NpcId::class, NpcName::class),
            new Kind('shop', 'Shop', 'Shops', ShopId::class, ShopName::class),
            new Kind('item', 'Item', 'Items', ItemId::class, ItemName::class),
            new Kind('quest', 'Quest', 'Quests', QuestId::class, QuestName::class),
            new Kind('faction', 'Faction', 'Factions', FactionId::class, FactionName::class),
            new Kind('class_name', 'Class', 'Classes', ClassName::class, null),
        ];
    }

    /** @return Option\Option<Kind> */
    public static function byKey(string $key): Option\Option
    {
        foreach (self::all() as $kind) {
            if ($kind->key === $key) {
                return Option\some($kind);
            }
        }

        return Option\none();
    }

    /** @return Option\Option<Kind> */
    public static function of(Subject $subject): Option\Option
    {
        foreach (self::all() as $kind) {
            if ($subject instanceof $kind->subjectClass) {
                return Option\some($kind);
            }
        }

        return Option\none();
    }
}
