<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Shop;

use Lorekeeper\Core\Game\Tag\Tags;
use Lorekeeper\Core\Game\Tag\Traits\Taggable;

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
