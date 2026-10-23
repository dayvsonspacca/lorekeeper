<?php

declare(strict_types=1);

namespace Lorekeeper\Core\World\Currency;

use Lorekeeper\Core\Support\NonNegativeInteger;

abstract readonly class Currency extends NonNegativeInteger
{
    abstract public function symbol(): string;
}
