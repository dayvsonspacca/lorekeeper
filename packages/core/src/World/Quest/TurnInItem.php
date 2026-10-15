<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest;

use Lorekeeper\Core\Item\ItemId;
use Psl\Type;

final readonly class TurnInItem
{
    /** @var positive-int */
    public int $quantity;

    public function __construct(
        public ItemId $itemId,
        int $quantity,
    ) {
        $this->quantity = Type\positive_int()->assert($quantity);
    }
}
