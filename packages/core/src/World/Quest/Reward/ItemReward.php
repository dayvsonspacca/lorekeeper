<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest\Reward;

use Lorekeeper\Core\Item\ItemId;
use Psl\Type;

final readonly class ItemReward implements QuestReward
{
    /** @var positive-int */
    public int $quantity;

    public function __construct(
        public ItemId $itemId,
        public DropRate $dropRate,
        int $quantity,
    ) {
        $this->quantity = Type\positive_int()->assert($quantity);
    }
}
