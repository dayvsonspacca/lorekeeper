<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\Knowledge\Fact;
use Lorekeeper\Core\Knowledge\Subject;
use Lorekeeper\Core\World\Map\MapMention;
use Lorekeeper\Core\World\Map\MapName;

final readonly class AnalysisFacts
{
    /** @return list<Fact<Subject, object>> */
    public static function of(MapName $map, MapAnalysis $analysis): array
    {
        $facts = [];

        foreach ($analysis->mentions as $mention) {
            if ($mention->value === $map->value) {
                continue;
            }

            $facts[] = new Fact($map, new MapMention($mention));
        }

        foreach ($analysis->exits as $exit) {
            if ($exit->mapName->value === $map->value) {
                continue;
            }

            $facts[] = new Fact($map, $exit);
        }

        foreach ($analysis->npcs as $npc) {
            $facts = [...$facts, ...NpcFacts::of($map, $npc)];
        }

        return $facts;
    }
}
