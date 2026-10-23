<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Event\Inventory;

use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\NamedAfterClass;

/**
 * Something changed in our player's inventory.
 */
abstract readonly class InventoryUpdatedEvent implements Event
{
    use NamedAfterClass;
}
