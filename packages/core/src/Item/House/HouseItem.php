<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\House;

use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;

final readonly class HouseItem extends Item
{
    public function __construct(
        ItemId $id,
        ItemName $name,
        public HouseItemType $type,
    ) {
        parent::__construct($id, $name);
    }
}
