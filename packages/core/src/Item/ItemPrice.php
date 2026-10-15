<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item;

use Lorekeeper\Core\Currency\AdventureCoins;
use Lorekeeper\Core\Currency\Gold;

final readonly class ItemPrice
{
    public function __construct(
        public Gold|AdventureCoins $amount,
    ) {}
}
