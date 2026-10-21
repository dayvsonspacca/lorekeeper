<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Inventory\Helper;

enum ItemArrival
{
    case TemporaryGained;
    case TemporaryStacked;
    case PermanentStacked;
}
