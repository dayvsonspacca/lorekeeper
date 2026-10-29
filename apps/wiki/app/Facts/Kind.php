<?php

declare(strict_types=1);

namespace Lorekeeper\Apps\Wiki\Facts;

use Lorekeeper\Core\Item\Item;

final readonly class Kind
{
    /** @param class-string<Item> $item */
    public function __construct(
        public string $item,
    ) {}
}
