<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapNpc;
use Lorekeeper\Core\World\Npc\NpcId;
use Lorekeeper\Core\World\Npc\NpcQuest;
use Lorekeeper\Core\World\Npc\NpcShop;
use Lorekeeper\Core\World\Npc\NpcTravel;

final readonly class NpcFacts
{
    /** @return list<Fact<Subject, object>> */
    public static function of(MapName $map, Npc $npc): array
    {
        $id = new NpcId($map, $npc->popup);
        $values = [
            ...array_filter([$npc->text->name, $npc->text->title], static fn(?object $value): bool => $value !== null),
            ...array_map(static fn($quest): NpcQuest => new NpcQuest($quest), $npc->buttons->quests),
            ...array_map(static fn($shop): NpcShop => new NpcShop($shop), $npc->buttons->shops),
            ...array_map(static fn($travel): NpcTravel => new NpcTravel($travel), $npc->buttons->travels),
        ];

        return [
            new Fact($map, new MapNpc($id)),
            ...array_map(static fn(object $value): Fact => new Fact($id, $value), $values),
        ];
    }
}
