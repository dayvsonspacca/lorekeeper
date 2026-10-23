<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest\Reward;

use Lorekeeper\Core\World\Currency\Gold;

final readonly class GoldReward implements QuestReward
{
    public function __construct(
        public Gold $gold,
    ) {}
}
