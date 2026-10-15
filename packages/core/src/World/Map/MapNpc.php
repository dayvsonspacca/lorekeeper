<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Map;

use Lorekeeper\Core\World\Npc\NpcId;

final readonly class MapNpc
{
    public function __construct(
        public NpcId $npcId,
    ) {}
}
