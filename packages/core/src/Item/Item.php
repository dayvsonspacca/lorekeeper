<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item;

abstract readonly class Item
{
    public function __construct(
        public ItemId $id,
        public ItemName $name,
    ) {}
}
