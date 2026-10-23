<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero\Inventory;

use Lorekeeper\Core\World\Currency\AdventureCoins;
use Lorekeeper\Core\World\Currency\Gold;

final readonly class HeroWallet
{
    public function __construct(
        public Gold $gold,
        public AdventureCoins $coins,
    ) {}
}
