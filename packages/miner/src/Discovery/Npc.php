<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\World\Npc\NpcName;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Shop\ShopId;

final readonly class Npc
{
    /**
     * @param list<QuestId> $quests
     * @param list<ShopId> $shops
     */
    public function __construct(
        public NpcName $name,
        public array $quests,
        public array $shops,
    ) {}
}
