<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest\Reward;

use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Support\Quantity;

final readonly class ItemReward implements QuestReward
{
    public function __construct(
        public Item $item,
        public DropRate $dropRate,
        public Quantity $quantity,
        public ItemRewardType $type,
    ) {}
}
