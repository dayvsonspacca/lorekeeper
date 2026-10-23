<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item;

use Lorekeeper\Core\World\Currency\Currency;

final readonly class ItemPrice
{
    public function __construct(
        public Currency $amount,
    ) {}
}
