<?php

declare(strict_types=1);

namespace Lorekeeper\Miner\Discovery;

use Lorekeeper\Core\Asset\Linkage;
use Lorekeeper\Core\World\Npc\NpcName;
use Lorekeeper\Core\World\Npc\NpcTitle;
use Lorekeeper\Core\World\Quest\QuestId;
use Lorekeeper\Core\World\Shop\ShopId;

final readonly class Npc
{
    /**
     * @param list<QuestId> $quests
     * @param list<ShopId> $shops
     */
    public function __construct(
        public Linkage $popup,
        public array $quests,
        public array $shops,
        public ?NpcName $name = null,
        public ?NpcTitle $title = null,
    ) {}
}
