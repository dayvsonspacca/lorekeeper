<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Core\World\Map\MapLink;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Map\MapNpc;
use Lorekeeper\Core\World\Npc\NpcId;
use Lorekeeper\Core\World\Npc\NpcQuest;
use Lorekeeper\Core\World\Npc\NpcShop;

final readonly class AnalysisFacts
{
    /** @return list<Fact<Subject, object>> */
    public static function of(MapName $map, MapAnalysis $analysis): array
    {
        $facts = [];

        foreach ($analysis->links as $link) {
            if ($link->value === $map->value) {
                continue;
            }

            $facts[] = new Fact($map, new MapLink($link));
        }

        foreach ($analysis->npcs as $npc) {
            $id = new NpcId($map, $npc->popup);
            $facts[] = new Fact($map, new MapNpc($id));
            if ($npc->name !== null) {
                $facts[] = new Fact($id, $npc->name);
            }

            if ($npc->title !== null) {
                $facts[] = new Fact($id, $npc->title);
            }

            foreach ($npc->quests as $quest) {
                $facts[] = new Fact($id, new NpcQuest($quest));
            }

            foreach ($npc->shops as $shop) {
                $facts[] = new Fact($id, new NpcShop($shop));
            }
        }

        return $facts;
    }
}
