<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\Access\MemberOnly;
use Lorekeeper\Core\World\Map\MapName;
use Lorekeeper\Core\World\Npc\NpcId;
use Lorekeeper\Core\World\Npc\NpcQuest;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Quest\QuestName;
use Lorekeeper\Persistence\Store\FactStore;

final readonly class QuestQueue
{
    public function __construct(
        private FactStore $store,
    ) {}

    /** @return array<string, array<string, non-empty-list<QuestId>>> */
    public function toLoad(): array
    {
        $skip = [];
        foreach ($this->store->withValue(QuestName::class) as $stored) {
            if (!$stored->fact->subject instanceof QuestId) {
                continue;
            }

            $skip[$stored->fact->subject->value] = true;
        }

        $memberOnly = [];
        foreach ($this->store->withValue(MemberOnly::class) as $stored) {
            if (!$stored->fact->subject instanceof MapName) {
                continue;
            }

            $memberOnly[$stored->fact->subject->value] = true;
        }

        $maps = [];
        foreach ($this->store->withValue(NpcQuest::class) as $stored) {
            $npc = $stored->fact->subject;
            $quest = $stored->fact->value;
            if (
                !$npc instanceof NpcId
                || !$quest instanceof NpcQuest
                || array_key_exists($npc->map->value, $memberOnly)
                || array_key_exists($quest->questId->value, $skip)
            ) {
                continue;
            }

            $skip[$quest->questId->value] = true;
            $maps[$npc->map->value][$npc->name->value][] = $quest->questId;
        }

        return $maps;
    }
}
