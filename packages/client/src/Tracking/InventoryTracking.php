<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tracking;

use Lorekeeper\Core\Player\PlayerInventory;
use Lorekeeper\Server\Event\Event;
use Lorekeeper\Server\Event\Inventory\PlayerInventoryLoadedEvent;

final readonly class InventoryTracking
{
    public static function follow(PlayerInventory $inventory, Event $event): PlayerInventory
    {
        return $event instanceof PlayerInventoryLoadedEvent ? $event->inventory : $inventory;
    }
}
