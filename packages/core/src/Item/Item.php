<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item;

use Lorekeeper\Core\Support\Tag\Taggable;
use Lorekeeper\Core\Support\Tag\Tags;

abstract readonly class Item
{
    /** @use Taggable<ItemTag> */
    use Taggable;

    public function __construct(
        public ItemId $id,
        public ItemName $name,
        public ItemLevel $level,
    ) {
        $this->tags = new Tags();
    }
}
