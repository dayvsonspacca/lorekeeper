<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Attribute\Stat\Resistance;

use Lorekeeper\Core\Attribute\Stat\Stat;
use Lorekeeper\Core\Support\SignedRatio;

abstract readonly class Resistance extends SignedRatio implements Stat
{
    protected const float CAP = 0.8;

    public function effective(): float
    {
        return min($this->value, static::CAP);
    }
}
