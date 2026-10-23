<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item;

use Lorekeeper\Core\Game\Tag\Tags;
use Lorekeeper\Core\Game\Tag\Traits\Taggable;

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
