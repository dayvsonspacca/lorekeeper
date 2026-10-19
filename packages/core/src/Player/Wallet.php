<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player;

use Lorekeeper\Core\Currency\AdventureCoins;
use Lorekeeper\Core\Currency\Gold;

final readonly class Wallet
{
    public function __construct(
        public Gold $gold,
        public AdventureCoins $coins,
    ) {}
}
