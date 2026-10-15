<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Npc;

use Lorekeeper\Core\World\Quest\QuestId;

final readonly class NpcQuest
{
    public function __construct(
        public QuestId $questId,
    ) {}
}
