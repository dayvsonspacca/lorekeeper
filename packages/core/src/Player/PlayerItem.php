<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player;

use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Support\Quantity;

final readonly class PlayerItem extends Item
{
    public function __construct(
        ItemId $id,
        ItemName $name,
        public Quantity $quantity = new Quantity(1),
    ) {
        parent::__construct($id, $name);
    }
}
