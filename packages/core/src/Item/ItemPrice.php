<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item;

use Lorekeeper\Core\World\Currency\AdventureCoins;
use Lorekeeper\Core\World\Currency\Gold;

final readonly class ItemPrice
{
    public function __construct(
        public Gold|AdventureCoins $amount,
    ) {}
}
