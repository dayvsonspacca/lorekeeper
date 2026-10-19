<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item;

use Lorekeeper\Core\Support\Tag\Taggable;

abstract readonly class Item
{
    /** @use Taggable<ItemTag> */
    use Taggable;

    /** @param list<ItemTag> $tags */
    public function __construct(
        public ItemId $id,
        public ItemName $name,
        array $tags = [],
    ) {
        $this->tags = $tags;
    }
}
