<?php

declare(strict_types=1);

namespace Lorekeeper\Client\Tests\Tracking;

use Lorekeeper\Client\Drops;
use Lorekeeper\Client\Tracking\Identity;
use Lorekeeper\Client\Tracking\InventoryTracking;
use Lorekeeper\Core\Item\ItemId;
use Lorekeeper\Core\Item\ItemLevel;
use Lorekeeper\Core\Item\ItemName;
use Lorekeeper\Core\Item\Misc\Resource;
use Lorekeeper\Core\Player\PlayerInventory;
use Lorekeeper\Core\Player\PlayerItem;
use Lorekeeper\Core\Player\PlayerItems;
use Lorekeeper\Core\Player\PlayerName;
use Lorekeeper\Core\Support\Quantity;
use Lorekeeper\Server\Event\Combat\PlayerDiedEvent;
use Lorekeeper\Server\Event\Inventory\DropPickedUpEvent;
use Lorekeeper\Server\Event\Inventory\PlayerInventoryLoadedEvent;
use Lorekeeper\Server\Event\Inventory\TemporaryItemsAddedEvent;
use Lorekeeper\Server\Event\Quest\ItemsTurnedInEvent;
use Lorekeeper\Server\Event\Quest\TurnedInItem;
use Lorekeeper\Server\SocketId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InventoryTrackingTest extends TestCase
{
    #[Test]
    public function quest_items_pile_up_as_temporary_and_leave_when_turned_in(): void
    {
        $inventory = new PlayerInventory(new PlayerItems([self::token(3)]), new Quantity(30));

        $inventory = InventoryTracking::follow($inventory, new TemporaryItemsAddedEvent([self::ore(1)]), self::me());
        $inventory = InventoryTracking::follow($inventory, new TemporaryItemsAddedEvent([self::ore(2)]), self::me());
        static::assertSame([3, 3], [
            $inventory->quantity(new ItemId(42_658)),
            $inventory->temporary->find(new ItemId(42_658))->unwrap()->quantity->value,
        ]);

        $inventory = InventoryTracking::follow($inventory, self::turnedIn(42_658, 2), self::me());
        static::assertSame(1, $inventory->quantity(new ItemId(42_658)));

        $inventory = InventoryTracking::follow($inventory, self::turnedIn(42_658, 1), self::me());
        static::assertTrue($inventory->find(new ItemId(42_658))->isNone());
        static::assertSame(3, $inventory->quantity(new ItemId(42_657)));
    }

    #[Test]
    public function a_quest_can_take_kept_items_too(): void
    {
        $inventory = InventoryTracking::follow(
            new PlayerInventory(new PlayerItems([self::token(3)]), new Quantity(30)),
            self::turnedIn(42_657, 3),
            self::me(),
        );

        static::assertTrue($inventory->find(new ItemId(42_657))->isNone());
    }

    #[Test]
    public function loading_the_inventory_keeps_what_is_held_for_now(): void
    {
        $inventory = new PlayerInventory(new PlayerItems([]), new Quantity(30), new PlayerItems([self::ore(1)]));

        $inventory = InventoryTracking::follow(
            $inventory,
            new PlayerInventoryLoadedEvent(new PlayerItems([self::token(5)])),
            self::me(),
        );

        static::assertSame([5, 1], [
            $inventory->quantity(new ItemId(42_657)),
            $inventory->quantity(new ItemId(42_658)),
        ]);
    }

    #[Test]
    public function a_picked_up_drop_joins_the_kept_items_with_how_many_are_held_now(): void
    {
        $drops = new Drops([42_657 => self::token(1)]);
        $picked = new DropPickedUpEvent(new ItemId(42_657), new Quantity(1), new Quantity(4));

        $empty = InventoryTracking::follow(
            new PlayerInventory(new PlayerItems([]), new Quantity(30)),
            $picked,
            self::me(),
            $drops,
        );
        $holding = InventoryTracking::follow(
            new PlayerInventory(new PlayerItems([self::token(3)]), new Quantity(30)),
            $picked,
            self::me(),
        );

        static::assertSame(4, $empty->permanent->find(new ItemId(42_657))->unwrap()->quantity->value);
        static::assertSame(4, $holding->quantity(new ItemId(42_657)));
    }

    #[Test]
    public function a_picked_up_drop_it_never_saw_is_left_for_the_next_load(): void
    {
        $inventory = new PlayerInventory(new PlayerItems([]), new Quantity(30));

        static::assertSame($inventory, InventoryTracking::follow(
            $inventory,
            new DropPickedUpEvent(new ItemId(42_657), new Quantity(1), new Quantity(1)),
            self::me(),
        ));
    }

    #[Test]
    public function other_events_leave_the_inventory_as_it_is(): void
    {
        $inventory = new PlayerInventory(new PlayerItems([self::token(3)]), new Quantity(30));

        static::assertSame($inventory, InventoryTracking::follow($inventory, new PlayerDiedEvent(), self::me()));
    }

    private static function me(): Identity
    {
        return new Identity(new SocketId(122_628), new PlayerName('lorekeeper03'));
    }

    private static function ore(int $quantity): PlayerItem
    {
        return new PlayerItem(
            new Resource(new ItemId(42_658), new ItemName('Metal Ore'), new ItemLevel(1)),
            new Quantity($quantity),
        );
    }

    private static function token(int $quantity): PlayerItem
    {
        return new PlayerItem(
            new Resource(new ItemId(42_657), new ItemName('Crypto Token'), new ItemLevel(1)),
            new Quantity($quantity),
        );
    }

    private static function turnedIn(int $item, int $quantity): ItemsTurnedInEvent
    {
        return new ItemsTurnedInEvent([new TurnedInItem(new ItemId($item), new Quantity($quantity))]);
    }
}
