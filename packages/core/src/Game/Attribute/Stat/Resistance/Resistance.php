<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Game\Attribute\Stat\Resistance;

use Lorekeeper\Core\Game\Attribute\Stat\Stat;
use Lorekeeper\Core\Support\SignedRatio;

abstract readonly class Resistance extends SignedRatio implements Stat
{
    protected const float CAP = 0.8;

    public function effective(): float
    {
        return min($this->value, static::CAP);
    }
}
