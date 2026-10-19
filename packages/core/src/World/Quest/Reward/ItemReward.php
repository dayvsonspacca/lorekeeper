<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest\Reward;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Support\Quantity;

final readonly class ItemReward implements QuestReward
{
    public function __construct(
        public ItemId $itemId,
        public DropRate $dropRate,
        public Quantity $quantity,
        public ItemRewardType $type,
    ) {}
}
