<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Quest\Reward;

enum ItemRewardType
{
    case Guaranteed;
    case Random;
}
