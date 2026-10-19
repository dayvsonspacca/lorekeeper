<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Tracking;

use Lorekeeper\Client\Tracking\InventoryTracking;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Player\PlayerInventory;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Player\PlayerItems;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Inventory\PlayerInventoryLoadedEvent;
use Lorekeeper\Server\Event\Inventory\TemporaryItemsAddedEvent;
use Lorekeeper\Server\Event\Quest\ItemsTurnedInEvent;
use Lorekeeper\Server\Event\Quest\TurnedInItem;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InventoryTrackingTest extends TestCase
{
    #[Test]
    public function quest_items_pile_up_as_temporary_and_leave_when_turned_in(): void
    {
        $inventory = new PlayerInventory(new PlayerItems([self::token(3)]));

        $inventory = InventoryTracking::follow($inventory, new TemporaryItemsAddedEvent([self::ore(1)]));
        $inventory = InventoryTracking::follow($inventory, new TemporaryItemsAddedEvent([self::ore(2)]));
        static::assertSame([3, 3], [
            $inventory->quantity(new ItemId(42_658)),
            $inventory->temporary->find(new ItemId(42_658))->unwrap()->quantity->value,
        ]);

        $inventory = InventoryTracking::follow($inventory, self::turnedIn(42_658, 2));
        static::assertSame(1, $inventory->quantity(new ItemId(42_658)));

        $inventory = InventoryTracking::follow($inventory, self::turnedIn(42_658, 1));
        static::assertTrue($inventory->find(new ItemId(42_658))->isNone());
        static::assertSame(3, $inventory->quantity(new ItemId(42_657)));
    }

    #[Test]
    public function a_quest_can_take_kept_items_too(): void
    {
        $inventory = InventoryTracking::follow(
            new PlayerInventory(new PlayerItems([self::token(3)])),
            self::turnedIn(42_657, 3),
        );

        static::assertTrue($inventory->find(new ItemId(42_657))->isNone());
    }

    #[Test]
    public function loading_the_inventory_keeps_what_is_held_for_now(): void
    {
        $inventory = new PlayerInventory(new PlayerItems([]), new PlayerItems([self::ore(1)]));

        $inventory = InventoryTracking::follow($inventory, new PlayerInventoryLoadedEvent(new PlayerInventory(new PlayerItems([self::token(
            5,
        )]))));

        static::assertSame([5, 1], [
            $inventory->quantity(new ItemId(42_657)),
            $inventory->quantity(new ItemId(42_658)),
        ]);
    }

    #[Test]
    public function other_events_leave_the_inventory_as_it_is(): void
    {
        $inventory = new PlayerInventory(new PlayerItems([self::token(3)]));

        static::assertSame($inventory, InventoryTracking::follow($inventory, new PlayerDiedEvent()));
    }

    private static function ore(int $quantity): PlayerItem
    {
        return new PlayerItem(new Resource(new ItemId(42_658), new ItemName('Metal Ore')), new Quantity($quantity));
    }

    private static function token(int $quantity): PlayerItem
    {
        return new PlayerItem(new Resource(new ItemId(42_657), new ItemName('Crypto Token')), new Quantity($quantity));
    }

    private static function turnedIn(int $item, int $quantity): ItemsTurnedInEvent
    {
        return new ItemsTurnedInEvent([new TurnedInItem(new ItemId($item), new Quantity($quantity))]);
    }
}
