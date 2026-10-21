<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Inventory;

use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;

abstract readonly class InventoryUpdatedEvent implements Event
{
    use NamedAfterClass;
}
