<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player;

use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Psl\Type;

final readonly class PlayerItem extends Item
{
    /** @var positive-int */
    public int $quantity;

    public function __construct(ItemId $id, ItemName $name, int $quantity)
    {
        parent::__construct($id, $name);

        $this->quantity = Type\positive_int()->assert($quantity);
    }
}
