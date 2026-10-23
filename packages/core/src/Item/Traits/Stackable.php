<?php

declare(strict_types=1);

namespace Lorekeeper\Core\Item\Traits;

use Lorekeeper\Core\Support\Quantity;

interface Stackable
{
    public Quantity $stack { get; }
}
