<?php

declare(strict_types=1);

namespace Lorekeeper\Server\Tests\Event\Inventory;

use Lorekeeper\Server\Event\Inventory\DropPickedUpEvent;
use Lorekeeper\Server\Event\Inventory\InventoryLoadedEvent;
use Lorekeeper\Server\Event\Inventory\InventoryUpdatedEvent;
use Lorekeeper\Server\Event\Inventory\ItemDroppedEvent;
use Lorekeeper\Server\Event\Inventory\ItemsEnhancedEvent;
use Lorekeeper\Server\Event\Inventory\ItemsIncreasedEvent;
use Lorekeeper\Server\Event\Inventory\QuestItemsIncreasedEvent;
use Lorekeeper\Server\Event\Inventory\QuestItemsReceivedEvent;
use Lorekeeper\Server\Event\Quest\ItemsTurnedInEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InventoryUpdatedEventTest extends TestCase
{
    #[Test]
    public function every_event_that_changes_what_is_held_is_marked_as_an_inventory_update(): void
    {
        foreach ([
            InventoryLoadedEvent::class,
            QuestItemsReceivedEvent::class,
            QuestItemsIncreasedEvent::class,
            ItemsIncreasedEvent::class,
            DropPickedUpEvent::class,
            ItemsTurnedInEvent::class,
            ItemsEnhancedEvent::class,
        ] as $event) {
            static::assertTrue(is_subclass_of($event, InventoryUpdatedEvent::class), $event);
        }

        static::assertFalse(is_subclass_of(ItemDroppedEvent::class, InventoryUpdatedEvent::class));
    }
}
