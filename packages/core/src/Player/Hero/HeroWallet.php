<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Player\Hero;

use Lorekeeper\Core\Currency\AdventureCoins;
use Lorekeeper\Core\Currency\Gold;

final readonly class HeroWallet
{
    public function __construct(
        public Gold $gold,
        public AdventureCoins $coins,
    ) {}
}
