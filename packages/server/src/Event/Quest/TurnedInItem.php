<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Quest;

use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Support\Quantity;

final readonly class TurnedInItem
{
    public function __construct(
        public ItemId $item,
        public Quantity $quantity,
    ) {}
}
