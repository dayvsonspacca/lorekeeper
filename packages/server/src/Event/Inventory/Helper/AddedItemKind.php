<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Inventory\Helper;

enum AddedItemKind
{
    case NewTemporary;
    case MoreTemporary;
    case MoreKept;
}
