<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\Misc;

use Lorekeeper\Core\Item\Item;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Traits\Stackable;
use Lorekeeper\Core\Support\Quantity;

final class Resource extends Item implements Stackable
{
    public function __construct(
        ItemId $id,
        ItemName $name,
        ItemLevel $level,
        public readonly Quantity $stack,
    ) {
        parent::__construct($id, $name, $level);
    }
}
