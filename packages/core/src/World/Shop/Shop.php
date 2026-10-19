<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Shop;

use Lorekeeper\Core\Support\Tag\Taggable;
use Lorekeeper\Core\Support\Tag\Tags;

abstract readonly class Shop
{
    /** @use Taggable<ShopTag> */
    use Taggable;

    public function __construct(
        public ShopId $id,
        public ShopName $name,
    ) {
        $this->tags = new Tags();
    }
}
