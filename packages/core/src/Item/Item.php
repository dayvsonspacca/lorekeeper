<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item;

use Lorekeeper\Core\Support\Tag\Taggable;
use Lorekeeper\Core\Support\Tag\Tags;

abstract class Item
{
    /** @use Taggable<ItemTag> */
    use Taggable;

    public function __construct(
        public readonly ItemId $id,
        public readonly ItemName $name,
        public readonly ItemLevel $level,
    ) {
        $this->tags = new Tags();
    }
}
